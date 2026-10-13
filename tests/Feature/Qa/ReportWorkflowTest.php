<?php

namespace Tests\Feature\Qa;

use App\Models\Facility;
use App\Models\FacilityReport;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * US-06 / US-07 / US-11 / US-12 — Laporan kerusakan, status, dan alur perbaikan.
 */
class ReportWorkflowTest extends QaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 08:00:00');
    }

    private function reportPayload(array $overrides = []): array
    {
        return array_merge([
            'facility_id' => $this->makeFacility()->id,
            'category' => 'kecil',
            'report_date' => '2026-06-15',
            'description' => 'Kerusakan ringan pada kursi QA',
        ], $overrides);
    }

    public function test_valid_report_with_photo_is_saved(): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility();

        $this->actingAs($user)->post(route('reports.store'), $this->reportPayload([
            'facility_id' => $facility->id,
            'photo' => $this->fakePng('bukti.png'),
        ]))->assertRedirect(route('reports.index'));

        $report = FacilityReport::firstOrFail();
        $this->assertSame('pending', $report->status);
        $this->assertNotNull($report->photo_path);
        $this->assertFileExists(public_path($report->photo_path));

        $this->deletePublicFile($report->photo_path);
    }

    public function test_php_named_image_uploads_are_rejected(): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility();

        // Konten adalah PNG valid, tetapi nama file berbahaya harus ditolak.
        foreach (['evil.php', 'evil.PHP', 'evil.pHp', 'evil.phtml'] as $name) {
            $this->actingAs($user)->from(route('reports.index'))
                ->post(route('reports.store'), $this->reportPayload([
                    'facility_id' => $facility->id,
                    'photo' => $this->fakePng($name),
                ]))
                ->assertSessionHasErrors('photo');
        }

        $this->assertDatabaseCount('facility_reports', 0);
    }

    public function test_php_payload_uploaded_as_image_is_rejected(): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility();

        $php = UploadedFile::fake()->createWithContent('shell.php', "<?php echo 'pwned'; ?>");

        $response = $this->actingAs($user)->from(route('reports.index'))
            ->post(route('reports.store'), $this->reportPayload([
                'facility_id' => $facility->id,
                'photo' => $php,
            ]));

        $response->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('facility_reports', 0);
    }

    public function test_report_without_photo_is_allowed(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('reports.store'), $this->reportPayload())
            ->assertSessionHasNoErrors();

        $this->assertNull(FacilityReport::firstOrFail()->photo_path);
    }

    #[DataProvider('invalidReportPayloads')]
    public function test_invalid_report_payloads_are_rejected(string $field, $value): void
    {
        $user = $this->makeUser();
        $payload = $this->reportPayload();

        if ($value === '__MISSING__') {
            unset($payload[$field]);
        } else {
            $payload[$field] = $value;
        }

        $response = $this->actingAs($user)->from(route('reports.index'))
            ->post(route('reports.store'), $payload);

        $response->assertSessionHasErrors($field);
        $this->assertDatabaseCount('facility_reports', 0);
    }

    public static function invalidReportPayloads(): array
    {
        return [
            'kategori kosong' => ['category', '__MISSING__'],
            'kategori tidak valid' => ['category', 'super_parah'],
            'deskripsi kosong' => ['description', ''],
            'deskripsi hanya spasi' => ['description', '     '],
            'deskripsi terlalu panjang' => ['description', str_repeat('x', 2001)],
            'tanggal kosong' => ['report_date', '__MISSING__'],
            'tanggal masa depan' => ['report_date', '2026-07-01'],
            'tanggal malformed' => ['report_date', '2026-13-40'],
            'fasilitas tidak ada' => ['facility_id', 999999],
        ];
    }

    public function test_report_for_facility_under_repair_is_rejected(): void
    {
        $user = $this->makeUser();
        $facility = $this->makeFacility(['status' => 'dalam_perbaikan']);

        $response = $this->actingAs($user)->from(route('reports.index'))
            ->post(route('reports.store'), $this->reportPayload(['facility_id' => $facility->id]));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('facility_reports', 0);
    }

    public function test_user_sees_own_reports_only(): void
    {
        $facility = $this->makeFacility();
        $owner = $this->makeUser();
        FacilityReport::create([
            'user_id' => $owner->id,
            'facility_id' => $facility->id,
            'category' => 'parah',
            'report_date' => '2026-06-10',
            'description' => 'LAPORAN-PRIBADI-QA',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)->get('/reports')->assertOk()->assertSee('LAPORAN-PRIBADI-QA');
        $this->actingAs($this->makeUser())->get('/reports')->assertOk()->assertDontSee('LAPORAN-PRIBADI-QA');
    }

    public function test_staff_status_change_is_visible_to_the_owner(): void
    {
        $owner = $this->makeUser();
        $facility = $this->makeFacility();
        $petugas = $this->makeUser('petugas');
        $report = FacilityReport::create([
            'user_id' => $owner->id,
            'facility_id' => $facility->id,
            'category' => 'sedang',
            'report_date' => '2026-06-10',
            'description' => 'Deskripsi QA status',
            'status' => 'pending',
        ]);

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.processReport'), ['id' => $report->id, 'action' => 'start_report'])
            ->assertSessionHas('success');
        $this->assertSame('in_progress', $report->fresh()->status);
        $this->actingAs($owner)->get('/reports')->assertSee('Diproses');

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.updateReportStatus'), [
                'id' => $report->id,
                'status' => 'resolved',
                'resolution_notes' => 'Sudah diperbaiki oleh teknisi QA',
            ])
            ->assertSessionHas('success');

        $this->assertSame('resolved', $report->fresh()->status);
        $this->actingAs($owner)->get('/reports')
            ->assertSee('Selesai')
            ->assertSee('Sudah diperbaiki oleh teknisi QA');
    }

    public function test_closing_report_requires_resolution_notes(): void
    {
        $petugas = $this->makeUser('petugas');
        $report = FacilityReport::create([
            'user_id' => $this->makeUser()->id,
            'facility_id' => $this->makeFacility()->id,
            'category' => 'kecil',
            'report_date' => '2026-06-10',
            'description' => 'Deskripsi QA',
            'status' => 'in_progress',
        ]);

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.updateReportStatus'), ['id' => $report->id, 'status' => 'resolved'])
            ->assertSessionHas('error');

        $this->assertSame('in_progress', $report->fresh()->status);
    }

    public function test_reject_via_queue_does_not_require_resolution_notes(): void
    {
        // Dokumentasi inkonsistensi: menolak dari antrian tidak mewajibkan catatan.
        $petugas = $this->makeUser('petugas');
        $report = FacilityReport::create([
            'user_id' => $this->makeUser()->id,
            'facility_id' => $this->makeFacility()->id,
            'category' => 'kecil',
            'report_date' => '2026-06-10',
            'description' => 'Deskripsi QA',
            'status' => 'pending',
        ]);

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.processReport'), ['id' => $report->id, 'action' => 'reject_report'])
            ->assertSessionHas('success');

        $this->assertSame('rejected', $report->fresh()->status);
        $this->assertNull($report->fresh()->resolution_notes, 'Menolak dari antrian tidak menyimpan catatan resolusi.');
    }

    public function test_resolved_report_can_currently_be_reopened(): void
    {
        // Dokumentasi: transisi belum dibatasi (resolved -> pending dapat terjadi).
        $petugas = $this->makeUser('petugas');
        $report = FacilityReport::create([
            'user_id' => $this->makeUser()->id,
            'facility_id' => $this->makeFacility()->id,
            'category' => 'kecil',
            'report_date' => '2026-06-10',
            'description' => 'Deskripsi QA',
            'status' => 'resolved',
        ]);

        $this->actingAs($petugas)->post(route('petugas.updateReportStatus'), [
            'id' => $report->id,
            'status' => 'pending',
        ]);

        $this->assertSame('pending', $report->fresh()->status);
    }

    public function test_non_staff_cannot_change_report_status(): void
    {
        $report = FacilityReport::create([
            'user_id' => $this->makeUser()->id,
            'facility_id' => $this->makeFacility()->id,
            'category' => 'kecil',
            'report_date' => '2026-06-10',
            'description' => 'Deskripsi QA',
            'status' => 'pending',
        ]);

        $this->actingAs($this->makeUser())->post(route('petugas.updateReportStatus'), [
            'id' => $report->id,
            'status' => 'resolved',
            'resolution_notes' => 'oleh pengguna biasa',
        ])->assertForbidden();

        $this->assertSame('pending', $report->fresh()->status);
    }

    public function test_maintenance_lifecycle(): void
    {
        $petugas = $this->makeUser('petugas');
        $facility = $this->makeFacility();

        // Tanpa laporan in_progress tidak boleh menandai perbaikan.
        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.markFacilityRepair', $facility->id))
            ->assertSessionHas('error');
        $this->assertSame('aktif', $facility->fresh()->status);

        $report = FacilityReport::create([
            'user_id' => $this->makeUser()->id,
            'facility_id' => $facility->id,
            'category' => 'parah',
            'report_date' => '2026-06-10',
            'description' => 'Rusak berat QA',
            'status' => 'in_progress',
        ]);

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.markFacilityRepair', $facility->id))
            ->assertSessionHas('success');
        $this->assertSame('dalam_perbaikan', $facility->fresh()->status);

        // Reservasi baru untuk fasilitas rusak ditolak.
        $this->actingAs($this->makeUser())->from(route('reservations.index'))
            ->post(route('reservations.store'), $this->reservationPayload(['facility_id' => $facility->id]))
            ->assertSessionHas('error');

        // Selesaikan laporan lalu aktifkan kembali.
        $this->actingAs($petugas)->post(route('petugas.updateReportStatus'), [
            'id' => $report->id,
            'status' => 'resolved',
            'resolution_notes' => 'Perbaikan QA selesai',
        ]);

        $this->actingAs($petugas)->from('/petugas/dashboard')
            ->post(route('petugas.reactivateFacility', $facility->id))
            ->assertSessionHas('success');
        $this->assertSame('aktif', $facility->fresh()->status);
    }
}
