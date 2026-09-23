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
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DIPSpace - Daftar Fasilitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="index.php">DIPSpace</a><div class="navbar-nav ms-auto"><?php if (isset($_SESSION['user_id'])): ?><span class="navbar-text me-3">Halo, <?= e($_SESSION['nama']) ?></span><?php if (in_array($_SESSION['role'] ?? '', ['petugas', 'admin'], true)): ?><a class="nav-link" href="dashboard.php">Antrian</a><?php endif; ?><a class="nav-link" href="reservation.php">Reservasi</a><a class="nav-link" href="report.php">Laporkan Kerusakan</a><a class="nav-link" href="logout.php">Logout</a><?php else: ?><a class="nav-link" href="login.php">Login</a><?php endif; ?></div></div></nav>
    <main class="container py-5">
        <div class="mb-4"><h1 class="mb-1">DIPSpace</h1><p class="text-body-secondary mb-0">Daftar fasilitas kampus yang tersedia untuk reservasi.</p></div>
        <div class="card shadow-sm"><div class="card-body p-0"><div class="table-responsive"><table class="table table-striped table-hover mb-0"><thead class="table-dark"><tr><th>Nama</th><th>Tipe</th><th>Lokasi</th><th>Kapasitas</th><th>Status</th></tr></thead><tbody><?php if (mysqli_num_rows($result) === 0): ?><tr><td colspan="5" class="text-center text-body-secondary py-4">Belum ada fasilitas.</td></tr><?php else: ?><?php while ($facility = mysqli_fetch_assoc($result)): ?><tr><td><?= e($facility['nama']) ?></td><td><?= e($facility['tipe']) ?></td><td><?= e($facility['lokasi']) ?></td><td><?= e($facility['kapasitas']) ?></td><td><?= e($facility['status']) ?></td></tr><?php endwhile; ?><?php endif; ?></tbody></table></div></div></div>
    </main>
</body>
</html>
<?php mysqli_free_result($result); ?>
