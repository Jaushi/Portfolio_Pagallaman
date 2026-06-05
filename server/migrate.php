<?php
declare(strict_types=1);

// Simple migration: read legacy messages file and insert into DB
try {
  $pdo = require __DIR__ . '/db.php';
} catch (Throwable $e) {
  echo "DB connection failed: " . $e->getMessage() . PHP_EOL;
  exit(1);
}

$file = __DIR__ . '/../../WebDev-Pagallaman-private/messages.jsonl';
if (!is_file($file)) {
  echo "No legacy messages file found at $file" . PHP_EOL;
  exit(0);
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
$inserted = 0;
foreach ($lines as $line) {
  $data = json_decode($line, true);
  if (!is_array($data)) continue;
  $stmt = $pdo->prepare('INSERT INTO messages (name,email,message,ts) VALUES (:name,:email,:message,:ts)');
  $stmt->execute([
    ':name' => $data['name'] ?? '',
    ':email' => $data['email'] ?? '',
    ':message' => $data['message'] ?? '',
    ':ts' => isset($data['ts']) ? date('Y-m-d H:i:s', strtotime($data['ts'])) : date('Y-m-d H:i:s'),
  ]);
  $inserted++;
}

echo "Inserted $inserted messages into DB" . PHP_EOL;
