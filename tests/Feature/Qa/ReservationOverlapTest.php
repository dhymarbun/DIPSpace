<?php

namespace Tests\Feature\Qa;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * US-09 + PHASE 3-D — Pencegahan bentrok jadwal & logika interval.
 * Ini aturan bisnis KRITIS. Interval diperlakukan half-open [start, end).
 */
class ReservationOverlapTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 08:00:00');
    }

    private function petugas(): User
    {
        return $this->makeUser('petugas');
    }

    private function approve(User $petugas, Reservation $reservation)
    {
        return $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.processReservation'), [
                'id' => $reservation->id,
                'action' => 'approve',
            ]);
    }

    // ---------- Penyimpanan (US-03) vs jadwal approved ----------

    public function test_store_blocks_overlap_with_approved_reservation(): void
    {
        $facility = $this->makeFacility();
        $this->makeReservation([
            'facility_id' => $facility->id,
            'start_time' => '2026-06-16 09:00:00',
            'end_time' => '2026-06-16 10:00:00',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->makeUser())->from(route('reservations.index'))
            ->post(route('reservations.store'), $this->reservationPayload([
                'facility_id' => $facility->id,
                'start_time' => '09:30',
                'end_time' => '10:30',
            ]));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_store_allows_adjacent_reservation_to_approved(): void
    {
        $facility = $this->makeFacility();
        $this->makeReservation([
            'facility_id' => $facility->id,
            'start_time' => '2026-06-16 09:00:00',
            'end_time' => '2026-06-16 09:30:00',
            'status' => 'approved',
        ]);

        $user = $this->makeUser();
        $this->actingAs($user)->post(route('reservations.store'), $this->reservationPayload([
            'facility_id' => $facility->id,
            'start_time' => '09:30',
            'end_time' => '10:00',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reservations', 2);
    }

    public function test_store_blocks_own_pending_overlap(): void
    {
        $facility = $this->makeFacility();
        $user = $this->makeUser();
        $this->makeReservation([
            'facility_id' => $facility->id,
            'user_id' => $user->id,
            'start_time' => '2026-06-16 09:00:00',
            'end_time' => '2026-06-16 10:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->from(route('reservations.index'))
            ->post(route('reservations.store'), $this->reservationPayload([
                'facility_id' => $facility->id,
                'start_time' => '09:30',
                'end_time' => '10:30',
            ]));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_rejected_and_cancelled_reservations_do_not_block_store(): void
    {
        $facility = $this->makeFacility();

        foreach (['rejected', 'cancelled'] as $status) {
            $this->makeReservation([
                'facility_id' => $facility->id,
                'start_time' => '2026-06-16 09:00:00',
                'end_time' => '2026-06-16 10:00:00',
                'status' => $status,
            ]);
        }

        $this->actingAs($this->makeUser())->post(route('reservations.store'), $this->reservationPayload([
            'facility_id' => $facility->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reservations', [
            'facility_id' => $facility->id,
            'status' => 'pending',
        ]);
    }

    // ---------- Persetujuan petugas (US-09) ----------

    /** Pengajuan yang bertumpuk tidak boleh sama-sama disetujui. */
    #[DataProvider('overlapPairs')]
    public function test_approval_rejects_conflicting_pairs(
        array $first,
        array $second,
        string $case
    ): void {
        $facility = $this->makeFacility();
        $petugas = $this->petugas();

        $a = $this->makeReservation([
            'facility_id' => $facility->id,
            'start_time' => '2026-06-16 '.$first[0].':00',
            'end_time' => '2026-06-16 '.$first[1].':00',
            'status' => 'pending',
        ]);
        $b = $this->makeReservation([
            'facility_id' => $facility->id,
            'start_time' => '2026-06-16 '.$second[0].':00',
            'end_time' => '2026-06-16 '.$second[1].':00',
            'status' => 'pending',
        ]);

        $this->approve($petugas, $a)->assertSessionHas('success');
        $this->assertSame('approved', $a->fresh()->status);

        $this->approve($petugas, $b)->assertSessionHas('error');
        $this->assertSame('pending', $b->fresh()->status, "Kasus [$case] seharusnya ditolak.");
    }

    public static function overlapPairs(): array
    {
        return [
            'tumpang penuh' => [['09:00', '10:00'], ['09:00', '10:00'], 'tumpang penuh'],
            'sebagian (09:30-10:30)' => [['09:00', '10:00'], ['09:30', '10:30'], 'sebagian'],
            'mengandung (09:30-10:00 di dalam 09:00-11:00)' => [['09:00', '11:00'], ['09:30', '10:00'], 'mengandung'],
        ];
    }

    #[DataProvider('compatiblePairs')]
    public function test_approval_allows_non_conflicting_pairs(
        array $first,
        array $second
    ): void {
        $facility = $this->makeFacility();
        $petugas = $this->petugas();

        $a = $this->makeReservation([
            'facility_id' => $facility->id,
            'start_time' => '2026-06-16 '.$first[0].':00',
            'end_time' => '2026-06-16 '.$first[1].':00',
            'status' => 'pending',
        ]);
        $b = $this->makeReservation([
            'facility_id' => $facility->id,
            'start_time' => '2026-06-16 '.$second[0].':00',
            'end_time' => '2026-06-16 '.$second[1].':00',
            'status' => 'pending',
        ]);

        $this->approve($petugas, $a)->assertSessionHas('success');
        $this->approve($petugas, $b)->assertSessionHas('success');

        $this->assertSame('approved', $a->fresh()->status);
        $this->assertSame('approved', $b->fresh()->status);
    }

    public static function compatiblePairs(): array
    {
        return [
            'bersambung 09:00-09:30 & 09:30-10:00' => [['09:00', '09:30'], ['09:30', '10:00']],
            'bersambung 10:00-10:30 setelah 09:00-10:00' => [['09:00', '10:00'], ['10:00', '10:30']],
            'terpisah 08:00-09:00 & 10:00-11:00' => [['08:00', '09:00'], ['10:00', '11:00']],
        ];
    }

    public function test_approval_allows_same_clock_on_different_facilities(): void
    {
        $petugas = $this->petugas();
        $f1 = $this->makeFacility();
        $f2 = $this->makeFacility();

        $a = $this->makeReservation(['facility_id' => $f1->id, 'start_time' => '2026-06-16 09:00:00', 'end_time' => '2026-06-16 10:00:00', 'status' => 'pending']);
        $b = $this->makeReservation(['facility_id' => $f2->id, 'start_time' => '2026-06-16 09:00:00', 'end_time' => '2026-06-16 10:00:00', 'status' => 'pending']);

        $this->approve($petugas, $a)->assertSessionHas('success');
        $this->approve($petugas, $b)->assertSessionHas('success');

        $this->assertSame('approved', $a->fresh()->status);
        $this->assertSame('approved', $b->fresh()->status);
    }

    public function test_approval_allows_same_clock_on_different_dates(): void
    {
        $petugas = $this->petugas();
        $facility = $this->makeFacility();

        $a = $this->makeReservation(['facility_id' => $facility->id, 'start_time' => '2026-06-16 09:00:00', 'end_time' => '2026-06-16 10:00:00', 'status' => 'pending']);
        $b = $this->makeReservation(['facility_id' => $facility->id, 'start_time' => '2026-06-17 09:00:00', 'end_time' => '2026-06-17 10:00:00', 'status' => 'pending']);

        $this->approve($petugas, $a)->assertSessionHas('success');
        $this->approve($petugas, $b)->assertSessionHas('success');

        $this->assertSame('approved', $a->fresh()->status);
        $this->assertSame('approved', $b->fresh()->status);
    }

    public function test_approval_is_not_blocked_by_cancelled_or_rejected_overlap(): void
    {
        $petugas = $this->petugas();
        $facility = $this->makeFacility();

        $this->makeReservation(['facility_id' => $facility->id, 'start_time' => '2026-06-16 09:00:00', 'end_time' => '2026-06-16 10:00:00', 'status' => 'cancelled']);
        $this->makeReservation(['facility_id' => $facility->id, 'start_time' => '2026-06-16 09:30:00', 'end_time' => '2026-06-16 10:30:00', 'status' => 'rejected']);

        $target = $this->makeReservation(['facility_id' => $facility->id, 'start_time' => '2026-06-16 09:00:00', 'end_time' => '2026-06-16 10:00:00', 'status' => 'pending']);

        $this->approve($petugas, $target)->assertSessionHas('success');
        $this->assertSame('approved', $target->fresh()->status);
    }

    public function test_repeated_approval_request_is_rejected(): void
    {
        $petugas = $this->petugas();
        $reservation = $this->makeReservation(['status' => 'pending']);

        $this->approve($petugas, $reservation)->assertSessionHas('success');
        $this->approve($petugas, $reservation)->assertSessionHas('error');

        $this->assertSame('approved', $reservation->fresh()->status);
    }

    public function test_approval_of_unknown_id_is_rejected(): void
    {
        $this->actingAs($this->petugas())->from('/petugas/dashboard')
            ->post(route('petugas.processReservation'), ['id' => 999999, 'action' => 'approve'])
            ->assertSessionHasErrors('id');
    }

    public function test_approval_is_blocked_when_facility_enters_maintenance(): void
    {
        $petugas = $this->petugas();
        $facility = $this->makeFacility();
        $reservation = $this->makeReservation(['facility_id' => $facility->id, 'status' => 'pending']);

        $facility->update(['status' => Facility::STATUS_DALAM_PERBAIKAN]);

        $this->approve($petugas, $reservation)->assertSessionHas('error');
        $this->assertSame('pending', $reservation->fresh()->status);
    }

    public function test_reject_action_sets_rejected_status(): void
    {
        $petugas = $this->petugas();
        $reservation = $this->makeReservation(['status' => 'pending']);

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.processReservation'), ['id' => $reservation->id, 'action' => 'reject'])
            ->assertSessionHas('success');

        $this->assertSame('rejected', $reservation->fresh()->status);
    }

    public function test_reject_is_an_atomic_conditional_update(): void
    {
        // Regresi BUG-03: penolakan memakai update bersyarat (hanya baris
        // berstatus pending) sehingga reservasi yang sudah diproses tidak
        // ikut berubah.
        $petugas = $this->petugas();
        $reservation = $this->makeReservation(['status' => 'approved']);

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.processReservation'), ['id' => $reservation->id, 'action' => 'reject'])
            ->assertSessionHas('error');

        $this->assertSame('approved', $reservation->fresh()->status);
    }

    public function test_approval_rechecks_state_after_locking(): void
    {
        // Regresi BUG-03: persetujuan dijalankan dalam transaksi dengan row
        // lock pada fasilitas dan membaca ulang status reservasi setelah lock.
        // Lingkungan test memakai SQLite yang tidak menegakkan lock, sehingga
        // yang diverifikasi di sini adalah invarian perilakunya.
        $petugas = $this->petugas();
        $facility = $this->makeFacility();
        $a = $this->makeReservation(['facility_id' => $facility->id, 'start_time' => '2026-06-16 09:00:00', 'end_time' => '2026-06-16 10:00:00', 'status' => 'pending']);
        $b = $this->makeReservation(['facility_id' => $facility->id, 'start_time' => '2026-06-16 09:30:00', 'end_time' => '2026-06-16 10:30:00', 'status' => 'pending']);

        // B sudah diproses (ditolak) -> permintaan approve berikutnya harus gagal.
        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.processReservation'), ['id' => $b->id, 'action' => 'reject'])
            ->assertSessionHas('success');

        $this->approve($petugas, $b)->assertSessionHas('error');
        $this->assertSame('rejected', $b->fresh()->status);

        // A tetap dapat disetujui tanpa terpengaruh.
        $this->approve($petugas, $a)->assertSessionHas('success');
        $this->assertSame('approved', $a->fresh()->status);
    }

    public function test_overlapping_scope_uses_half_open_intervals(): void
    {
        $facility = $this->makeFacility();
        $ref = $this->makeReservation([
            'facility_id' => $facility->id,
            'start_time' => '2026-06-16 09:00:00',
            'end_time' => '2026-06-16 10:00:00',
            'status' => 'approved',
        ]);

        // Bersambung: tidak boleh dianggap bentrok.
        $this->assertFalse(
            Reservation::overlapping($facility->id, '2026-06-16 10:00:00', '2026-06-16 10:30:00')->exists()
        );
        $this->assertFalse(
            Reservation::overlapping($facility->id, '2026-06-16 08:30:00', '2026-06-16 09:00:00')->exists()
        );

        // Bertumpuk: harus terdeteksi.
        $this->assertTrue(
            Reservation::overlapping($facility->id, '2026-06-16 09:30:00', '2026-06-16 10:30:00')->exists()
        );
        $this->assertTrue(
            Reservation::overlapping($facility->id, '2026-06-16 08:00:00', '2026-06-16 12:00:00')->exists()
        );

        // Fasilitas berbeda: tidak boleh terdeteksi.
        $other = $this->makeFacility();
        $this->assertFalse(
            Reservation::overlapping($other->id, '2026-06-16 09:00:00', '2026-06-16 10:00:00')->exists()
        );

        $this->assertNotNull($ref);
    }
}
