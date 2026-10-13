<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan & Rekap Fasilitas - DIPSpace</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 40px; color: #1f2937; }
        .header { display: flex; align-items: center; gap: 12px; border-bottom: 3px solid #1e3a5f; padding-bottom: 14px; }
        .header img { height: 52px; width: auto; }
        .brand { display: flex; flex-direction: column; line-height: 1.2; }
        .brand .name { font-size: 22px; font-weight: 700; color: #1e3a5f; letter-spacing: .5px; }
        .brand .sub { font-size: 12px; color: #6b7280; }
        .title-block { text-align: center; margin: 24px 0 8px; }
        .title-block h1 { color: #1e3a5f; font-size: 20px; margin: 0; }
        .title-block .date { color: #6b7280; font-size: 13px; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 12px; text-align: left; font-size: 13px; }
        th { background-color: #1e3a5f; color: #ffffff; }
        tr:nth-child(even) td { background-color: #f9fafb; }
        .footer { margin-top: 28px; font-size: 12px; color: #6b7280; text-align: center; }
        @media print {
            body { margin: 16px; }
            .no-print { display: none !important; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ asset('images/logo-undip.png') }}" alt="Logo DIPSpace">
        <div class="brand">
            <span class="name">DIPSpace</span>
            <span class="sub">Sistem Reservasi Fasilitas</span>
        </div>
    </div>

    <div class="title-block">
        <h1>Laporan & Rekap Fasilitas</h1>
        <p class="date">Diekspor: {{ $exportedAt->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Fasilitas</th>
                <th>Tipe</th>
                <th>Lokasi</th>
                <th>Total Jam Okupansi</th>
                <th>Jumlah Reservasi</th>
                <th>Jumlah Kerusakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['nama'] }}</td>
                    <td>{{ $row['tipe'] }}</td>
                    <td>{{ $row['lokasi'] }}</td>
                    <td>{{ $row['total_jam'] }} jam</td>
                    <td>{{ $row['jumlah_reservasi'] }}</td>
                    <td>{{ $row['jumlah_kerusakan'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:#6b7280;">Tidak ada data fasilitas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Dicetak dari DIPSpace pada {{ $exportedAt->format('d/m/Y H:i') }}</div>

    <div class="no-print" style="margin-top:18px; text-align:center;">
        <button onclick="window.print()" style="padding:10px 20px; background:#1e3a5f; color:white; border:none; cursor:pointer; border-radius:4px;">Cetak / Simpan PDF</button>
    </div>
</body>
</html>
