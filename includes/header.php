<?php
$pageTitle = $pageTitle ?? 'Portfolio';
require_once __DIR__ . '/functions.php';

$profile  = get_profile($pdo);
$settings = get_settings($pdo);

$fullName  = $profile['full_name'] ?? 'Portfolio';
$nameParts = explode(' ', trim($fullName));
$lastName  = count($nameParts) > 1 ? array_pop($nameParts) : '';
$firstName = implode(' ', $nameParts);
$logoText  = $settings['logo_text'] ?? 'JA';

$navItems = [
    ['index.php',     '🏠', 'Home'],
    ['about.php',    '👤', 'About'],
    ['skills.php',   '⚡', 'Skills'],
    ['projects.html', '💼', 'Projects'],
    ['resume.html',   '📄', 'Resume'],
    ['contact.html',  '✉️', 'Contact'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= e($profile['headline'] ?? '') ?>">
<title><?= e($pageTitle) ?> | <?= e($fullName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="topbar">
  <a href="index.php" class="topbar-brand"><span class="brand-mark"><?= e($logoText) ?></span> <?= e($fullName) ?></a>
  <button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="sidebar">
    <span></span><span></span><span></span>
  </button>
</header>

<nav class="sidebar" id="sidebar" aria-label="Main navigation">
  <div class="brand">
    <span class="brand-mark"><?= e($logoText) ?></span>
    <span class="brand-name"><?= e($firstName) ?><small><?= e($lastName) ?></small></span>
  </div>
  <ul class="nav-list">
    <?php foreach ($navItems as [$href, $icon, $label]): ?>
    <li><a href="<?= e($href) ?>" class="nav-link"><span class="nav-icon"><?= $icon ?></span><?= e($label) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <div class="sidebar-footer">
    <button id="themeToggle" class="theme-toggle" type="button">🌙 Dark mode</button>
  </div>
</nav>
<div class="nav-overlay" id="navOverlay"></div>

<main class="main">