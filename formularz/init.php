<?php
declare(strict_types=1);

$domainRoot = dirname(__DIR__, 2);

require $domainRoot . '/private/src/Common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(405, [
        'ok' => false,
        'message' => 'Niedozwolona metoda.',
    ]);
}

startFormSession();

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['form_started_at'] = time();

jsonResponse(200, [
    'ok' => true,
    'csrf_token' => $_SESSION['csrf_token'],
]);
