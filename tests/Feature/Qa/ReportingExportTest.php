<?php

namespace Tests\Feature\Qa;

use App\Models\Facility;
use App\Models\FacilityReport;

/**
 * US-17 — Rekap okupansi & frekuensi kerusakan + ekspor.
 */
class ReportingExportTest extends QaTestCase
{
    private function admin(): \App\Models\User
    {
        return $this->makeUser('admin');
    }

    /**
     * Dataset terkontrol: 2 reservasi approved (1 jam + 1.5 jam = 2.5 jam),
     * 1 pending (diabaikan), 3 laporan kerusakan.
     */
    private function seedData(): Facility
    {
        $facility = $this->makeFacility(['nama' => 'Fasilitas Rekap QA']);

        $this->makeReservation(['facility_id' => $facility->id, 'status' => 'approved', 'start_time' => '2026-06-16 09:00:00', 'end_time' => '2026-06-16 10:00:00']);
        $this->makeReservation(['facility_id' => $facility->id, 'status' => 'approved', 'start_time' => '2026-06-16 13:00:00', 'end_time' => '2026-06-16 14:30:00']);
        $this->makeReservation(['facility_id' => $facility->id, 'status' => 'pending', 'start_time' => '2026-06-16 16:00:00', 'end_time' => '2026-06-16 17:00:00']);

        foreach (['kecil', 'sedang', 'parah'] as $i => $category) {
            FacilityReport::create([
                'user_id' => $this->makeUser()->id,
                'facility_id' => $facility->id,
                'category' => $category,
                'report_date' => '2026-06-0'.($i + 1),
                'description' => 'Laporan rekap QA',
                'status' => 'pending',
            ]);
        }

        return $facility;
    }

    public function test_reports_page_requires_admin(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect('/login');

        foreach (['pengguna', 'petugas'] as $role) {
            $this->actingAs($this->makeUser($role))->get(route('admin.reports.index'))->assertForbidden();
            $this->actingAs($this->makeUser($role))->get(route('admin.reports.export', ['format' => 'csv']))->assertForbidden();
        }
    }

    public function test_occupancy_and_damage_totals_match_expected_values(): void
    {
        $facility = $this->seedData();

        $this->actingAs($this->admin())->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Fasilitas Rekap QA')
            ->assertSee('2.5 jam')
            ->assertSeeInOrder(['2.5 jam', '2', '3']);
    }

    public function test_empty_facility_shows_zero_totals(): void
    {
        $this->makeFacility(['nama' => 'Fasilitas Kosong QA']);

        $this->actingAs($this->admin())->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Fasilitas Kosong QA')
            ->assertSee('0 jam');
    }

    public function test_csv_export_contains_correct_header_and_values(): void
    {
        $this->seedData();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.export', ['format' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));

        $body = $response->streamedContent();
        $this->assertStringContainsString('Nama Fasilitas', $body);
        $this->assertStringContainsString('Total Jam Okupansi', $body);
        $this->assertStringContainsString('Fasilitas Rekap QA', $body);
        $this->assertStringContainsString('2.5', $body);
    }

    public function test_excel_export_is_a_real_xlsx(): void
    {
        $this->seedData();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.export', ['format' => 'excel']));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));

        $body = $response->getContent();
        $this->assertStringStartsWith("PK\x03\x04", $body, 'XLSX harus berupa arsip ZIP.');

        // Arsip ditulis dengan metode "store" (tanpa kompresi), sehingga bagian XML
        // dapat dicari langsung pada isi berkas.
        $this->assertStringContainsString('[Content_Types].xml', $body);
        $this->assertStringContainsString('xl/workbook.xml', $body);
        $this->assertStringContainsString('xl/worksheets/sheet1.xml', $body);
        $this->assertStringContainsString('Nama Fasilitas', $body);
        $this->assertStringContainsString('Fasilitas Rekap QA', $body);
        $this->assertStringContainsString('<v>2.5</v>', $body);
    }

    public function test_pdf_export_returns_branded_printable_html(): void
    {
        $this->seedData();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.export', ['format' => 'pdf']));

        $response->assertOk();
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('text/html', $contentType);
        $this->assertStringNotContainsString('application/pdf', $contentType);

        // Header berbrand: logo, nama aplikasi, judul, dan timestamp ekspor.
        $response->assertSee('images/logo-undip.png', false)
            ->assertSee('DIPSpace')
            ->assertSee('Laporan & Rekap Fasilitas', false)
            ->assertSee('Diekspor:')
            ->assertSee('Fasilitas Rekap QA');
    }

    public function test_invalid_export_format_is_rejected(): void
    {
        $this->actingAs($this->admin())->from(route('admin.reports.index'))
            ->get(route('admin.reports.export', ['format' => 'xlsx']))
            ->assertSessionHasErrors('format');
    }

    public function test_csv_neutralizes_formula_injection(): void
    {
        // BUG-10 diperbaiki: nilai yang diawali '=' diberi prefiks apostrof.
        $this->makeFacility(['nama' => '=1+1']);

        $body = $this->actingAs($this->admin())
            ->get(route('admin.reports.export', ['format' => 'csv']))
            ->streamedContent();

        $this->assertStringContainsString("'=1+1", $body, 'Sel berbahaya harus diberi prefiks apostrof.');

        // Baris data pertama: kolom pertama harus diawali apostrof, bukan "=".
        $firstDataRow = explode("\n", trim($body))[1] ?? '';
        $this->assertSame("'=1+1", str_getcsv($firstDataRow)[0] ?? null);
    }
}
