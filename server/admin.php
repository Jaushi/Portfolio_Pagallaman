<?php

declare(strict_types=1);

$config = require __DIR__ . '/admin-config.php';
$expectedUser = (string) ($config['username'] ?? 'owner');
$expectedPass = (string) ($config['password'] ?? '');

$providedUser = $_SERVER['PHP_AUTH_USER'] ?? '';
$providedPass = $_SERVER['PHP_AUTH_PW'] ?? '';

if ($providedUser !== $expectedUser || !hash_equals($expectedPass, $providedPass)) {
  header('WWW-Authenticate: Basic realm="Owner Messages"');
  header('HTTP/1.0 401 Unauthorized');
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Authentication required.';
  exit;
}

$file = __DIR__ . '/../../WebDev-Pagallaman-private/messages.jsonl';
$messages = [];

$candidateFiles = [
  __DIR__ . '/../../WebDev-Pagallaman-private/messages.jsonl',
  __DIR__ . '/messages.jsonl',
];

foreach ($candidateFiles as $candidateFile) {
  if (!is_file($candidateFile)) {
    continue;
  }

  $lines = file($candidateFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
  foreach ($lines as $line) {
    $decoded = json_decode($line, true);
    if (is_array($decoded)) {
      $messages[] = $decoded;
    }
  }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Owner Messages</title>
    <style>
      body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 24px;
        background: #f6f7fb;
        color: #1f2937;
      }
      .wrap {
        max-width: 1100px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #dbe1ea;
        border-radius: 14px;
        padding: 24px;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
      }
      h1 {
        margin-top: 0;
      }
      .meta {
        color: #6b7280;
        margin-bottom: 16px;
      }
      table {
        width: 100%;
        border-collapse: collapse;
      }
      th,
      td {
        text-align: left;
        vertical-align: top;
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
      }
      th {
        background: #f9fafb;
        position: sticky;
        top: 0;
      }
      .empty {
        padding: 20px;
        background: #f9fafb;
        border: 1px dashed #d1d5db;
        border-radius: 12px;
        color: #6b7280;
      }
      .message {
        white-space: pre-wrap;
      }
      .pill {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        background: #e8f0ff;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 700;
      }
    </style>
  </head>
  <body>
    <div class="wrap">
      <h1>Owner Messages</h1>
      <p class="meta">Protected view for the site owner only. Source file: <span class="pill">../WebDev-Pagallaman-private/messages.jsonl</span></p>

      <?php if (!$messages): ?>
        <div class="empty">No messages received yet.</div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Name</th>
              <th>Email</th>
              <th>Message</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_reverse($messages) as $message): ?>
              <tr>
                <td><?= htmlspecialchars((string) ($message['ts'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($message['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($message['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="message"><?= htmlspecialchars((string) ($message['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </body>
</html>
