<?php
// ================================================================
// MAC Manager — Login Page
// ================================================================
require_once __DIR__ . '/includes/config.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        if (doLogin($username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password salah';
        }
    } else {
        $error = 'Isi username dan password';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Login — MAC Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --red:#D42B2B;--red-d:#A51C1C;
  --blue:#1B3FA6;--blue-d:#122B7A;--blue-m:#1E4DBF;--blue-l:#2555D4;
}
html{height:100%}
body{
  font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
  min-height:100%;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,var(--blue-d) 0%,var(--blue) 40%,var(--blue-m) 100%);
  padding:20px;position:relative;overflow:hidden;
  -webkit-font-smoothing:antialiased;
}
body::before{content:'';position:absolute;top:-50%;right:-50%;width:100%;height:200%;background:radial-gradient(circle,rgba(212,43,43,.08) 0%,transparent 60%);pointer-events:none}
body::after{content:'';position:absolute;bottom:-30%;left:-30%;width:80%;height:80%;background:radial-gradient(circle,rgba(255,255,255,.04) 0%,transparent 60%);pointer-events:none}

.login-card{
  background:#fff;width:100%;max-width:400px;border-radius:20px;
  box-shadow:0 20px 60px rgba(18,43,122,.35),0 0 0 1px rgba(255,255,255,.1);
  overflow:hidden;position:relative;z-index:1;
  animation:cardIn .5s ease;
}
@keyframes cardIn{from{opacity:0;transform:translateY(20px) scale(.97)}to{opacity:1;transform:none}}

/* ── Header dengan Logo ── */
.login-header{
  background:linear-gradient(135deg,var(--blue-d),var(--blue));
  padding:36px 28px 30px;text-align:center;position:relative;
}
.login-header::after{
  content:'';position:absolute;bottom:0;left:0;right:0;height:4px;
  background:linear-gradient(90deg,var(--red) 0%,var(--red) 33%,#fff 33%,#fff 66%,var(--blue-l) 66%);
}

/* Logo S.NET */
.login-logo{
  width:120px;height:120px;
  margin:0 auto 20px;
  background:#fff;
  border-radius:50%;
  padding:10px;
  box-shadow:0 4px 20px rgba(0,0,0,.2);
  display:flex;align-items:center;justify-content:center;
  animation:logoPulse 3s ease-in-out infinite;
}
.login-logo img{
  width:100%;height:100%;object-fit:contain;
  border-radius:50%;
}
@keyframes logoPulse{
  0%,100%{box-shadow:0 4px 20px rgba(0,0,0,.2)}
  50%{box-shadow:0 4px 30px rgba(0,0,0,.3),0 0 0 8px rgba(255,255,255,.1)}
}

.app-name{
  color:#fff;font-size:1.5rem;font-weight:900;
  letter-spacing:.04em;margin-bottom:4px;
  text-shadow:0 2px 8px rgba(0,0,0,.2);
}
.app-desc{color:rgba(255,255,255,.65);font-size:.78rem;font-weight:500}

/* ── Form Body ── */
.login-body{padding:28px}

.fg{margin-bottom:16px}
.fl{display:block;font-size:.68rem;font-weight:700;color:#6270A0;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px}
.fc{
  width:100%;padding:13px 16px;
  border:1.5px solid #E0E6F5;border-radius:12px;
  font-family:inherit;font-size:.92rem;color:#3A4468;
  background:#F8FAFF;outline:none;transition:.2s;
}
.fc:focus{border-color:var(--blue);background:#fff;box-shadow:0 0 0 3px rgba(27,63,166,.08)}
.fc::placeholder{color:#8A95B8}

.btn-login{
  width:100%;padding:15px;border:none;border-radius:12px;
  background:linear-gradient(135deg,var(--blue),var(--blue-d));
  color:#fff;font-family:inherit;font-size:.95rem;font-weight:800;
  cursor:pointer;transition:.2s;
  box-shadow:0 4px 16px rgba(27,63,166,.3);
  margin-top:8px;letter-spacing:.02em;
  position:relative;overflow:hidden;
}
.btn-login::after{content:'';position:absolute;top:0;right:0;bottom:0;width:5px;background:var(--red);border-radius:0 12px 12px 0}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(27,63,166,.4)}
.btn-login:active{transform:scale(.98)}

.alert-err{
  background:#FEE2E2;border-left:4px solid var(--red);color:var(--red-d);
  padding:12px 14px;border-radius:10px;font-size:.84rem;font-weight:600;
  margin-bottom:16px;display:flex;align-items:center;gap:8px;
  animation:shake .4s ease;
}
@keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}

.login-footer{
  text-align:center;padding:0 28px 22px;
  font-size:.7rem;color:#8A95B8;line-height:1.5;
}
.login-footer strong{color:var(--blue)}

/* Floating particles */
.particle{position:absolute;border-radius:50%;background:rgba(255,255,255,.06);pointer-events:none;animation:float linear infinite}
@keyframes float{0%{transform:translateY(0) rotate(0deg);opacity:1}100%{transform:translateY(-100vh) rotate(720deg);opacity:0}}

@media(max-width:440px){
  .login-card{border-radius:16px}
  .login-header{padding:28px 20px 24px}
  .login-logo{width:100px;height:100px}
  .app-name{font-size:1.3rem}
  .login-body{padding:22px 20px}
  .login-footer{padding:0 20px 18px}
}
</style>
</head>
<body>
<!-- Floating particles -->
<div class="particle" style="width:6px;height:6px;left:10%;bottom:0;animation-duration:12s;animation-delay:0s"></div>
<div class="particle" style="width:8px;height:8px;left:25%;bottom:0;animation-duration:16s;animation-delay:2s"></div>
<div class="particle" style="width:4px;height:4px;left:45%;bottom:0;animation-duration:10s;animation-delay:4s"></div>
<div class="particle" style="width:10px;height:10px;left:65%;bottom:0;animation-duration:14s;animation-delay:1s"></div>
<div class="particle" style="width:5px;height:5px;left:80%;bottom:0;animation-duration:18s;animation-delay:3s"></div>
<div class="particle" style="width:7px;height:7px;left:90%;bottom:0;animation-duration:11s;animation-delay:5s"></div>

<div class="login-card">
  <div class="login-header">
    <!-- Logo S.NET -->
    <div class="login-logo">
      <img src="assets/logo.png" alt="S.NET">
    </div>
    <div class="app-name">S.NET MAC MANAGER</div>
    <div class="app-desc">MikroTik MAC Binding Manager</div>
  </div>

  <div class="login-body">
    <?php if($error): ?>
    <div class="alert-err">❌ <?=h($error)?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="fg">
        <label class="fl">Username</label>
        <input type="text" name="username" class="fc" placeholder="Masukkan username" required autofocus
               value="<?=h($_POST['username'] ?? '')?>">
      </div>
      <div class="fg">
        <label class="fl">Password</label>
        <input type="password" name="password" class="fc" placeholder="Masukkan password" required>
      </div>
      <button type="submit" class="btn-login">Masuk</button>
    </form>
  </div>

  <div class="login-footer">
    © <?=date('Y')?> <strong>S.NET</strong> — MAC Manager v1.0
  </div>
</div>
</body>
</html>
