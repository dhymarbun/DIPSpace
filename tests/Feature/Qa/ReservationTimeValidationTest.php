<?php

namespace Tests\Feature\Qa;

use App\Models\Reservation;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PHASE 3 — Audit validasi waktu, tanggal, dan input reservasi.
 * Semua aturan harus ditegakkan di sisi server, bukan hanya di UI.
 */
class ReservationTimeValidationTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 08:00:00'); // Senin pagi
    }

    private function assertRejected($response): void
    {
        $hasErrors = false;
        try {
            $response->assertSessionHasErrors();
            $hasErrors = true;
        } catch (\PHPUnit\Framework\AssertionFailedError) {
            // tidak ada validation error; mungkin berupa flash 'error' manual.
        }

        $hasFlash = (bool) ($response->getSession()?->has('error'));

        $this->assertTrue(
            $hasErrors || $hasFlash,
            'Server harus menolak input tidak valid (validasi atau flash error).'
        );
    }

    #[DataProvider('validBoundaries')]
    public function test_valid_boundary_times_are_accepted(string $start, string $end): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility();

        $response = $this->actingAs($user)->post(route('reservations.store'), $this->reservationPayload([
            'facility_id' => $facility->id,
            'start_time' => $start,
            'end_time' => $end,
        ]));

        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reservations', [
            'facility_id' => $facility->id,
            'user_id' => $user->id,
            'start_time' => '2026-06-16 '.$start.':00',
            'end_time' => '2026-06-16 '.$end.':00',
            'status' => 'pending',
        ]);
    }

    public static function validBoundaries(): array
    {
        return [
            'buka 07:00-07:30' => ['07:00', '07:30'],
            '07:30-08:00' => ['07:30', '08:00'],
            'tengah hari 12:00-13:00' => ['12:00', '13:00'],
            '19:00-19:30' => ['19:00', '19:30'],
            'tutup 19:30-20:00' => ['19:30', '20:00'],
        ];
    }

    public function test_multi_slot_booking_is_allowed(): void
    {
        // Spesifikasi mengizinkan multi-slot; tidak boleh dipaksa tepat 30 menit.
        $user = $this->makeUser();
        $facility = $this->makeFacility();

        $this->actingAs($user)->post(route('reservations.store'), $this->reservationPayload([
            'facility_id' => $facility->id,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reservations', [
            'facility_id' => $facility->id,
            'start_time' => '2026-06-16 09:00:00',
            'end_time' => '2026-06-16 11:00:00',
        ]);
    }

    #[DataProvider('invalidTimes')]
    public function test_invalid_times_are_rejected_server_side(string $start, string $end): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility();

        $response = $this->actingAs($user)->from(route('reservations.index'))
            ->post(route('reservations.store'), $this->reservationPayload([
                'facility_id' => $facility->id,
                'start_time' => $start,
                'end_time' => $end,
            ]));

        $this->assertRejected($response);
        $this->assertDatabaseCount('reservations', 0);
    }

    public static function invalidTimes(): array
    {
        return [
            'mulai sebelum 07:00 (06:30)' => ['06:30', '07:00'],
            'mulai 06:00' => ['06:00', '07:00'],
            'selesai lewat 20:00 (20:30)' => ['19:00', '20:30'],
            'mulai 20:00 durasi positif' => ['20:00', '20:30'],
            'mulai tidak align (07:15)' => ['07:15', '08:00'],
            'selesai tidak align (09:15)' => ['08:00', '09:15'],
            'mulai sama dengan selesai' => ['09:00', '09:00'],
            'mulai setelah selesai' => ['10:00', '09:00'],
            'durasi bukan kelipatan 30 (08:20)' => ['08:00', '08:20'],
        ];
    }

    #[DataProvider('malformedFields')]
    public function test_malformed_or_missing_fields_are_rejected(string $field, $value): void
    {
        $user = $this->makeUser();
        $payload = $this->reservationPayload();

        if ($value === '__MISSING__') {
            unset($payload[$field]);
        } else {
            $payload[$field] = $value;
        }

        $response = $this->actingAs($user)->from(route('reservations.index'))
            ->post(route('reservations.store'), $payload);

        $this->assertRejected($response);
        $this->assertDatabaseCount('reservations', 0);
    }

    public static function malformedFields(): array
    {
        return [
            'mulai kosong' => ['start_time', '__MISSING__'],
            'selesai kosong' => ['end_time', '__MISSING__'],
            'tanggal kosong' => ['start_date', '__MISSING__'],
            'tanggal malformed' => ['start_date', '2026-13-40'],
            'waktu malformed' => ['start_time', 'abc'],
            'jam tidak mungkin 25:00' => ['start_time', '25:00'],
            'menit tidak mungkin 12:60' => ['start_time', '12:60'],
            'waktu dengan detik' => ['start_time', '09:00:00'],
            'fasilitas tidak ada' => ['facility_id', 999999],
            'fasilitas bukan angka' => ['facility_id', 'abc'],
            'tujuan kosong' => ['tujuan', ''],
            'tujuan hanya spasi' => ['tujuan', '     '],
            'tujuan terlalu panjang' => ['tujuan', str_repeat('a', 256)],
        ];
    }

    public function test_past_date_is_rejected(): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility();

        $response = $this->actingAs($user)->from(route('reservations.index'))
            ->post(route('reservations.store'), $this->reservationPayload([
                'facility_id' => $facility->id,
                'start_date' => '2026-06-14',
            ]));

        $this->assertRejected($response);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_today_past_time_is_rejected(): void
    {
        // Sekarang 2026-06-15 08:00; minta 07:00 hari ini.
        $this->makeUser();
        $facility = $this->makeFacility();

        $response = $this->actingAs($this->makeUser())->from(route('reservations.index'))
            ->post(route('reservations.store'), $this->reservationPayload([
                'facility_id' => $facility->id,
                'start_date' => '2026-06-15',
                'start_time' => '07:00',
                'end_time' => '08:00',
            ]));

        $this->assertRejected($response);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_today_future_time_is_accepted(): void
    {
        $facility = $this->makeFacility();

        $this->actingAs($this->makeUser())->post(route('reservations.store'), $this->reservationPayload([
            'facility_id' => $facility->id,
            'start_date' => '2026-06-15',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reservations', [
            'start_time' => '2026-06-15 09:00:00',
            'end_time' => '2026-06-15 10:00:00',
        ]);
    }

    public function test_future_date_is_persisted_on_the_intended_date(): void
    {
        $facility = $this->makeFacility();

        $this->actingAs($this->makeUser())->post(route('reservations.store'), $this->reservationPayload([
            'facility_id' => $facility->id,
            'start_date' => '2026-07-01',
            'start_time' => '14:00',
            'end_time' => '15:30',
        ]))->assertSessionHasNoErrors();

        $reservation = Reservation::firstOrFail();
        $this->assertSame('2026-07-01 14:00:00', $reservation->start_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-01 15:30:00', $reservation->end_time->format('Y-m-d H:i:s'));
    }

    public function test_different_start_and_end_date_is_rejected(): void
    {
        $facility = $this->makeFacility();

        $response = $this->actingAs($this->makeUser())->from(route('reservations.index'))
            ->post(route('reservations.store'), $this->reservationPayload([
                'facility_id' => $facility->id,
                'start_date' => '2026-06-16',
                'end_date' => '2026-06-17',
                'start_time' => '09:00',
                'end_time' => '10:00',
            ]));

        $this->assertRejected($response);
        $this->assertDatabaseCount('reservations', 0);
    }
}
