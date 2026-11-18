<?php
require __DIR__ . '/includes/db.php';
$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';

$projectCount = count_rows($pdo, 'projects');
$skillCount   = count_rows($pdo, 'skills');
$certCount    = count_rows($pdo, 'achievements');
?>

  <section class="hero">
    <div class="hero-text reveal">
      <span class="eyebrow">Welcome to my portfolio</span>
      <h1>Hi, I'm <span class="hero-name"><?= e($fullName) ?></span></h1>
      <p class="typed-line">I am a <span id="typed" class="typed"></span><span class="caret"></span></p>
      <p class="lead"><?= e($profile['introduction'] ?? '') ?></p>

      <div class="hero-actions">
        <a href="projects.html" class="btn btn-primary">View My Projects →</a>
        <a href="contact.html" class="btn btn-ghost">Contact Me</a>
      </div>

      <ul class="hero-stats">
        <li><strong><?= $projectCount ?></strong><span>Projects Built</span></li>
        <li><strong><?= $skillCount ?></strong><span>Skills Listed</span></li>
        <li><strong><?= $certCount ?></strong><span>Certificates</span></li>
      </ul>
    </div>

    <div class="hero-photo reveal">
      <div class="img-slot img-slot--avatar" data-label="Add your photo">
        <img src="<?= e($profile['profile_image'] ?? 'images/jez.jpg') ?>" alt="Portrait of <?= e($fullName) ?>">
      </div>
    </div>
  </section>

  <section class="section reveal">
    <div class="cta-banner">
      <div>
        <h2>Let's build something together</h2>
        <p>I'm open to school collaborations, freelance practice projects and learning opportunities.</p>
      </div>
      <a href="contact.html" class="btn btn-primary">Get in touch →</a>
    </div>
  </section>

<?php require __DIR__ . '/includes/footer.php'; ?>