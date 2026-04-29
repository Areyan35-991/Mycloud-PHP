#!/usr/bin/env php
<?php
/**
 * Run this ONCE from the command line to generate your password hash.
 *
 * Usage:
 *   php setup.php
 *
 * Then paste the output hash into config/config.php as OWNER_PASSWORD_HASH.
 */

echo "\n=== MyCloud Setup ===\n\n";
echo "Enter a password for your admin account: ";
system('stty -echo');   // hide input
$password = trim(fgets(STDIN));
system('stty echo');
echo "\n";

if (strlen($password) < 8) {
    echo "Error: Password must be at least 8 characters.\n";
    exit(1);
}

$hash = password_hash('mycloudspace101', PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,
    'time_cost'   => 4,
    'threads'     => 1,
]);

echo "\nYour password hash:\n\n";
echo $hash . "\n\n";
echo "Paste this into config/config.php as the value of OWNER_PASSWORD_HASH.\n\n";
