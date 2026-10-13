<?php

namespace Tests\Feature\Qa;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Base class untuk audit QA (Phase 2–4). Semua tes berjalan di database
 * in-memory yang terisolasi (RefreshDatabase) — tidak menyentuh data nyata.
 */
abstract class QaTestCase extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role = 'pengguna', bool $verified = true, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'is_verified' => $verified,
            'rejection_reason' => null,
        ], $overrides));
    }

    protected function makeFacility(array $overrides = []): Facility
    {
        return Facility::create(array_merge([
            'nama' => 'Ruang Uji '.uniqid(),
            'tipe' => 'Ruang Kelas',
            'lokasi' => 'Gedung QA Lantai 1',
            'kapasitas' => 30,
            'status' => Facility::STATUS_AKTIF,
        ], $overrides));
    }

    protected function makeReservation(array $overrides = []): Reservation
    {
        $facility = $overrides['facility_id'] ?? $this->makeFacility()->id;

        return Reservation::create(array_merge([
            'facility_id' => $facility,
            'user_id' => $this->makeUser()->id,
            'start_time' => '2026-06-16 09:00:00',
            'end_time' => '2026-06-16 10:00:00',
            'tujuan' => 'Reservasi uji QA',
            'status' => 'pending',
        ], $overrides));
    }

    /**
     * Payload reservasi valid (default: besok, 09:00–10:00).
     */
    protected function reservationPayload(array $overrides = []): array
    {
        return array_merge([
            'facility_id' => $this->makeFacility()->id,
            'start_date' => '2026-06-16',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'tujuan' => 'Kegiatan uji QA',
        ], $overrides);
    }

    protected function fakePng(string $name = 'foto.png'): UploadedFile
    {
        $bytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    /**
     * Hapus file yang ditulis ke public/ oleh fitur upload (menjaga isolasi repo).
     */
    protected function deletePublicFile(?string $relativePath): void
    {
        if ($relativePath && is_file(public_path($relativePath))) {
            @unlink(public_path($relativePath));
        }
    }
}
