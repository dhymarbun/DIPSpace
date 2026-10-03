<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeFacility(string $nama = 'Ruang Uji'): Facility
    {
        return Facility::create([
            'nama' => $nama,
            'tipe' => 'Ruang Kelas',
            'lokasi' => 'Gedung A',
            'kapasitas' => 30,
            'status' => Facility::STATUS_AKTIF,
        ]);
    }

    private function makeReservation(Facility $facility, string $start, string $end, string $status = 'approved'): Reservation
    {
        return Reservation::create([
            'facility_id' => $facility->id,
            'user_id' => User::factory()->create()->id,
            'start_time' => $start,
            'end_time' => $end,
            'tujuan' => 'Reservasi uji',
            'status' => $status,
        ]);
    }

    public function test_schedule_endpoint_is_publicly_accessible(): void
    {
        // Tidak login, halaman depan harus tetap bisa memuat kalender.
        $this->getJson('/api/reservations/schedule?month=10&year=2026')
            ->assertOk();
    }

    public function test_it_returns_reservations_across_all_facilities_when_facility_id_is_omitted(): void
    {
        $a = $this->makeFacility();
        $b = $this->makeFacility();

        $this->makeReservation($a, '2026-10-05 08:00', '2026-10-05 10:00');
        $this->makeReservation($b, '2026-10-06 08:00', '2026-10-06 10:00');

        $response = $this->getJson('/api/reservations/schedule?month=10&year=2026')
            ->assertOk()
            ->assertJsonCount(2, 'reservations');

        $facilityIds = collect($response->json('reservations'))->pluck('facility_id');
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $facilityIds->all());

        // Nama fasilitas ikut dikembalikan agar badge bisa menampilkannya.
        $response->assertJsonStructure(['reservations' => [['facility_id', 'facility_nama', 'date', 'start_time', 'end_time', 'status']]]);
    }

    public function test_it_filters_by_facility_when_facility_id_is_given(): void
    {
        $a = $this->makeFacility();
        $b = $this->makeFacility();

        $this->makeReservation($a, '2026-10-05 08:00', '2026-10-05 10:00');
        $this->makeReservation($b, '2026-10-06 08:00', '2026-10-06 10:00');

        $this->getJson("/api/reservations/schedule?facility_id={$a->id}&month=10&year=2026")
            ->assertOk()
            ->assertJsonCount(1, 'reservations')
            ->assertJsonPath('reservations.0.facility_id', $a->id);
    }

    public function test_it_includes_reservations_that_straddle_the_month_boundary(): void
    {
        $f = $this->makeFacility();

        // Mulai September, berakhir Oktober: tetap harus tampil di kalender Oktober.
        $this->makeReservation($f, '2026-09-30 18:00', '2026-10-01 10:00');

        $this->getJson("/api/reservations/schedule?facility_id={$f->id}&month=10&year=2026")
            ->assertOk()
            ->assertJsonCount(1, 'reservations')
            ->assertJsonPath('reservations.0.date', '2026-10-01');
    }

    public function test_it_excludes_cancelled_and_rejected_reservations(): void
    {
        $f = $this->makeFacility();

        $this->makeReservation($f, '2026-10-05 08:00', '2026-10-05 10:00', 'approved');
        $this->makeReservation($f, '2026-10-06 08:00', '2026-10-06 10:00', 'pending');
        $this->makeReservation($f, '2026-10-07 08:00', '2026-10-07 10:00', 'cancelled');
        $this->makeReservation($f, '2026-10-08 08:00', '2026-10-08 10:00', 'rejected');

        $this->getJson("/api/reservations/schedule?facility_id={$f->id}&month=10&year=2026")
            ->assertOk()
            ->assertJsonCount(2, 'reservations');
    }

    public function test_it_rejects_an_unknown_facility_id(): void
    {
        $this->getJson('/api/reservations/schedule?facility_id=99999&month=10&year=2026')
            ->assertStatus(422)
            ->assertJsonValidationErrors('facility_id');
    }

    public function test_it_rejects_an_out_of_range_month(): void
    {
        $this->getJson('/api/reservations/schedule?month=13&year=2026')
            ->assertStatus(422)
            ->assertJsonValidationErrors('month');
    }
}