<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFacilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_verified' => true,
        ]);
    }

    /**
     * UploadedFile gambar PNG 1x1 yang valid (GD tidak tersedia di lingkungan uji).
     */
    private function fakePng(string $name = 'foto.png'): UploadedFile
    {
        $bytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Ruang Rapat Baru',
            'tipe' => 'Ruang Rapat',
            'lokasi' => 'Gedung B Lantai 2',
            'kapasitas' => 20,
        ], $overrides);
    }

    private function makeFacility(array $overrides = []): Facility
    {
        return Facility::create(array_merge([
            'nama' => 'Aula Lama',
            'tipe' => 'Aula',
            'lokasi' => 'Gedung A',
            'kapasitas' => 100,
            'status' => Facility::STATUS_AKTIF,
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/facilities')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_manage_facilities(): void
    {
        $user = User::factory()->create([
            'role' => 'pengguna',
            'is_verified' => true,
        ]);

        $this->actingAs($user)->get('/admin/facilities')->assertForbidden();
    }

    public function test_admin_can_create_facility_with_photo_and_description(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post('/admin/facilities', $this->validPayload([
                'deskripsi' => 'Ruang rapat baru yang nyaman.',
                'foto' => $this->fakePng('rapat.png'),
            ]))
            ->assertRedirect(route('admin.facilities.index'));

        $facility = Facility::where('nama', 'Ruang Rapat Baru')->firstOrFail();

        $this->assertSame('Ruang rapat baru yang nyaman.', $facility->deskripsi);
        $this->assertNotNull($facility->foto);
        Storage::disk('public')->assertExists($facility->foto);
    }

    public function test_admin_can_update_facility_photo_and_description(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('facilities/lama.png', 'old-content');

        $facility = $this->makeFacility([
            'foto' => 'facilities/lama.png',
            'deskripsi' => 'Deskripsi lama',
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/facilities/{$facility->id}", $this->validPayload([
                'nama' => 'Aula Lama',
                'tipe' => 'Aula',
                'lokasi' => 'Gedung A',
                'kapasitas' => 100,
                'status' => Facility::STATUS_AKTIF,
                'deskripsi' => 'Deskripsi baru',
                'foto' => $this->fakePng('baru.png'),
            ]))
            ->assertRedirect(route('admin.facilities.index'));

        $facility->refresh();

        $this->assertSame('Deskripsi baru', $facility->deskripsi);
        $this->assertNotSame('facilities/lama.png', $facility->foto);
        Storage::disk('public')->assertMissing('facilities/lama.png');
        Storage::disk('public')->assertExists($facility->foto);
    }

    public function test_admin_can_remove_facility_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('facilities/lama.png', 'old-content');

        $facility = $this->makeFacility(['foto' => 'facilities/lama.png']);

        $this->actingAs($this->admin())
            ->put("/admin/facilities/{$facility->id}", $this->validPayload([
                'nama' => 'Aula Lama',
                'tipe' => 'Aula',
                'lokasi' => 'Gedung A',
                'kapasitas' => 100,
                'status' => Facility::STATUS_AKTIF,
                'hapus_foto' => '1',
            ]))
            ->assertRedirect(route('admin.facilities.index'));

        $facility->refresh();

        $this->assertNull($facility->foto);
        Storage::disk('public')->assertMissing('facilities/lama.png');
    }

    public function test_photo_must_be_a_valid_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post('/admin/facilities', $this->validPayload([
                'foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('foto');

        $this->assertDatabaseCount('facilities', 0);
    }

    public function test_description_has_maximum_length(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post('/admin/facilities', $this->validPayload([
                'deskripsi' => str_repeat('a', 2001),
            ]))
            ->assertSessionHasErrors('deskripsi');

        $this->assertDatabaseCount('facilities', 0);
    }

    public function test_edit_page_shows_existing_photo_and_fields(): void
    {
        Storage::fake('public');

        $facility = $this->makeFacility([
            'foto' => 'facilities/lama.png',
            'deskripsi' => 'Deskripsi tersimpan',
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/facilities/{$facility->id}/edit")
            ->assertOk()
            ->assertSee('name="deskripsi"', false)
            ->assertSee('Deskripsi tersimpan')
            ->assertSee('name="foto"', false)
            ->assertSee('name="hapus_foto"', false)
            ->assertSee('facilities/lama.png');
    }
}
