<?php

namespace Tests\Feature\Qa;

use App\Models\Facility;
use App\Models\FacilityReport;
use Carbon\Carbon;

/**
 * US-08 / US-10 + Phase 4 — Antrian petugas, pembatalan darurat, dan batas akses.
 */
class StaffDashboardSecurityTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 08:00:00');
    }

    private function petugas(): \App\Models\User
    {
        return $this->makeUser('petugas');
    }

    public function test_dashboard_access_is_limited_to_petugas(): void
    {
        $this->get(route('petugas.dashboard'))->assertRedirect('/login');
        $this->actingAs($this->makeUser('pengguna'))->get(route('petugas.dashboard'))->assertForbidden();
        $this->actingAs($this->makeUser('admin'))->get(route('petugas.dashboard'))->assertForbidden();
    }

    public function test_pending_queue_lists_only_pending_reservations(): void
    {
        $pending = $this->makeFacility(['nama' => 'Fasilitas Pending QA']);
        $approved = $this->makeFacility(['nama' => 'Fasilitas Approved QA']);

        $this->makeReservation(['facility_id' => $pending->id, 'status' => 'pending', 'start_time' => '2026-06-16 09:00:00', 'end_time' => '2026-06-16 10:00:00']);
        $this->makeReservation(['facility_id' => $approved->id, 'status' => 'approved', 'start_time' => '2026-06-16 11:00:00', 'end_time' => '2026-06-16 12:00:00']);

        $this->actingAs($this->petugas())
            ->get(route('petugas.dashboard', ['reservation_status' => 'pending']))
            ->assertOk()
            ->assertSee('Fasilitas Pending QA')
            ->assertDontSee('Fasilitas Approved QA');
    }

    public function test_empty_queues_show_empty_states(): void
    {
        $this->actingAs($this->petugas())->get(route('petugas.dashboard'))
            ->assertOk()
            ->assertSee('Tidak ada reservasi.')
            ->assertSee('Tidak ada laporan.');
    }

    public function test_pending_report_appears_in_queue_but_resolved_does_not(): void
    {
        $facility = $this->makeFacility(['nama' => 'Fasilitas Laporan QA']);

        FacilityReport::create([
            'user_id' => $this->makeUser()->id,
            'facility_id' => $facility->id,
            'category' => 'kecil',
            'report_date' => '2026-06-10',
            'description' => 'LAPORAN-PENDING-QA',
            'status' => 'pending',
        ]);
        FacilityReport::create([
            'user_id' => $this->makeUser()->id,
            'facility_id' => $facility->id,
            'category' => 'kecil',
            'report_date' => '2026-06-10',
            'description' => 'LAPORAN-RESOLVED-QA',
            'status' => 'resolved',
        ]);

        $this->actingAs($this->petugas())
            ->get(route('petugas.dashboard', ['report_status' => 'pending']))
            ->assertOk()
            ->assertSee('LAPORAN-PENDING-QA')
            ->assertDontSee('LAPORAN-RESOLVED-QA');
    }

    public function test_emergency_cancel_persists_reason_and_releases_slot(): void
    {
        $facility = $this->makeFacility();
        $reservation = $this->makeReservation([
            'facility_id' => $facility->id,
            'status' => 'approved',
            'start_time' => '2026-06-20 09:00:00',
            'end_time' => '2026-06-20 10:00:00',
        ]);

        $this->actingAs($this->petugas())->from('/petugas/dashboard')
            ->post(route('petugas.cancelReservation'), [
                'id' => $reservation->id,
                'cancellation_reason' => 'Banjir di gedung',
            ])
            ->assertSessionHas('success');

        $reservation->refresh();
        $this->assertSame('cancelled', $reservation->status);
        $this->assertSame('Banjir di gedung', $reservation->cancellation_reason);

        $this->actingAs($this->petugas())
            ->get(route('petugas.dashboard', ['reservation_status' => 'cancelled']))
            ->assertSee('Banjir di gedung');
    }

    public function test_emergency_cancel_requires_nonempty_reason(): void
    {
        $reservation = $this->makeReservation(['status' => 'approved', 'start_time' => '2026-06-20 09:00:00', 'end_time' => '2026-06-20 10:00:00']);
        $petugas = $this->petugas();

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.cancelReservation'), ['id' => $reservation->id, 'cancellation_reason' => ''])
            ->assertSessionHasErrors('cancellation_reason');

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.cancelReservation'), ['id' => $reservation->id, 'cancellation_reason' => '     '])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertSame('approved', $reservation->fresh()->status);
    }

    public function test_emergency_cancel_rejects_overly_long_reason(): void
    {
        $reservation = $this->makeReservation(['status' => 'approved', 'start_time' => '2026-06-20 09:00:00', 'end_time' => '2026-06-20 10:00:00']);

        $this->actingAs($this->petugas())->from('/petugas/dashboard')
            ->post(route('petugas.cancelReservation'), [
                'id' => $reservation->id,
                'cancellation_reason' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors('cancellation_reason');

        $this->assertSame('approved', $reservation->fresh()->status);
    }

    public function test_emergency_cancel_only_applies_to_approved_future(): void
    {
        $petugas = $this->petugas();
        $pending = $this->makeReservation(['status' => 'pending']);
        $started = $this->makeReservation([
            'status' => 'approved',
            'start_time' => '2026-06-15 07:00:00',
            'end_time' => '2026-06-15 09:00:00',
        ]);

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.cancelReservation'), ['id' => $pending->id, 'cancellation_reason' => 'Alasan'])
            ->assertSessionHas('error');

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.cancelReservation'), ['id' => $started->id, 'cancellation_reason' => 'Alasan'])
            ->assertSessionHas('error');

        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertSame('approved', $started->fresh()->status);
    }

    public function test_emergency_cancel_rejects_unknown_id(): void
    {
        $this->actingAs($this->petugas())->from('/petugas/dashboard')
            ->post(route('petugas.cancelReservation'), ['id' => 999999, 'cancellation_reason' => 'Alasan'])
            ->assertSessionHasErrors('id');
    }

    public function test_non_staff_cannot_cancel_reservations(): void
    {
        $reservation = $this->makeReservation(['status' => 'approved', 'start_time' => '2026-06-20 09:00:00', 'end_time' => '2026-06-20 10:00:00']);

        $this->actingAs($this->makeUser('pengguna'))->post(route('petugas.cancelReservation'), [
            'id' => $reservation->id,
            'cancellation_reason' => 'Percobaan tidak sah',
        ])->assertForbidden();

        $this->assertSame('approved', $reservation->fresh()->status);
    }

    public function test_petugas_cannot_access_admin_area(): void
    {
        $this->actingAs($this->petugas())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->petugas())->get(route('admin.users.all'))->assertForbidden();
    }

    public function test_legacy_dashboard_route_redirects_each_role(): void
    {
        // Guard BUG-12: setelah menghapus controller/view lama, /dashboard tetap
        // mengarahkan sesuai peran. Cek tamu dilakukan lebih dulu sebelum actingAs.
        $this->get('/dashboard')->assertRedirect('/login');

        $this->actingAs($this->makeUser('admin'))->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($this->makeUser('petugas'))->get('/dashboard')
            ->assertRedirect(route('petugas.dashboard'));

        $this->actingAs($this->makeUser('pengguna'))->get('/dashboard')
            ->assertRedirect(route('home'));
    }
}
