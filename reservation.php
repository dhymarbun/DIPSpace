<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/config/db.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_valid_slot($dateTime)
{
    $hour = (int) $dateTime->format('H');
    $minute = (int) $dateTime->format('i');

    return $hour >= 7 && $hour <= 20 && ($minute === 0 || $minute === 30);
}

$error = '';
$success = '';
$warning = '';
$facilityId = (int) ($_POST['facility_id'] ?? 0);
$startInput = $_POST['start_time'] ?? '';
$endInput = $_POST['end_time'] ?? '';
$tujuan = trim($_POST['tujuan'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $reservationId = (int) ($_POST['id'] ?? 0);
    $userId = (int) $_SESSION['user_id'];

    if ($reservationId <= 0) {
        $warning = 'Reservasi tidak dapat dibatalkan.';
    } else {
        $checkStatement = mysqli_prepare($conn, 'SELECT id, status, start_time FROM reservations WHERE id = ? AND user_id = ? LIMIT 1');
        mysqli_stmt_bind_param($checkStatement, 'ii', $reservationId, $userId);
        mysqli_stmt_execute($checkStatement);
        $reservationToCancel = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStatement));
        mysqli_stmt_close($checkStatement);

        $reservationStart = $reservationToCancel ? DateTime::createFromFormat('Y-m-d H:i:s', $reservationToCancel['start_time']) : false;

        if (!$reservationToCancel) {
            $warning = 'Reservasi tidak ditemukan atau bukan milik Anda.';
        } elseif (!in_array($reservationToCancel['status'], ['pending', 'approved'], true) || !$reservationStart || $reservationStart <= new DateTime()) {
            $warning = 'Reservasi sudah tidak dapat dibatalkan.';
        } else {
            $cancelledStatus = 'cancelled';
            $cancelStatement = mysqli_prepare($conn, 'UPDATE reservations SET status = ? WHERE id = ? AND user_id = ?');
            mysqli_stmt_bind_param($cancelStatement, 'sii', $cancelledStatus, $reservationId, $userId);

            if (mysqli_stmt_execute($cancelStatement) && mysqli_stmt_affected_rows($cancelStatement) === 1) {
                $success = 'Reservasi berhasil dibatalkan';
            } else {
                $warning = 'Reservasi tidak dapat dibatalkan.';
            }

            mysqli_stmt_close($cancelStatement);
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startTime = DateTime::createFromFormat('Y-m-d\\TH:i', $startInput);
    $endTime = DateTime::createFromFormat('Y-m-d\\TH:i', $endInput);

    if (!$facilityId || $startTime === false || $endTime === false || $tujuan === '') {
        $error = 'Semua data reservasi wajib diisi dengan format yang benar.';
    } elseif (!is_valid_slot($startTime) || !is_valid_slot($endTime)) {
        $error = 'Waktu reservasi harus berada antara pukul 07:00 hingga 20:00 dan menggunakan slot 30 menit.';
    } elseif ($endTime <= $startTime) {
        $error = 'Waktu selesai harus setelah waktu mulai.';
    } else {
        $facilityStatement = mysqli_prepare($conn, 'SELECT id FROM facilities WHERE id = ?');
        mysqli_stmt_bind_param($facilityStatement, 'i', $facilityId);
        mysqli_stmt_execute($facilityStatement);
        $facilityResult = mysqli_stmt_get_result($facilityStatement);
        $facilityExists = mysqli_fetch_assoc($facilityResult);
        mysqli_stmt_close($facilityStatement);

        if (!$facilityExists) {
            $error = 'Fasilitas yang dipilih tidak tersedia.';
        } else {
            $startValue = $startTime->format('Y-m-d H:i:s');
            $endValue = $endTime->format('Y-m-d H:i:s');
            $overlapStatement = mysqli_prepare($conn, "SELECT id FROM reservations WHERE facility_id = ? AND status = 'approved' AND start_time < ? AND end_time > ? LIMIT 1");
            mysqli_stmt_bind_param($overlapStatement, 'iss', $facilityId, $endValue, $startValue);
            mysqli_stmt_execute($overlapStatement);
            $overlapResult = mysqli_stmt_get_result($overlapStatement);
            $hasOverlap = mysqli_fetch_assoc($overlapResult);
            mysqli_stmt_close($overlapStatement);

            if ($hasOverlap) {
                $error = 'Jadwal tersebut bertabrakan dengan reservasi yang sudah disetujui.';
            } else {
                $userId = (int) $_SESSION['user_id'];
                $insertStatement = mysqli_prepare($conn, "INSERT INTO reservations (user_id, facility_id, start_time, end_time, tujuan, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                mysqli_stmt_bind_param($insertStatement, 'iisss', $userId, $facilityId, $startValue, $endValue, $tujuan);

                if (mysqli_stmt_execute($insertStatement)) {
                    $success = 'Reservasi berhasil dikirim dan menunggu persetujuan.';
                    $facilityId = 0;
                    $startInput = '';
                    $endInput = '';
                    $tujuan = '';
                } else {
                    $error = 'Reservasi tidak dapat disimpan. Silakan coba lagi.';
                }

                mysqli_stmt_close($insertStatement);
            }
        }
    }
}

$facilities = mysqli_query($conn, 'SELECT id, nama, lokasi FROM facilities ORDER BY nama ASC');
$userId = (int) $_SESSION['user_id'];
$reservationStatement = mysqli_prepare($conn, 'SELECT r.id, f.nama AS facility_name, r.start_time, r.end_time, r.status FROM reservations r INNER JOIN facilities f ON f.id = r.facility_id WHERE r.user_id = ? ORDER BY r.created_at DESC');
mysqli_stmt_bind_param($reservationStatement, 'i', $userId);
mysqli_stmt_execute($reservationStatement);
$reservations = mysqli_stmt_get_result($reservationStatement);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reservasi - DIPSpace</title>
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
            <?php if (in_array($_SESSION['role'] ?? '', ['petugas', 'admin'], true)): ?>
                <a class="nav-item" href="dashboard.php">Antrian</a>
            <?php endif; ?>
            <div class="nav-item active">
                <a href="reservation.php">Reservasi</a>
                <div class="indicator"></div>
            </div>
            <a class="nav-item" href="report.php">Laporkan Kerusakan</a>
            <a class="nav-item" href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="backdrop">
        <h1 class="page-title">Ajukan Reservasi Fasilitas</h1>

        <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($warning !== ''): ?><div class="alert alert-danger"><?= e($warning) ?></div><?php endif; ?>
        <?php if ($success !== ''): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

        <div class="card">
            <form method="post">
                <div class="form-group">
                    <label class="form-label" for="facility_id">Fasilitas</label>
                    <select class="form-control" id="facility_id" name="facility_id" required>
                        <option value="">Pilih fasilitas</option>
                        <?php while ($facility = mysqli_fetch_assoc($facilities)): ?>
                            <option value="<?= e($facility['id']) ?>" <?= $facilityId === (int) $facility['id'] ? 'selected' : '' ?>><?= e($facility['nama']) ?> — <?= e($facility['lokasi']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="start_time">Waktu Mulai</label>
                        <input type="datetime-local" class="form-control" id="start_time" name="start_time" value="<?= e($startInput) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="end_time">Waktu Selesai</label>
                        <input type="datetime-local" class="form-control" id="end_time" name="end_time" value="<?= e($endInput) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="tujuan">Tujuan Penggunaan</label>
                    <input type="text" class="form-control" id="tujuan" name="tujuan" maxlength="255" placeholder="Contoh: Rapat koordinasi organisasi mahasiswa" value="<?= e($tujuan) ?>" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Ajukan Reservasi</button>
                </div>
            </form>
        </div>

        <div class="card">
            <p class="card-title">Riwayat Reservasi Saya</p>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Fasilitas</th><th>Waktu Mulai</th><th>Waktu Selesai</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($reservations) === 0): ?>
                            <tr class="empty-row"><td colspan="5">Belum ada reservasi.</td></tr>
                        <?php else: ?>
                            <?php while ($reservation = mysqli_fetch_assoc($reservations)): ?>
                                <?php $canCancel = in_array($reservation['status'], ['pending', 'approved'], true) && strtotime($reservation['start_time']) > time(); ?>
                                <tr>
                                    <td><?= e($reservation['facility_name']) ?></td>
                                    <td><?= e($reservation['start_time']) ?></td>
                                    <td><?= e($reservation['end_time']) ?></td>
                                    <td><span class="badge badge-<?= e($reservation['status']) ?>"><?= e(ucfirst($reservation['status'])) ?></span></td>
                                    <td>
                                        <?php if ($canCancel): ?>
                                            <form method="post">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="id" value="<?= e($reservation['id']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="text-align:center; margin-top:24px;">
            <a href="report.php" class="btn btn-outline-danger">Ada fasilitas yang rusak? Laporkan sekarang!</a>
        </div>
    </div>
</body>
</html>
<?php mysqli_stmt_close($reservationStatement); ?>