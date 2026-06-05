<?php
declare(strict_types=1);

require __DIR__ . '/admin-auth.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int) ($_POST['id'] ?? 0);
  $name = trim((string) ($_POST['name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $message = trim((string) ($_POST['message'] ?? ''));

  if ($id <= 0) {
    http_response_code(400);
    echo 'Invalid id';
    exit;
  }

  try {
    $pdo = require __DIR__ . '/db.php';
    $stmt = $pdo->prepare('UPDATE messages SET name = :name, email = :email, message = :message WHERE id = :id');
    $stmt->execute([':name' => $name, ':email' => $email, ':message' => $message, ':id' => $id]);
  } catch (Throwable $e) {
    error_log('Failed to update message: ' . $e->getMessage());
  }

  header('Location: /admin');
  exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo 'Missing id';
  exit;
}

$messageRow = null;
try {
  $pdo = require __DIR__ . '/db.php';
  $stmt = $pdo->prepare('SELECT id, name, email, message, ts FROM messages WHERE id = :id');
  $stmt->execute([':id' => $id]);
  $messageRow = $stmt->fetch();
} catch (Throwable $e) {
  error_log('Failed to load message for edit: ' . $e->getMessage());
}

if (!$messageRow) {
  http_response_code(404);
  echo 'Message not found';
  exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Edit Message</title>
    <style>
      body { font-family: Arial, sans-serif; background:#f6f7fb; color:#111827; padding:24px }
      .card { max-width:700px;margin:0 auto;background:#fff;padding:20px;border-radius:12px;border:1px solid #e6eef8 }
      label { display:block;margin-top:12px;font-weight:700 }
      input[type="text"], textarea { width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px }
      textarea { min-height:140px }
      .actions { margin-top:14px }
      .btn { display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;cursor:pointer }
      .btn.primary { background:#1d4ed8;color:#fff;border-color:#1e40af }
    </style>
  </head>
  <body>
    <div class="card">
      <h2>Edit Message</h2>
      <p><a href="/admin">Back to list</a> | <a href="/admin-logout">Log out</a></p>
      <form method="post" action="/server/admin_edit.php">
        <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($messageRow['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars((string) ($messageRow['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
        <label>Email</label>
        <input type="text" name="email" value="<?= htmlspecialchars((string) ($messageRow['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
        <label>Message</label>
        <textarea name="message"><?= htmlspecialchars((string) ($messageRow['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        <div class="actions">
          <button type="submit" class="btn primary">Save</button>
          <a href="/admin" class="btn">Cancel</a>
        </div>
      </form>
    </div>
  </body>
</html>
