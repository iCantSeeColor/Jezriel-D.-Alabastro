<?php
// One-time setup: creates the administrator account.
// Run from the command line:  php database/create_admin.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../includes/db.php';

echo "Create administrator account\n";
echo "Username: ";
$username = trim((string) fgets(STDIN));
echo "Password (at least 10 characters; it will be visible while you type): ";
$password = trim((string) fgets(STDIN));

if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    exit("Invalid username. Use 3 to 50 letters, numbers, dots, dashes or underscores.\n");
}
if (strlen($password) < 10) {
    exit("Password must be at least 10 characters long.\n");
}

// password_hash() adds a random salt and uses a slow algorithm (bcrypt) on purpose.
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:u, :h)');
    $stmt->execute([':u' => $username, ':h' => $hash]);
} catch (PDOException $e) {
    exit("Could not create the account (the username may already exist).\n");
}

echo "Administrator '$username' created.\n";