<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$path = rawurldecode($path);
$documentRoot = __DIR__;
$target = $documentRoot . $path;

if (str_starts_with($path, '/storage/')) {
  header('HTTP/1.1 403 Forbidden');
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Forbidden';
  return true;
}

if ($path === '/server/messages.jsonl') {
  header('HTTP/1.1 403 Forbidden');
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Forbidden';
  return true;
}

if ($path === '/admin') {
  require $documentRoot . '/server/admin.php';
  return true;
}

if ($path === '/' || $path === '') {
  $target = $documentRoot . '/index.html';
}

if (is_file($target)) {
  return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Not found';
return true;
