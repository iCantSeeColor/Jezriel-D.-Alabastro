<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please reload the page and try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        // Prepared statement: the username can never change the SQL (SQL injection protection).
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $admin = $stmt->fetch();

        // Always run password_verify, even for unknown usernames,
        // so the response time does not reveal which usernames exist.
        $hash = $admin['password_hash'] ?? password_hash('placeholder-password', PASSWORD_DEFAULT);

        if ($admin && password_verify($password, $hash)) {
            // New session ID after login (prevents session fixation).
            session_regenerate_id(true);
            $_SESSION['admin_id']       = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['csrf_token']     = bin2hex(random_bytes(32));

            // Upgrade the stored hash if PHP's recommended algorithm has changed.
            if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
                $upd = $pdo->prepare('UPDATE admins SET password_hash = :h WHERE id = :id');
                $upd->execute([':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => $admin['id']]);
            }

            header('Location: dashboard.php');
            exit;
        }

        // One generic message: never say whether the username or the password was wrong.
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login</title>
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <h1>Admin Login</h1>
    <p class="sub">Sign in to manage the portfolio.</p>

    <?php if ($error): ?>
      <div class="alert" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" autocomplete="off">
      <?= csrf_field() ?>
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= e($username) ?>" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn-admin">Log in</button>
    </form>
  </div>
</div>
</body>
</html>