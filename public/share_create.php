<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

verify_csrf();

$uuid = trim($_POST['uuid'] ?? '');
if ($uuid === '') {
    json_response(['error' => 'Missing file ID.'], 400);
}

try {
    $url = ShareLink::create($uuid);
    json_response(['url' => $url]);
} catch (RuntimeException $e) {
    json_response(['error' => $e->getMessage()], 500);
}
