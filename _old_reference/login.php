<?php

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $statement = mysqli_prepare($conn, 'SELECT id, nama, password, role FROM users WHERE email = ? LIMIT 1');
    mysqli_stmt_bind_param($statement, 's', $email);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($statement);

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['role'] = $user['role'];

        header('Location: index.php');
        exit;
    }

    $error = 'Email atau password salah';
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
    <title>Login - DIPSpace</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .login-page {
            display: flex;
            width: 100%;
            height: 100vh;
            background: #fff;
            position: relative;
            overflow: hidden;
        }

        .login-left {
            flex: 0 0 45%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 9%;
            max-width: none;
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            position: absolute;
            top: 40px;
            left: 10%;
        }

        .login-brand img { width: 56px; height: 56px; }
        .login-brand span { font-style: italic; font-weight: 500; font-size: 24px; }

        .login-left h1 {
            font-size: 30px;
            font-weight: 500;
            margin: 0 0 8px;
        }

        .login-left .subtitle {
            font-size: 15px;
            margin: 0 0 32px;
            color: #111;
        }

        .login-field { margin-bottom: 20px; }
        .login-field label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .login-field input {
            width: 100%;
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            padding: 9px 12px;
            font-family: inherit;
            font-size: 13px;
        }

        .login-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: -8px 0 20px;
            font-size: 12px;
        }

        .login-row label { display: flex; align-items: center; gap: 6px; font-weight: 500; }
        .login-row a { color: #0c2a92; font-weight: 500; }

        .login-btn {
            width: 100%;
            background: #060fff;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .login-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #666;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .login-divider::before, .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #d9d9d9;
        }

        .login-oauth {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }

        .login-oauth button {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            background: #fff;
            padding: 10px;
            font-size: 12px;
            font-weight: 500;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .login-signup {
            text-align: center;
            font-size: 13px;
        }
        .login-signup a { color: #0f3dde; font-weight: 600; }

        .login-right {
            flex: 0 0 55%;
            box-sizing: border-box;
            padding: 24px 24px 24px 0;
        }

        .login-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 45px 45px 45px 45px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.25);
        }

        @media (max-width: 900px) {
            .login-page { flex-direction: column; }
            .login-right { display: none; }
            .login-left { max-width: none; padding-top: 120px; }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-brand">
            <img src="assets/logo-undip.png" alt="Undip">
            <span>DIPSpace</span>
        </div>

        <div class="login-left">
            <h1>Welcome back!</h1>
            <p class="subtitle">Enter your credentials to access your account</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="login-field">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="login-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <div class="login-row">
                    <label><input type="checkbox" name="remember"> Remember for 30 days</label>
                    <a href="#" tabindex="-1" title="Belum tersedia">Forgot password</a>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>

            <div class="login-divider">Or</div>

            <div class="login-oauth">
                <button type="button" disabled title="Belum tersedia">Sign in with Google</button>
                <button type="button" disabled title="Belum tersedia">Sign in with SSO</button>
            </div>

            <p class="login-signup">Don't have an account? <a href="#" tabindex="-1" title="Belum tersedia">Sign Up</a></p>
            <p class="login-signup" style="margin-top:12px;"><a href="index.php">&larr; Kembali ke daftar fasilitas</a></p>
        </div>

        <div class="login-right">
            <img src="assets/login-photo.png" alt="">
        </div>
    </div>
</body>
</html>