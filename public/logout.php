<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    Auth::logout();
}

redirect('/login.php');
