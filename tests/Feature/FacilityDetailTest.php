<?php

namespace Tests\Feature;

use App\Models\Facility;
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
}
