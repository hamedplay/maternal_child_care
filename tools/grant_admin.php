<?php

/**
 * Grant admin access to an existing OTP user by phone.
 *
 * Usage:
 *   php tools/grant_admin.php 09121234567
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool must be executed from CLI.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';

use App\Repositories\AdminRepository;
use App\Repositories\UserRepository;

$phone = trim((string) ($argv[1] ?? ''));
if (!preg_match('/^09\d{9}$/', $phone)) {
    fwrite(STDERR, "Usage: php tools/grant_admin.php 09121234567\n");
    exit(1);
}

$user = (new UserRepository())->findByPhone($phone);
if ($user === null) {
    fwrite(STDERR, "No user found for this phone. User must login/register first.\n");
    exit(1);
}

try {
    (new AdminRepository())->grant((int) $user['id']);
    fwrite(STDOUT, 'Admin granted to user #' . (int) $user['id'] . ' (' . $phone . ')' . PHP_EOL);
} catch (Throwable $e) {
    fwrite(STDERR, 'Grant failed: ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, "Make sure migration 20261015_005_create_admin_users.sql is applied.\n");
    exit(1);
}
