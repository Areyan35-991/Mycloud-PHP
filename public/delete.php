<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dashboard.php');
}

verify_csrf();

$uuid = trim($_POST['uuid'] ?? '');
if ($uuid === '') {
    $_SESSION['flash'] = 'Error: Missing file ID.';
    redirect('/dashboard.php');
}

try {
    Storage::delete($uuid);
    $_SESSION['flash'] = 'File deleted.';
} catch (RuntimeException $e) {
    $_SESSION['flash'] = 'Error: ' . $e->getMessage();
}

redirect('/dashboard.php');
