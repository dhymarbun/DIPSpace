<?php

namespace Tests\Feature\Qa;

use App\Models\Reservation;
use Carbon\Carbon;

/**
 * US-04 — Pengguna membatalkan reservasinya sendiri.
 * Aturan aplikasi (terverifikasi): hanya status pending/approved DAN
 * start_time masih di masa depan. Batas waktu batal tidak didefinisikan PDF.
 */
class ReservationCancellationTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 08:00:00');
    }

    public function test_user_can_cancel_own_pending_reservation(): void
    {
        $user = $this->makeUser();
        $reservation = $this->makeReservation(['user_id' => $user->id, 'status' => 'pending']);

        $this->actingAs($user)->post(route('reservations.cancel'), ['id' => $reservation->id])
            ->assertRedirect(route('reservations.index'))
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    public function test_user_can_cancel_own_approved_future_reservation(): void
    {
        $user = $this->makeUser();
        $reservation = $this->makeReservation([
            'user_id' => $user->id,
            'status' => 'approved',
            'start_time' => '2026-06-20 09:00:00',
            'end_time' => '2026-06-20 10:00:00',
        ]);

        $this->actingAs($user)->post(route('reservations.cancel'), ['id' => $reservation->id]);

        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    public function test_user_cannot_cancel_another_users_reservation_via_id_tampering(): void
    {
        $owner = $this->makeUser();
        $attacker = $this->makeUser();
        $reservation = $this->makeReservation(['user_id' => $owner->id, 'status' => 'pending']);

        $this->actingAs($attacker)->from(route('reservations.index'))
            ->post(route('reservations.cancel'), ['id' => $reservation->id])
            ->assertSessionHas('warning');

        $this->assertSame('pending', $reservation->fresh()->status);
    }

    public function test_user_cannot_cancel_reservation_that_has_already_started(): void
    {
        $user = $this->makeUser();
        $reservation = $this->makeReservation([
            'user_id' => $user->id,
            'status' => 'approved',
            'start_time' => '2026-06-15 07:00:00', // sudah mulai (sekarang 08:00)
            'end_time' => '2026-06-15 09:00:00',
        ]);

        $this->actingAs($user)->from(route('reservations.index'))
            ->post(route('reservations.cancel'), ['id' => $reservation->id])
            ->assertSessionHas('warning');

        $this->assertSame('approved', $reservation->fresh()->status);
    }

    public function test_user_cannot_cancel_already_cancelled_reservation(): void
    {
        $user = $this->makeUser();
        $reservation = $this->makeReservation(['user_id' => $user->id, 'status' => 'cancelled']);

        $this->actingAs($user)->from(route('reservations.index'))
            ->post(route('reservations.cancel'), ['id' => $reservation->id])
            ->assertSessionHas('warning');

        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    public function test_guest_cannot_post_cancellation(): void
    {
        $reservation = $this->makeReservation(['status' => 'pending']);

        $this->post(route('reservations.cancel'), ['id' => $reservation->id])
            ->assertRedirect('/login');

        $this->assertSame('pending', $reservation->fresh()->status);
    }

    public function test_cancellation_releases_the_slot_for_other_users(): void
    {
        $facility = $this->makeFacility();
        $owner = $this->makeUser();
        $reservation = $this->makeReservation([
            'facility_id' => $facility->id,
            'user_id' => $owner->id,
            'status' => 'approved',
            'start_time' => '2026-06-16 09:00:00',
            'end_time' => '2026-06-16 10:00:00',
        ]);

        $this->actingAs($owner)->post(route('reservations.cancel'), ['id' => $reservation->id]);
        $this->assertSame('cancelled', $reservation->fresh()->status);

        // Pengguna lain kini dapat memesan slot yang sama.
        $this->actingAs($this->makeUser())->post(route('reservations.store'), $this->reservationPayload([
            'facility_id' => $facility->id,
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, Reservation::where('status', 'pending')->count());
    }

    public function test_cancelled_reservation_disappears_from_public_schedule(): void
    {
        $facility = $this->makeFacility();
        $user = $this->makeUser();
        $reservation = $this->makeReservation([
            'facility_id' => $facility->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $this->actingAs($user)->post(route('reservations.cancel'), ['id' => $reservation->id]);

        $data = $this->getJson('/api/reservations/schedule?month=6&year=2026&facility_id='.$facility->id)->json();
        $this->assertSame([], $data['reservations']);
    }

    public function test_unverified_user_cannot_reach_cancellation_route(): void
    {
        $user = $this->makeUser('pengguna', false);
        $reservation = $this->makeReservation(['user_id' => $user->id, 'status' => 'pending']);

        $this->actingAs($user)->post(route('reservations.cancel'), ['id' => $reservation->id])
            ->assertRedirect(route('verification.notice'));

        $this->assertSame('pending', $reservation->fresh()->status);
    }
}
