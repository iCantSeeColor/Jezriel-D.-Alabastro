<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Dashboard</title>
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-card">
  <h1>Welcome, <?= e($_SESSION['admin_username']) ?></h1>
  <p>You are logged in. The full dashboard comes in the next commits.</p>

  <form method="post" action="logout.php">
    <?= csrf_field() ?>
    <button type="submit" class="btn-admin btn-small">Log out</button>
  </form>
</div>
</body>
</html>