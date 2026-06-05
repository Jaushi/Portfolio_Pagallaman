<?php

declare(strict_types=1);

require __DIR__ . '/admin-auth.php';

admin_require_login();

$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$messages = [];
$totalMessages = 0;
$usingDatabase = false;

// Prefer loading messages from the database (if available)
try {
  $pdo = require __DIR__ . '/db.php';
  $whereClause = '';
  $params = [];

  if ($search !== '') {
    $whereClause = 'WHERE name LIKE :search OR email LIKE :search OR message LIKE :search OR ts LIKE :search';
    $params[':search'] = '%' . $search . '%';
  }

  $countStmt = $pdo->prepare('SELECT COUNT(*) FROM messages ' . $whereClause);
  foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value, PDO::PARAM_STR);
  }
  $countStmt->execute();
  $totalMessages = (int) $countStmt->fetchColumn();

  $dataSql = 'SELECT id, name, email, message, ts FROM messages ' . $whereClause . ' ORDER BY id DESC LIMIT :limit OFFSET :offset';
  $stmt = $pdo->prepare($dataSql);
  foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
  }
  $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $messages = $stmt->fetchAll();
  $usingDatabase = true;
} catch (Throwable $e) {
  error_log('Failed to load messages from DB: ' . $e->getMessage());
  // Fall back to legacy files
  $candidateFiles = [
    __DIR__ . '/../../WebDev-Pagallaman-private/messages.jsonl',
    __DIR__ . '/messages.jsonl',
  ];

  foreach ($candidateFiles as $candidateFile) {
    if (!is_file($candidateFile)) continue;
    $lines = file($candidateFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
      $decoded = json_decode($line, true);
      if (is_array($decoded)) $messages[] = $decoded;
    }
  }

  if ($search !== '') {
    $messages = array_values(array_filter($messages, static function (array $message) use ($search): bool {
      $haystack = strtolower(
        (string) ($message['name'] ?? '') . ' ' .
        (string) ($message['email'] ?? '') . ' ' .
        (string) ($message['message'] ?? '') . ' ' .
        (string) ($message['ts'] ?? '')
      );

      return str_contains($haystack, strtolower($search));
    }));
  }

  $totalMessages = count($messages);
  $messages = array_slice(array_reverse($messages), $offset, $perPage);
}

$totalPages = max(1, (int) ceil(max(1, $totalMessages) / $perPage));
$page = min($page, $totalPages);

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
      .topbar {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
      }
      .logout-link {
        display: inline-block;
        padding: 8px 12px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        color: #111827;
        text-decoration: none;
        background: #fff;
      }
      .meta {
        color: #6b7280;
        margin-bottom: 16px;
      }
      .toolbar {
        display: flex;
        gap: 12px;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        margin: 18px 0 16px;
      }
      .search-form {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
      }
      .search-form input {
        min-width: 260px;
        padding: 10px 12px;
        border: 1px solid #cfd8e3;
        border-radius: 10px;
      }
      .search-form button,
      .pager a {
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid #cfd8e3;
        background: #fff;
        color: #111827;
        text-decoration: none;
        cursor: pointer;
      }
      .search-form button {
        background: #111827;
        color: #fff;
        border-color: #111827;
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
      .pager {
        display: flex;
        gap: 8px;
        align-items: center;
        justify-content: space-between;
        margin-top: 18px;
        flex-wrap: wrap;
      }
      .pager .info {
        color: #6b7280;
      }
      .pager .disabled {
        opacity: 0.5;
        pointer-events: none;
      }
    </style>
  </head>
  <body>
    <div class="wrap">
      <div class="topbar">
        <h1>Owner Messages</h1>
        <a class="logout-link" href="/admin-logout">Log out</a>
      </div>
      <p class="meta">Protected view for the site owner only. Source file: <span class="pill"><?= $usingDatabase ? 'MySQL messages table' : '../WebDev-Pagallaman-private/messages.jsonl' ?></span></p>

      <div class="toolbar">
        <form class="search-form" method="get" action="/admin">
          <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search name, email, message, or time" />
          <button type="submit">Search</button>
          <?php if ($search !== ''): ?>
            <a href="/admin">Clear</a>
          <?php endif; ?>
        </form>
        <div class="meta"><?= (int) $totalMessages ?> message<?= $totalMessages === 1 ? '' : 's' ?> found</div>
      </div>

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
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_reverse($messages) as $message): ?>
              <tr>
                <td><?= htmlspecialchars((string) ($message['ts'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($message['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($message['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="message"><?= htmlspecialchars((string) ($message['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td style="white-space:nowrap">
                  <form method="post" action="/server/admin_action.php" onsubmit="return confirm('Delete this message?');">
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($message['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                    <button type="submit" style="background:#ffeef0;border:1px solid #ffccd5;padding:6px 10px;border-radius:8px;cursor:pointer;color:#a11;">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="pager">
          <div class="info">Page <?= $page ?> of <?= $totalPages ?></div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php $prevPage = max(1, $page - 1); ?>
            <?php $nextPage = min($totalPages, $page + 1); ?>
            <?php $baseQuery = $search !== '' ? '&q=' . urlencode($search) : ''; ?>
            <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="/admin?page=<?= $prevPage ?><?= $baseQuery ?>">Previous</a>
            <a class="<?= $page >= $totalPages ? 'disabled' : '' ?>" href="/admin?page=<?= $nextPage ?><?= $baseQuery ?>">Next</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </body>
</html>
