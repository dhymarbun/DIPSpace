<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!in_array($_SESSION['role'] ?? '', ['petugas', 'admin'], true)) {
    http_response_code(403);
    exit('Akses ditolak.');
}

require __DIR__ . '/config/db.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $itemId = (int) ($_POST['id'] ?? 0);

    if ($itemId <= 0) {
        $error = 'Data yang diproses tidak valid.';
    } elseif ($action === 'approve' || $action === 'reject') {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $statement = mysqli_prepare($conn, "UPDATE reservations SET status = ? WHERE id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($statement, 'si', $newStatus, $itemId);
        mysqli_stmt_execute($statement);
        $updated = mysqli_stmt_affected_rows($statement);
        mysqli_stmt_close($statement);
        if ($updated === 1) {
            $success = $action === 'approve' ? 'Reservasi berhasil disetujui.' : 'Reservasi berhasil ditolak.';
        } else {
            $error = 'Reservasi tidak ditemukan atau sudah diproses.';
        }
    } elseif ($action === 'start_report' || $action === 'reject_report') {
        $newStatus = $action === 'start_report' ? 'in_progress' : 'rejected';
        $statement = mysqli_prepare($conn, "UPDATE facility_reports SET status = ? WHERE id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($statement, 'si', $newStatus, $itemId);
        mysqli_stmt_execute($statement);
        $updated = mysqli_stmt_affected_rows($statement);
        mysqli_stmt_close($statement);
        if ($updated === 1) {
            $success = $action === 'start_report' ? 'Laporan ditandai sedang diproses.' : 'Laporan berhasil ditolak.';
        } else {
            $error = 'Laporan tidak ditemukan atau sudah diproses.';
        }
    } else {
        $error = 'Aksi tidak dikenali.';
    }
}

$reservations = mysqli_query($conn, "SELECT r.id, f.nama AS facility_name, f.lokasi, u.email, r.start_time, r.end_time
    FROM reservations r
    INNER JOIN facilities f ON f.id = r.facility_id
    INNER JOIN users u ON u.id = r.user_id
    WHERE r.status = 'pending'
    ORDER BY r.created_at ASC");

$reports = mysqli_query($conn, "SELECT fr.id, f.nama AS facility_name, f.lokasi, u.email, fr.category, fr.report_date, fr.description, fr.photo_path
    FROM facility_reports fr
    INNER JOIN facilities f ON f.id = fr.facility_id
    INNER JOIN users u ON u.id = fr.user_id
    WHERE fr.status = 'pending'
    ORDER BY fr.created_at ASC");
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Antrian Petugas - DIPSpace</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a class="navbar-brand" href="index.php">
            <img src="assets/logo-undip.png" alt="Undip">
            <span>DIPSpace</span>
        </a>
        <div class="navbar-links">
            <span class="hello">Halo, <?= e($_SESSION['nama']) ?></span>
            <div class="nav-item active">
                <a href="dashboard.php">Antrian</a>
                <div class="indicator"></div>
            </div>
            <a class="nav-item" href="reservation.php">Reservasi</a>
            <a class="nav-item" href="report.php">Laporkan Kerusakan</a>
            <a class="nav-item" href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="backdrop">
        <h1 class="page-title">Antrian yang Menunggu Diproses</h1>

        <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success !== ''): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

        <div class="card">
            <p class="card-title">Reservasi Menunggu Persetujuan</p>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fasilitas</th><th>Lokasi</th><th>Peminjam (email)</th><th>Mulai</th><th>Selesai</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($reservations) === 0): ?>
                            <tr class="empty-row"><td colspan="6">Tidak ada reservasi yang menunggu.</td></tr>
                        <?php else: ?>
                            <?php while ($reservation = mysqli_fetch_assoc($reservations)): ?>
                                <tr>
                                    <td><?= e($reservation['facility_name']) ?></td>
                                    <td><?= e($reservation['lokasi']) ?></td>
                                    <td><?= e($reservation['email']) ?></td>
                                    <td><?= e($reservation['start_time']) ?></td>
                                    <td><?= e($reservation['end_time']) ?></td>
                                    <td>
                                        <form method="post" class="action-row">
                                            <input type="hidden" name="id" value="<?= e($reservation['id']) ?>">
                                            <button class="btn btn-sm btn-success" name="action" value="approve" type="submit">Ya</button>
                                            <button class="btn btn-sm btn-outline-danger" name="action" value="reject" type="submit">Tidak</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <p class="card-title">Laporan Kerusakan Menunggu Diproses</p>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fasilitas</th><th>Lokasi</th><th>Pelapor (email)</th><th>Kategori</th><th>Tanggal</th><th>Deskripsi</th><th>Foto</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($reports) === 0): ?>
                            <tr class="empty-row"><td colspan="8">Tidak ada laporan yang menunggu.</td></tr>
                        <?php else: ?>
                            <?php while ($report = mysqli_fetch_assoc($reports)): ?>
                                <tr>
                                    <td><?= e($report['facility_name']) ?></td>
                                    <td><?= e($report['lokasi']) ?></td>
                                    <td><?= e($report['email']) ?></td>
                                    <td><span class="badge badge-<?= e($report['category']) ?>"><?= e(ucfirst($report['category'])) ?></span></td>
                                    <td><?= e($report['report_date']) ?></td>
                                    <td><?= e($report['description']) ?></td>
                                    <td><?php if ($report['photo_path']): ?><a href="<?= e($report['photo_path']) ?>" target="_blank" rel="noopener" style="color:var(--navy); font-weight:600; text-decoration:underline;">Lihat</a><?php else: ?>-<?php endif; ?></td>
                                    <td>
                                        <form method="post" class="action-stack">
                                            <input type="hidden" name="id" value="<?= e($report['id']) ?>">
                                            <button class="btn btn-sm btn-success" name="action" value="start_report" type="submit">Proses</button>
                                            <button class="btn btn-sm btn-outline-danger" name="action" value="reject_report" type="submit">Tolak</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php
mysqli_free_result($reservations);
mysqli_free_result($reports);
?>