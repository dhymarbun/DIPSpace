<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\FacilityReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    // ========== FR-15: Export Reports ==========

    public function index()
    {
        $facilities = Facility::orderBy('nama', 'asc')->get();

        // Calculate occupancy and damage data per facility
        $facilityStats = $facilities->map(function ($facility) {
            $totalHours = Reservation::where('facility_id', $facility->id)
                ->where('status', 'approved')
                ->get()
                ->sum(function ($res) {
                    return $res->start_time->diffInHours($res->end_time);
                });

            $reservationCount = Reservation::where('facility_id', $facility->id)
                ->where('status', 'approved')
                ->count();

            $damageCount = FacilityReport::where('facility_id', $facility->id)->count();

            return [
                'facility' => $facility,
                'total_hours' => $totalHours,
                'reservation_count' => $reservationCount,
                'damage_count' => $damageCount,
            ];
        });

        return view('admin.reports.index', compact('facilityStats'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,excel,pdf',
        ]);

        $format = $request->format;
        $facilities = Facility::orderBy('nama', 'asc')->get();

        $data = $facilities->map(function ($facility) {
            $totalHours = Reservation::where('facility_id', $facility->id)
                ->where('status', 'approved')
                ->get()
                ->sum(function ($res) {
                    return $res->start_time->diffInHours($res->end_time);
                });

            $reservationCount = Reservation::where('facility_id', $facility->id)
                ->where('status', 'approved')
                ->count();

            $damageCount = FacilityReport::where('facility_id', $facility->id)->count();

            return [
                'nama' => $facility->nama,
                'tipe' => $facility->tipe,
                'lokasi' => $facility->lokasi,
                'total_jam' => $totalHours,
                'jumlah_reservasi' => $reservationCount,
                'jumlah_kerusakan' => $damageCount,
            ];
        });

        if ($format === 'csv') {
            return $this->exportCsv($data);
        }

        if ($format === 'excel') {
            return $this->exportXlsx($data);
        }

        // PDF export - halaman siap cetak (browser "Save as PDF").
        return view('admin.reports.pdf', [
            'data' => $data,
            'exportedAt' => now(),
        ]);
    }

    private function exportCsv($data, $filename = 'rekap-fasilitas')
    {
        $filename = $filename . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Nama Fasilitas', 'Tipe', 'Lokasi', 'Total Jam Okupansi', 'Jumlah Reservasi', 'Jumlah Kerusakan']);

            // Data rows
            foreach ($data as $row) {
                fputcsv($file, [
                    $this->csvSafe($row['nama']),
                    $this->csvSafe($row['tipe']),
                    $this->csvSafe($row['lokasi']),
                    $this->csvSafe($row['total_jam']),
                    $this->csvSafe($row['jumlah_reservasi']),
                    $this->csvSafe($row['jumlah_kerusakan']),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Netralkan potensi CSV formula injection: sel yang diawali karakter
     * berbahaya ('=', '+', '-', '@', tab, CR) diberi prefiks apostrof agar
     * tidak dieksekusi sebagai formula saat dibuka di spreadsheet.
     */
    private function csvSafe($value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    private function exportXlsx($data)
    {
        $filename = 'rekap-fasilitas_' . date('Y-m-d_His') . '.xlsx';

        return response($this->buildXlsx($data), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Bangun berkas XLSX (Office Open XML) minimal tanpa dependensi eksternal.
     * Ekstensi `zip` tidak tersedia di lingkungan ini, sehingga arsip ZIP
     * ditulis manual memakai metode "store" (tanpa kompresi).
     */
    private function buildXlsx($data): string
    {
        $headers = ['Nama Fasilitas', 'Tipe', 'Lokasi', 'Total Jam Okupansi', 'Jumlah Reservasi', 'Jumlah Kerusakan'];

        $rows = [$headers];
        foreach ($data as $row) {
            $rows[] = [
                $row['nama'],
                $row['tipe'],
                $row['lokasi'],
                $row['total_jam'],
                $row['jumlah_reservasi'],
                $row['jumlah_kerusakan'],
            ];
        }

        $sheetRows = '';
        foreach ($rows as $r => $cells) {
            $rowNumber = $r + 1;
            $sheetRows .= '<row r="' . $rowNumber . '">';
            foreach ($cells as $c => $value) {
                $ref = chr(65 + $c) . $rowNumber;

                if ($rowNumber === 1) {
                    // Baris judul: teks tebal (style index 1).
                    $sheetRows .= '<c r="' . $ref . '" s="1" t="inlineStr"><is><t>' . $this->xml($value) . '</t></is></c>';
                } elseif (is_int($value) || is_float($value)) {
                    $sheetRows .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
                } else {
                    $sheetRows .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $this->xml($value) . '</t></is></c>';
                }
            }
            $sheetRows .= '</row>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $sheetRows . '</sheetData>'
            . '</worksheet>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Rekap Fasilitas" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            . '</styleSheet>';

        return $this->zip([
            '[Content_Types].xml' => $contentTypes,
            '_rels/.rels' => $rels,
            'xl/workbook.xml' => $workbook,
            'xl/_rels/workbook.xml.rels' => $workbookRels,
            'xl/styles.xml' => $styles,
            'xl/worksheets/sheet1.xml' => $sheet,
        ]);
    }

    /**
     * Tulis arsip ZIP (metode "store", tanpa kompresi) dari map nama => isi.
     * Dipakai untuk membungkus bagian-bagian XLSX.
     */
    private function zip(array $files): string
    {
        $local = '';
        $central = '';
        $offset = 0;
        $dos = $this->dosTime();
        $names = array_keys($files);

        foreach ($names as $name) {
            $content = $files[$name];
            $crc = crc32($content);
            $size = strlen($content);
            $nameLen = strlen($name);

            $localHeader = pack('V', 0x04034b50)
                . pack('v', 20)
                . pack('v', 0x0800) // flag: nama berkas UTF-8
                . pack('v', 0)      // metode: store
                . pack('v', $dos['time'])
                . pack('v', $dos['date'])
                . pack('V', $crc)
                . pack('V', $size)
                . pack('V', $size)
                . pack('v', $nameLen)
                . pack('v', 0)
                . $name;

            $local .= $localHeader . $content;

            $central .= pack('V', 0x02014b50)
                . pack('v', 20)
                . pack('v', 20)
                . pack('v', 0x0800)
                . pack('v', 0)
                . pack('v', $dos['time'])
                . pack('v', $dos['date'])
                . pack('V', $crc)
                . pack('V', $size)
                . pack('V', $size)
                . pack('v', $nameLen)
                . pack('v', 0)      // extra
                . pack('v', 0)      // komentar
                . pack('v', 0)      // nomor disk
                . pack('v', 0)      // atribut internal
                . pack('V', 0)      // atribut eksternal
                . pack('V', $offset)
                . $name;

            $offset += strlen($localHeader) + $size;
        }

        $end = pack('V', 0x06054b50)
            . pack('v', 0)
            . pack('v', 0)
            . pack('v', count($names))
            . pack('v', count($names))
            . pack('V', strlen($central))
            . pack('V', $offset)
            . pack('v', 0);

        return $local . $central . $end;
    }

    private function dosTime(): array
    {
        $now = getdate();

        return [
            'time' => ($now['hours'] << 11) | ($now['minutes'] << 5) | ($now['seconds'] >> 1),
            'date' => (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'],
        ];
    }

    private function xml($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
