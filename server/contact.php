<?php
header('Content-Type: application/json; charset=utf-8');
// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
  exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
  exit;
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$message = trim($data['message'] ?? '');

function input_length(string $value): int
{
  return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

$errors = [];
if (input_length($name) < 2) $errors['name'] = 'Please enter your name (2+ characters)';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email';
if (input_length($message) < 5) $errors['message'] = 'Please enter a message (5+ characters)';

if (!empty($errors)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'errors' => $errors]);
  exit;
}

$entry = [
  'name' => $name,
  'email' => $email,
  'message' => $message,
  'ts' => date(DATE_ATOM)
];

$dir = __DIR__ . '/../../WebDev-Pagallaman-private';
if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Failed to prepare storage']);
  exit;
}

$file = $dir . '/messages.jsonl';
$line = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
if (file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Failed to save message']);
  exit;
}

error_log('New contact message: ' . json_encode($entry));

echo json_encode(['ok' => true, 'message' => 'Saved']);

?>
