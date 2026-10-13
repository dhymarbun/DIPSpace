<?php

namespace Tests\Feature\Qa;

use App\Models\Reservation;
use Carbon\Carbon;

/**
 * US-03 — Pengajuan reservasi oleh pengguna terautentikasi.
 */
class ReservationSubmissionTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 08:00:00');
    }

    public function test_valid_submission_persists_all_fields_with_pending_status(): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility();

        $response = $this->actingAs($user)->post(route('reservations.store'), [
            'facility_id' => $facility->id,
            'start_date' => '2026-06-16',
            'start_time' => '13:00',
            'end_time' => '14:30',
            'tujuan' => 'Rapat organisasi mahasiswa',
        ]);

        $response->assertRedirect(route('reservations.index'));

        $reservation = Reservation::firstOrFail();
        $this->assertSame($facility->id, $reservation->facility_id);
        $this->assertSame($user->id, $reservation->user_id);
        $this->assertSame('2026-06-16 13:00:00', $reservation->start_time->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-16 14:30:00', $reservation->end_time->format('Y-m-d H:i:s'));
        $this->assertSame('Rapat organisasi mahasiswa', $reservation->tujuan);
        $this->assertSame('pending', $reservation->status);
        $this->assertNotNull($reservation->created_at);
    }

    public function test_reservation_survives_page_reload_and_is_visible_to_owner(): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility(['nama' => 'Ruang Sidang QA']);

        $this->actingAs($user)->post(route('reservations.store'), [
            'facility_id' => $facility->id,
            'start_date' => '2026-06-16',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'tujuan' => 'Seminar internal QA',
        ]);

        $this->actingAs($user)->get('/reservations')
            ->assertOk()
            ->assertSee('Ruang Sidang QA')
            ->assertSee('Seminar internal QA');
    }

    public function test_mass_assignment_of_status_and_owner_is_ignored(): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility();

        $this->actingAs($user)->post(route('reservations.store'), [
            'facility_id' => $facility->id,
            'start_date' => '2026-06-16',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'tujuan' => 'Uji mass assignment',
            'status' => 'approved',
            'user_id' => 999999,
            'role' => 'admin',
            'is_verified' => 1,
        ]);

        $reservation = Reservation::firstOrFail();
        $this->assertSame('pending', $reservation->status);
        $this->assertSame($user->id, $reservation->user_id);
    }

    public function test_facility_under_maintenance_cannot_be_reserved(): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility(['status' => 'dalam_perbaikan']);

        $response = $this->actingAs($user)->from(route('reservations.index'))
            ->post(route('reservations.store'), $this->reservationPayload([
                'facility_id' => $facility->id,
            ]));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_double_submit_does_not_create_duplicate(): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility();
        $payload = $this->reservationPayload(['facility_id' => $facility->id]);

        $this->actingAs($user)->post(route('reservations.store'), $payload);
        $this->actingAs($user)->from(route('reservations.index'))->post(route('reservations.store'), $payload);

        $this->assertSame(1, Reservation::where('facility_id', $facility->id)->count());
    }

    public function test_user_cannot_see_another_users_reservations(): void
    {
        $facility = $this->makeFacility();
        $other = $this->makeUser();
        $this->makeReservation([
            'facility_id' => $facility->id,
            'user_id' => $other->id,
            'tujuan' => 'KEPERLUAN-ORANG-LAIN-QA',
            'status' => 'pending',
        ]);

        $me = $this->makeUser();
        $this->actingAs($me)->get('/reservations')
            ->assertOk()
            ->assertDontSee('KEPERLUAN-ORANG-LAIN-QA');
    }
}
