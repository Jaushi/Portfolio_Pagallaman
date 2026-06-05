<?php
declare(strict_types=1);

require __DIR__ . '/admin-auth.php';

admin_logout();
header('Location: /admin-login');
exit;
