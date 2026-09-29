<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Fasilitas - DIPSpace</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #1e3a5f; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px 12px; text-align: left; }
        th { background-color: #1e3a5f; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .date { color: #666; font-size: 14px; }
        @media print {
            body { margin: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rekap Okupansi & Frekuensi Kerusakan Fasilitas</h1>
        <p class="date">{{ date('d/m/Y H:i') }}</p>
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
            @foreach($data as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row['nama'] }}</td>
                    <td>{{ $row['tipe'] }}</td>
                    <td>{{ $row['lokasi'] }}</td>
                    <td>{{ $row['total_jam'] }} jam</td>
                    <td>{{ $row['jumlah_reservasi'] }}</td>
                    <td>{{ $row['jumlah_kerusakan'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <br>
    <button class="no-print" onclick="window.print()" style="padding:10px 20px; background:#1e3a5f; color:white; border:none; cursor:pointer;">Cetak PDF</button>
</body>
</html>
