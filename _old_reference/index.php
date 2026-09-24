<?php

session_start();
require __DIR__ . '/config/db.php';

$result = mysqli_query($conn, 'SELECT * FROM facilities ORDER BY nama ASC');

if ($result === false) {
    die('Unable to load facility data. Please ensure the database schema has been imported.');
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function statusLabel($status)
{
    return ucwords(str_replace('_', ' ', $status));
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DIPSpace - Daftar Fasilitas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a class="navbar-brand" href="index.php">
            <img src="assets/logo-undip.png" alt="Undip">
            <span>DIPSpace</span>
        </a>
        <div class="navbar-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="hello">Halo, <?= e($_SESSION['nama']) ?></span>
                <?php if (in_array($_SESSION['role'] ?? '', ['petugas', 'admin'], true)): ?>
                    <a class="nav-item" href="dashboard.php">Antrian</a>
                <?php endif; ?>
                <a class="nav-item" href="reservation.php">Reservasi</a>
                <a class="nav-item" href="report.php">Laporkan Kerusakan</a>
                <a class="nav-item" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="nav-item" href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="backdrop">
        <h1 class="page-title">Cek Ketersediaan Ruangan</h1>

        <div class="card" style="padding:0; overflow:hidden;">
            <div class="table-wrap" style="border:none; border-radius:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tipe</th>
                            <th>Lokasi</th>
                            <th>Kapasitas</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) === 0): ?>
                            <tr class="empty-row"><td colspan="5">Belum ada fasilitas.</td></tr>
                        <?php else: ?>
                            <?php while ($facility = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= e($facility['nama']) ?></td>
                                    <td><?= e($facility['tipe']) ?></td>
                                    <td><?= e($facility['lokasi']) ?></td>
                                    <td><?= e($facility['kapasitas']) ?></td>
                                    <td><span class="badge badge-<?= e($facility['status']) ?>"><?= e(statusLabel($facility['status'])) ?></span></td>
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
<?php mysqli_free_result($result); ?>