<?php
declare(strict_types=1);

function load_db_local_config(): array
{
  $local = __DIR__ . '/db-config.local.php';
  if (!is_file($local)) return [];
  $cfg = require $local;
  return is_array($cfg) ? $cfg : [];
}

function load_db_config(): array
{
  $local = load_db_local_config();
  return [
    'host' => (string) ($local['host'] ?? '127.0.0.1'),
    'dbname' => (string) ($local['dbname'] ?? 'portfolio'),
    'user' => (string) ($local['user'] ?? 'root'),
    'pass' => (string) ($local['pass'] ?? ''),
    'port' => (int) ($local['port'] ?? 3306),
  ];
}

return load_db_config();
