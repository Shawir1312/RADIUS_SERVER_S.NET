<?php
session_start();
if (isset($_SESSION['portal_customer_id'])) {
    header("Location: index.php");
    exit;
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../include/functions.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan - S.NET</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .login-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .logo { width: 80px; margin-bottom: 20px; }
        h2 { margin-bottom: 10px; color: #1e3c72; font-weight: 700; }
        p { color: #666; margin-bottom: 30px; font-size: 14px; }
        .form-group { text-align: left; margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        input {
            width: 100%; padding: 12px 15px; border: 1px solid #ddd;
            border-radius: 8px; font-size: 15px; outline: none; transition: border 0.3s;
        }
        input:focus { border-color: #2a5298; }
        button {
            width: 100%; padding: 14px; background: #2a5298; color: white;
            border: none; border-radius: 8px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: background 0.3s, transform 0.1s;
        }
        button:hover { background: #1e3c72; }
        button:active { transform: scale(0.98); }
        .alert {
            padding: 12px; margin-bottom: 20px; border-radius: 8px; font-size: 14px;
        }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
    </style>
</head>
<body>
    <div class="login-box">
        <!-- You can place an img here if you have a logo -->
        <h2>S.NET Portal</h2>
        <p>Silakan masuk menggunakan Username PPPoE dan Password Anda.</p>
        
        <?php if ($msg = flash_get('error')): ?>
            <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <form action="process_login.php" method="POST">
            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="form-group">
                <label>Username (PPPoE)</label>
                <input type="text" name="username" required placeholder="Contoh: user123">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit">Masuk</button>
        </form>
    </div>
</body>
</html>
