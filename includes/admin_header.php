<?php
// Shared admin layout (top part).
// Every admin page must call require_admin() BEFORE including this file,
// and set $adminTitle first.
$adminTitle  = $adminTitle ?? 'Admin';
$currentPage = basename($_SERVER['SCRIPT_NAME']);

// Menu items. A link only appears once its page exists in the admin/ folder.
$adminNav = [
    ['dashboard.php',    'Dashboard'],
    ['profile.php',      'Profile'],
    ['skills.php',       'Skills'],
    ['projects.php',     'Projects'],
    ['education.php',    'Education'],
    ['experience.php',   'Experience'],
    ['achievements.php', 'Achievements'],
    ['messages.php',     'Messages'],
    ['settings.php',     'Settings'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($adminTitle) ?> | Admin</title>
<link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-layout">

  <aside class="admin-side">
    <h2>Portfolio Admin</h2>
    <nav aria-label="Admin navigation">
      <?php foreach ($adminNav as [$file, $label]): ?>
        <?php if (!file_exists(__DIR__ . '/../admin/' . $file)) continue; ?>
        <a href="<?= e($file) ?>"<?= $currentPage === $file ? ' class="active" aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <a href="../index.php" target="_blank" rel="noopener">View website ↗</a>
    </nav>

    <form method="post" action="logout.php">
      <?= csrf_field() ?>
      <button type="submit" class="btn-admin">Log out</button>
    </form>
  </aside>

  <main class="admin-main">