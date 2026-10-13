<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PetugasDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas', 'is_verified' => true]);
    }

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
            'user_id' => User::factory()->create(['role' => 'pengguna', 'is_verified' => true])->id,
            'start_time' => $start,
            'end_time' => $end,
            'tujuan' => 'Reservasi uji',
            'status' => $status,
        ]);
    }

    public function test_approved_tab_excludes_finished_reservations(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');
        $facility = $this->makeFacility();

        $this->makeReservation($facility, '2026-10-07 07:30', '2026-10-07 20:00'); // sudah selesai
        $this->makeReservation($facility, '2026-10-14 08:00', '2026-10-14 10:00'); // akan datang

        $this->actingAs($this->petugas())
            ->get('/petugas/dashboard?reservation_status=approved')
            ->assertOk()
            ->assertSee('2026-10-14 08:00:00')
            ->assertDontSee('2026-10-07 07:30:00');
    }

    public function test_selesai_tab_lists_only_finished_reservations(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');
        $facility = $this->makeFacility();

        $this->makeReservation($facility, '2026-10-07 07:30', '2026-10-07 20:00'); // sudah selesai
        $this->makeReservation($facility, '2026-10-14 08:00', '2026-10-14 10:00'); // akan datang
        $this->makeReservation($facility, '2026-10-15 08:00', '2026-10-15 10:00', 'pending');

        $this->actingAs($this->petugas())
            ->get('/petugas/dashboard?reservation_status=selesai')
            ->assertOk()
            ->assertSee('2026-10-07 07:30:00')
            ->assertDontSee('2026-10-14 08:00:00')
            ->assertDontSee('2026-10-15 08:00:00');
    }

    public function test_finished_reservation_gets_finished_badge(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');
        $facility = $this->makeFacility();
        $this->makeReservation($facility, '2026-10-07 07:30', '2026-10-07 20:00');

        $this->actingAs($this->petugas())
            ->get('/petugas/dashboard?reservation_status=selesai')
            ->assertOk()
            ->assertSee('badge-finished', false);
    }

    public function test_ongoing_reservation_gets_ongoing_badge_in_approved_tab(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');
        $facility = $this->makeFacility();
        $this->makeReservation($facility, '2026-10-13 08:00', '2026-10-13 17:00');

        $this->actingAs($this->petugas())
            ->get('/petugas/dashboard?reservation_status=approved')
            ->assertOk()
            ->assertSee('badge-ongoing', false)
            ->assertSee('Berlangsung');
    }

    public function test_cancel_button_hidden_for_started_or_finished_reservations(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');
        $facility = $this->makeFacility();

        $this->makeReservation($facility, '2026-10-13 08:00', '2026-10-13 17:00'); // sedang berlangsung
        $this->makeReservation($facility, '2026-10-07 07:30', '2026-10-07 20:00'); // sudah selesai

        $this->actingAs($this->petugas())
            ->get('/petugas/dashboard?reservation_status=approved')
            ->assertOk()
            ->assertDontSee('Batalkan');

        $this->actingAs($this->petugas())
            ->get('/petugas/dashboard?reservation_status=selesai')
            ->assertOk()
            ->assertDontSee('Batalkan');
    }

    public function test_cancel_button_shown_for_upcoming_approved_reservation(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');
        $facility = $this->makeFacility();
        $upcoming = $this->makeReservation($facility, '2026-10-14 08:00', '2026-10-14 10:00');

        $this->actingAs($this->petugas())
            ->get('/petugas/dashboard?reservation_status=approved')
            ->assertOk()
            ->assertSee('Batalkan')
            ->assertSee('cancel-res-'.$upcoming->id, false);
    }

    public function test_cancelling_a_started_reservation_is_still_rejected(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');
        $facility = $this->makeFacility();
        $reservation = $this->makeReservation($facility, '2026-10-13 08:00', '2026-10-13 17:00');

        $this->actingAs($this->petugas())
            ->from('/petugas/dashboard')
            ->post(route('petugas.cancelReservation'), [
                'id' => $reservation->id,
                'cancellation_reason' => 'Batal',
            ])
            ->assertRedirect('/petugas/dashboard')
            ->assertSessionHas('error');

        $this->assertSame('approved', $reservation->fresh()->status);
    }
}
