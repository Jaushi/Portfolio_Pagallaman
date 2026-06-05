<?php
declare(strict_types=1);

require __DIR__ . '/admin-auth.php';

admin_session_start();

if (admin_is_logged_in()) {
  header('Location: /admin');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim((string) ($_POST['username'] ?? ''));
  $password = (string) ($_POST['password'] ?? '');

  if (admin_login_attempt($username, $password)) {
    header('Location: /admin');
    exit;
  }

  $error = 'Invalid username or password.';
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin Login</title>
    <style>
      body { font-family: Arial, sans-serif; background:#f6f7fb; color:#111827; margin:0; min-height:100vh; display:grid; place-items:center; padding:24px }
      .card { width:min(420px, 100%); background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:24px; box-shadow:0 12px 28px rgba(0,0,0,.08) }
      h1 { margin-top:0 }
      label { display:block; margin-top:14px; font-weight:700 }
      input { width:100%; box-sizing:border-box; padding:12px; border:1px solid #cfd8e3; border-radius:10px; margin-top:6px }
      button { margin-top:18px; width:100%; padding:12px; border:0; border-radius:10px; background:#111827; color:#fff; font-weight:700; cursor:pointer }
      .error { margin-top:12px; color:#b91c1c; background:#fef2f2; border:1px solid #fecaca; padding:10px 12px; border-radius:10px }
      .hint { color:#6b7280; font-size:14px; margin-top:10px }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>Admin Login</h1>
      <p class="hint">Use the owner credentials from <code>server/admin-config.local.php</code>.</p>
      <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <form method="post" action="/admin-login">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" autocomplete="username" required />

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required />

        <button type="submit">Sign in</button>
      </form>
    </div>
  </body>
</html>
