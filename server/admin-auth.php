<?php
declare(strict_types=1);

function admin_session_start(): void
{
  if (session_status() === PHP_SESSION_ACTIVE) {
    return;
  }

  session_start();
}

function admin_expected_credentials(): array
{
  $config = require __DIR__ . '/admin-config.php';

  return [
    'username' => (string) ($config['username'] ?? 'owner'),
    'password' => (string) ($config['password'] ?? ''),
  ];
}

function admin_is_logged_in(): bool
{
  admin_session_start();
  return !empty($_SESSION['admin_logged_in']);
}

function admin_require_login(): void
{
  if (admin_is_logged_in()) {
    return;
  }

  header('Location: /admin-login');
  exit;
}

function admin_login_attempt(string $username, string $password): bool
{
  admin_session_start();
  $credentials = admin_expected_credentials();

  if ($username !== $credentials['username'] || !hash_equals($credentials['password'], $password)) {
    return false;
  }

  session_regenerate_id(true);
  $_SESSION['admin_logged_in'] = true;
  $_SESSION['admin_username'] = $username;

  return true;
}

function admin_logout(): void
{
  admin_session_start();
  $_SESSION = [];

  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
  }

  session_destroy();
}
