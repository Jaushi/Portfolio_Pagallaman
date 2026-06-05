<?php
declare(strict_types=1);

require __DIR__ . '/admin-auth.php';

admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo 'Method Not Allowed';
  exit;
}

$action = $_POST['action'] ?? '';
if ($action === 'delete') {
  $id = (int) ($_POST['id'] ?? 0);
  if ($id <= 0) {
    http_response_code(400);
    echo 'Invalid id';
    exit;
  }

  try {
    $pdo = require __DIR__ . '/db.php';
    $stmt = $pdo->prepare('DELETE FROM messages WHERE id = :id');
    $stmt->execute([':id' => $id]);
  } catch (Throwable $e) {
    error_log('Failed to delete message: ' . $e->getMessage());
    // attempt to continue
  }

  header('Location: /admin');
  exit;
}

http_response_code(400);
echo 'Unknown action';
