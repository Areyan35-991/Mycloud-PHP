<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/dashboard.php');
}

verify_csrf();

try {
    if (empty($_FILES['file'])) {
        throw new RuntimeException('No file received.');
    }
    Storage::store($_FILES['file']);
    $_SESSION['flash'] = 'File uploaded successfully.';
} catch (RuntimeException $e) {
    $_SESSION['flash'] = 'Error: ' . $e->getMessage();
}

redirect('/dashboard.php');
