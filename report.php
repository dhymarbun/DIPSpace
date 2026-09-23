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

$error = '';
$success = '';
$facilityId = (int) ($_POST['facility_id'] ?? 0);
$category = $_POST['category'] ?? '';
$reportDate = $_POST['report_date'] ?? date('Y-m-d');
$description = trim($_POST['description'] ?? '');
$allowedCategories = ['kecil', 'sedang', 'parah'];
$photoPath = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$facilityId || !in_array($category, $allowedCategories, true) || $reportDate === '' || $description === '') {
        $error = 'Semua data laporan wajib diisi dengan benar.';
    } elseif (!DateTime::createFromFormat('!Y-m-d', $reportDate) || DateTime::createFromFormat('!Y-m-d', $reportDate)->format('Y-m-d') !== $reportDate) {
        $error = 'Tanggal laporan tidak valid.';
    } else {
        $facilityStatement = mysqli_prepare($conn, "SELECT id FROM facilities WHERE id = ? AND status <> 'dalam_perbaikan' LIMIT 1");
        mysqli_stmt_bind_param($facilityStatement, 'i', $facilityId);
        mysqli_stmt_execute($facilityStatement);
        $facility = mysqli_fetch_assoc(mysqli_stmt_get_result($facilityStatement));
        mysqli_stmt_close($facilityStatement);

        if (!$facility) {
            $error = 'Fasilitas tidak tersedia atau sedang dalam perbaikan.';
        } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['photo'];
            $allowedMimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
                $error = 'Foto tidak valid atau ukurannya lebih dari 5 MB.';
            } else {
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($fileInfo, $file['tmp_name']);
                finfo_close($fileInfo);

                if (!isset($allowedMimeTypes[$mimeType])) {
                    $error = 'Format foto harus JPG, PNG, atau WEBP.';
                } else {
                    $uploadDirectory = __DIR__ . '/uploads/reports';
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
                        $error = 'Folder penyimpanan foto tidak dapat dibuat.';
                    } else {
                        $fileName = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mimeType];
                        if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . '/' . $fileName)) {
                            $error = 'Foto tidak dapat disimpan.';
                        } else {
                            $photoPath = 'uploads/reports/' . $fileName;
                        }
                    }
                }
            }
        }

        if ($error === '') {
            $userId = (int) $_SESSION['user_id'];
            $insertStatement = mysqli_prepare($conn, 'INSERT INTO facility_reports (user_id, facility_id, category, report_date, description, photo_path) VALUES (?, ?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($insertStatement, 'iissss', $userId, $facilityId, $category, $reportDate, $description, $photoPath);

            if (mysqli_stmt_execute($insertStatement)) {
                $success = 'Laporan kerusakan berhasil dikirim.';
                $facilityId = 0;
                $category = '';
                $reportDate = date('Y-m-d');
                $description = '';
            } else {
                if ($photoPath !== null) {
                    @unlink(__DIR__ . '/' . $photoPath);
                }
                $error = 'Laporan tidak dapat disimpan. Silakan coba lagi.';
            }

            mysqli_stmt_close($insertStatement);
        }
    }
}

$facilities = mysqli_query($conn, "SELECT id, nama, lokasi FROM facilities WHERE status <> 'dalam_perbaikan' ORDER BY nama ASC");
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporkan Kerusakan - DIPSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">DIPSpace</a>
            <div class="navbar-nav ms-auto"><span class="navbar-text me-3">Halo, <?= e($_SESSION['nama']) ?></span><a class="nav-link" href="reservation.php">Reservasi</a><a class="nav-link active" href="report.php">Laporkan Kerusakan</a><a class="nav-link" href="logout.php">Logout</a></div>
        </div>
    </nav>
    <main class="container py-5">
        <h1 class="h2 mb-4">Laporkan Kerusakan Fasilitas</h1>
        <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success !== ''): ?><div class="alert alert-success" role="alert"><?= e($success) ?></div><?php endif; ?>
        <div class="card shadow-sm"><div class="card-body p-4">
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3"><label for="facility_id" class="form-label">Fasilitas</label><select class="form-select" id="facility_id" name="facility_id" required><option value="">Pilih fasilitas</option><?php while ($facility = mysqli_fetch_assoc($facilities)): ?><option value="<?= e($facility['id']) ?>" <?= $facilityId === (int) $facility['id'] ? 'selected' : '' ?>><?= e($facility['nama']) ?> — <?= e($facility['lokasi']) ?></option><?php endwhile; ?></select></div>
                <div class="row"><div class="col-md-6 mb-3"><label for="category" class="form-label">Kategori kerusakan</label><select class="form-select" id="category" name="category" required><option value="">Pilih kategori</option><?php foreach ($allowedCategories as $option): ?><option value="<?= e($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select></div><div class="col-md-6 mb-3"><label for="report_date" class="form-label">Tanggal</label><input type="date" class="form-control" id="report_date" name="report_date" value="<?= e($reportDate) ?>" required></div></div>
                <div class="mb-3"><label for="description" class="form-label">Deskripsi masalah</label><textarea class="form-control" id="description" name="description" rows="5" maxlength="2000" required><?= e($description) ?></textarea></div>
                <div class="mb-3"><label for="photo" class="form-label">Foto (opsional)</label><input type="file" class="form-control" id="photo" name="photo" accept=".jpg,.jpeg,.png,.webp"><div class="form-text">Format JPG, PNG, atau WEBP. Maksimal 5 MB.</div></div>
                <button type="submit" class="btn btn-primary">Kirim Laporan</button>
            </form>
        </div></div>
    </main>
</body>
</html>
<?php mysqli_free_result($facilities); ?>
