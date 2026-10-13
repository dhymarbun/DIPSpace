<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilityDetailTest extends TestCase
{
    use RefreshDatabase;

    private function makeFacility(array $overrides = []): Facility
    {
        return Facility::create(array_merge([
            'nama' => 'Ruang Uji',
            'tipe' => 'Ruang Kelas',
            'lokasi' => 'Gedung A',
            'kapasitas' => 30,
            'status' => Facility::STATUS_AKTIF,
        ], $overrides));
    }

    public function test_detail_page_is_publicly_accessible_and_shows_facility_data(): void
    {
        $facility = $this->makeFacility([
            'nama' => 'Aula Serbaguna Cendekia',
            'tipe' => 'Aula',
            'lokasi' => 'Gedung Rektorat Lantai 1',
            'kapasitas' => 300,
        ]);

        $this->get("/facilities/{$facility->id}")
            ->assertOk()
            ->assertSee('Detail Fasilitas')
            ->assertSee('Aula Serbaguna Cendekia')
            ->assertSee('Gedung Rektorat Lantai 1')
            ->assertSee('300 orang')
            ->assertSee('Sarana pendukung');
    }

    public function test_active_facility_offers_reservation_action(): void
    {
        $facility = $this->makeFacility(['status' => Facility::STATUS_AKTIF]);

        $this->get("/facilities/{$facility->id}")
            ->assertOk()
            ->assertSee('Fasilitas tersedia untuk reservasi')
            ->assertSee('Ajukan Reservasi')
            ->assertSee(route('reservations.index', ['facility' => $facility->id]), false)
            ->assertDontSee('Reservasi Tidak Tersedia');
    }

    public function test_facility_under_repair_cannot_be_reserved(): void
    {
        $facility = $this->makeFacility(['status' => Facility::STATUS_DALAM_PERBAIKAN]);

        $this->get("/facilities/{$facility->id}")
            ->assertOk()
            ->assertSee('Fasilitas sedang dalam perbaikan')
            ->assertSee('Reservasi Tidak Tersedia')
            ->assertSee('Laporkan Kerusakan')
            ->assertDontSee('Ajukan Reservasi');
    }

    public function test_unknown_facility_returns_not_found(): void
    {
        $this->get('/facilities/99999')->assertNotFound();
    }

    public function test_detail_page_uses_custom_description_when_set(): void
    {
        $facility = $this->makeFacility([
            'tipe' => 'Aula',
            'deskripsi' => 'Deskripsi khusus yang diisi admin untuk fasilitas ini.',
        ]);

        $this->get("/facilities/{$facility->id}")
            ->assertOk()
            ->assertSee('Deskripsi khusus yang diisi admin untuk fasilitas ini.');
    }

    public function test_detail_page_falls_back_to_type_description_when_empty(): void
    {
        $facility = $this->makeFacility([
            'tipe' => 'Aula',
            'deskripsi' => null,
        ]);

        // Deskripsi bawaan untuk tipe Aula (lihat FacilityController::profileFor).
        $this->get("/facilities/{$facility->id}")
            ->assertOk()
            ->assertSee('Aula serbaguna');
    }

    public function test_detail_page_shows_custom_photo_instead_of_illustration(): void
    {
        $facility = $this->makeFacility(['foto' => 'facilities/aula.jpg']);

        $this->get("/facilities/{$facility->id}")
            ->assertOk()
            ->assertSee('class="detail-photo"', false)
            ->assertSee('facilities/aula.jpg')
            ->assertDontSee('Ilustrasi fasilitas');
    }

    public function test_detail_page_keeps_illustration_when_no_photo(): void
    {
        $facility = $this->makeFacility(['foto' => null]);

        $this->get("/facilities/{$facility->id}")
            ->assertOk()
            ->assertSee('Ilustrasi fasilitas')
            ->assertDontSee('class="detail-photo"', false);
    }

    public function test_staff_roles_get_disabled_reservation_actions(): void
    {
        $facility = $this->makeFacility(['status' => Facility::STATUS_AKTIF]);

        foreach (['admin', 'petugas'] as $role) {
            $staff = User::factory()->create([
                'role' => $role,
                'is_verified' => true,
            ]);

            $this->actingAs($staff)->get("/facilities/{$facility->id}")
                ->assertOk()
                ->assertSee('Ajukan Reservasi')
                ->assertSee('Laporkan Kerusakan')
                ->assertSee('hanya tersedia untuk akun pengguna')
                ->assertDontSee(route('reservations.index', ['facility' => $facility->id]), false)
                ->assertDontSee(route('reports.index', ['facility' => $facility->id]), false);
        }
    }

    public function test_pengguna_still_gets_active_reservation_actions(): void
    {
        $facility = $this->makeFacility(['status' => Facility::STATUS_AKTIF]);
        $user = User::factory()->create([
            'role' => 'pengguna',
            'is_verified' => true,
        ]);

        $this->actingAs($user)->get("/facilities/{$facility->id}")
            ->assertOk()
            ->assertSee(route('reservations.index', ['facility' => $facility->id]), false)
            ->assertSee(route('reports.index', ['facility' => $facility->id]), false)
            ->assertDontSee('hanya tersedia untuk akun pengguna');
    }
}
