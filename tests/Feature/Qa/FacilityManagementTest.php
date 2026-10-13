<?php

namespace Tests\Feature\Qa;

use App\Models\Facility;
use App\Models\FacilityReport;
use App\Models\Reservation;

/**
 * US-16 — Admin mengelola fasilitas (tambah, ubah, nonaktifkan).
 */
class FacilityManagementTest extends QaTestCase
{
    private function admin(): \App\Models\User
    {
        return $this->makeUser('admin');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Fasilitas Baru QA',
            'tipe' => 'Ruang Kelas',
            'lokasi' => 'Gedung QA',
            'kapasitas' => 25,
        ], $overrides);
    }

    public function test_admin_can_create_an_active_facility(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.facilities.store'), $this->payload())
            ->assertRedirect(route('admin.facilities.index'));

        $facility = Facility::where('nama', 'Fasilitas Baru QA')->firstOrFail();
        $this->assertSame('aktif', $facility->status);
        $this->assertSame(25, $facility->kapasitas);
    }

    public function test_admin_can_edit_a_facility(): void
    {
        $facility = $this->makeFacility(['nama' => 'Nama Lama QA']);

        $this->actingAs($this->admin())
            ->put(route('admin.facilities.update', $facility->id), $this->payload([
                'nama' => 'Nama Baru QA',
                'lokasi' => 'Gedung Baru QA',
                'kapasitas' => 99,
                'status' => 'aktif',
            ]))
            ->assertRedirect(route('admin.facilities.index'));

        $facility->refresh();
        $this->assertSame('Nama Baru QA', $facility->nama);
        $this->assertSame('Gedung Baru QA', $facility->lokasi);
        $this->assertSame(99, $facility->kapasitas);
    }

    public function test_admin_can_deactivate_and_reactivate_a_facility(): void
    {
        $facility = $this->makeFacility(['status' => 'aktif']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.facilities.deactivate', $facility->id))
            ->assertSessionHas('success');
        $this->assertSame('dalam_perbaikan', $facility->fresh()->status);

        $this->actingAs($admin)->post(route('admin.facilities.activate', $facility->id))
            ->assertSessionHas('success');
        $this->assertSame('aktif', $facility->fresh()->status);
    }

    public function test_deactivation_preserves_historical_reservations_and_reports(): void
    {
        $facility = $this->makeFacility();
        $reservation = $this->makeReservation(['facility_id' => $facility->id, 'status' => 'approved']);
        $report = FacilityReport::create([
            'user_id' => $this->makeUser()->id,
            'facility_id' => $facility->id,
            'category' => 'kecil',
            'report_date' => '2026-06-01',
            'description' => 'Riwayat QA',
            'status' => 'resolved',
        ]);

        $this->actingAs($this->admin())->post(route('admin.facilities.deactivate', $facility->id));

        $this->assertDatabaseHas('facilities', ['id' => $facility->id]);
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
        $this->assertDatabaseHas('facility_reports', ['id' => $report->id]);
    }

    public function test_inactive_facility_cannot_be_newly_reserved(): void
    {
        $facility = $this->makeFacility(['status' => 'dalam_perbaikan']);

        $this->actingAs($this->makeUser())->from(route('reservations.index'))
            ->post(route('reservations.store'), $this->reservationPayload(['facility_id' => $facility->id]))
            ->assertSessionHas('error');

        $this->assertSame(0, Reservation::where('facility_id', $facility->id)->count());
    }

    public function test_invalid_facility_data_is_rejected(): void
    {
        $admin = $this->admin();

        foreach ([
            'nama kosong' => ['nama' => ''],
            'nama terlalu panjang' => ['nama' => str_repeat('a', 101)],
            'lokasi terlalu panjang' => ['lokasi' => str_repeat('b', 101)],
            'kapasitas nol' => ['kapasitas' => 0],
            'kapasitas negatif' => ['kapasitas' => -5],
            'kapasitas bukan angka' => ['kapasitas' => 'banyak'],
        ] as $override) {
            $this->actingAs($admin)->from(route('admin.facilities.create'))
                ->post(route('admin.facilities.store'), $this->payload($override))
                ->assertSessionHasErrors(array_key_first($override));
        }

        $this->assertDatabaseCount('facilities', 0);
    }

    public function test_duplicate_facility_is_rejected(): void
    {
        $this->makeFacility(['nama' => 'Duplikat QA', 'tipe' => 'Aula', 'lokasi' => 'Gedung QA']);

        $this->actingAs($this->admin())->from(route('admin.facilities.create'))
            ->post(route('admin.facilities.store'), $this->payload([
                'nama' => 'duplikat qa',
                'tipe' => 'aula',
                'lokasi' => 'gedung qa',
            ]))
            ->assertSessionHas('error');

        $this->assertSame(1, Facility::whereRaw('LOWER(nama) = ?', ['duplikat qa'])->count());
    }

    public function test_invalid_status_on_update_is_rejected(): void
    {
        $facility = $this->makeFacility();

        $this->actingAs($this->admin())->from(route('admin.facilities.edit', $facility->id))
            ->put(route('admin.facilities.update', $facility->id), $this->payload(['status' => 'dihapus']))
            ->assertSessionHasErrors('status');

        $this->assertSame('aktif', $facility->fresh()->status);
    }

    public function test_non_admin_roles_cannot_manage_facilities(): void
    {
        $facility = $this->makeFacility();

        foreach (['pengguna', 'petugas'] as $role) {
            $user = $this->makeUser($role);
            $this->actingAs($user)->get(route('admin.facilities.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.facilities.store'), $this->payload(['nama' => "X $role"]))->assertForbidden();
            $this->actingAs($user)->post(route('admin.facilities.deactivate', $facility->id))->assertForbidden();
        }

        $this->assertSame('aktif', $facility->fresh()->status);
    }
}
