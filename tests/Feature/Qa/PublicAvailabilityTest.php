<?php

namespace Tests\Feature\Qa;

use Carbon\Carbon;

/**
 * US-01 — Daftar fasilitas publik & ketersediaan.
 * Publik boleh melihat fasilitas & jadwal, TANPA data pemohon/tujuan.
 */
class PublicAvailabilityTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 08:00:00');
    }

    public function test_home_lists_facilities_and_statuses_without_login(): void
    {
        $this->makeFacility(['nama' => 'Aula QA', 'lokasi' => 'Gedung QA', 'status' => 'aktif']);
        $this->makeFacility(['nama' => 'Lab Rusak QA', 'lokasi' => 'Gedung QA', 'status' => 'dalam_perbaikan']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Aula QA')
            ->assertSee('Lab Rusak QA')
            ->assertSee('Aktif')
            ->assertSee('Dalam Perbaikan');
    }

    public function test_schedule_endpoint_is_public(): void
    {
        $this->getJson('/api/reservations/schedule?month=6&year=2026')->assertOk();
    }

    public function test_schedule_does_not_leak_applicant_details(): void
    {
        $facility = $this->makeFacility();
        $this->makeReservation([
            'facility_id' => $facility->id,
            'user_id' => $this->makeUser('pengguna', true, ['email' => 'rahasia-pemohon@qa.test'])->id,
            'start_time' => '2026-06-16 09:00:00',
            'end_time' => '2026-06-16 10:00:00',
            'tujuan' => 'TUJUAN-RAHASIA-QA',
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/reservations/schedule?month=6&year=2026&facility_id='.$facility->id);

        $response->assertOk()
            ->assertDontSee('rahasia-pemohon@qa.test')
            ->assertDontSee('TUJUAN-RAHASIA-QA')
            ->assertJsonStructure([
                'reservations' => [['facility_id', 'facility_nama', 'date', 'start_time', 'end_time', 'status']],
            ]);
    }

    public function test_schedule_reflects_approved_and_pending_but_not_cancelled_or_rejected(): void
    {
        $facility = $this->makeFacility();
        $base = [
            'facility_id' => $facility->id,
            'start_time' => '2026-06-16 09:00:00',
            'end_time' => '2026-06-16 10:00:00',
        ];

        $this->makeReservation($base + ['status' => 'approved']);
        $this->makeReservation($base + ['status' => 'pending', 'start_time' => '2026-06-16 10:00:00', 'end_time' => '2026-06-16 11:00:00']);
        $this->makeReservation($base + ['status' => 'cancelled', 'start_time' => '2026-06-16 11:00:00', 'end_time' => '2026-06-16 12:00:00']);
        $this->makeReservation($base + ['status' => 'rejected', 'start_time' => '2026-06-16 12:00:00', 'end_time' => '2026-06-16 13:00:00']);

        $data = $this->getJson('/api/reservations/schedule?month=6&year=2026&facility_id='.$facility->id)
            ->assertOk()
            ->json();

        $statuses = array_column($data['reservations'], 'status');
        $this->assertContains('approved', $statuses);
        $this->assertContains('pending', $statuses);
        $this->assertNotContains('cancelled', $statuses);
        $this->assertNotContains('rejected', $statuses);
    }

    public function test_home_page_does_not_leak_other_users_data(): void
    {
        $facility = $this->makeFacility();
        $this->makeReservation([
            'facility_id' => $facility->id,
            'user_id' => $this->makeUser('pengguna', true, ['email' => 'bocor@qa.test'])->id,
            'tujuan' => 'URUSAN-PRIBADI-QA',
            'status' => 'approved',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('bocor@qa.test')
            ->assertDontSee('URUSAN-PRIBADI-QA');
    }

    public function test_guest_cannot_reach_reservation_or_report_pages(): void
    {
        $this->get('/reservations')->assertRedirect('/login');
        $this->get('/reports')->assertRedirect('/login');
    }

    public function test_no_public_route_exposes_a_single_reservation_detail(): void
    {
        $reservation = $this->makeReservation(['status' => 'approved']);

        $this->get('/reservations/'.$reservation->id)->assertNotFound();
        $this->get('/reservasi/'.$reservation->id)->assertNotFound();
    }
}
