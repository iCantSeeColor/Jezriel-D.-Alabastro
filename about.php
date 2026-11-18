<?php
require __DIR__ . '/includes/db.php';
$pageTitle = 'About';
require __DIR__ . '/includes/header.php';

$educationList = get_education($pdo);
?>

  <header class="page-head reveal">
    <span class="eyebrow">About Me</span>
    <h1>Get to know me</h1>
    <p class="page-sub">A short introduction to my background, my education, the goals I'm working towards, and the things I enjoy outside of coding.</p>
  </header>

  <section class="section reveal">
    <div class="grid grid-2">
      <article class="card">
        <span class="card-icon">🧭</span>
        <h3>My Background</h3>
        <p><?= nl2br(e($profile['biography'] ?? '')) ?></p>
        <p>
          Based in <strong><?= e($profile['location'] ?? '') ?></strong>,
          studying at <strong><?= e($profile['school'] ?? '') ?></strong>.
        </p>
      </article>

      <article class="card">
        <span class="card-icon">🎓</span>
        <h3>Education</h3>
        <ul class="detail-list">
          <?php foreach ($educationList as $edu): ?>
          <li>
            <strong><?= e($edu['degree']) ?></strong>
            <span><?= e($edu['school']) ?> — <?= e($edu['period_label']) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </article>
    </div>
  </section>

  <section class="section reveal">
    <div class="grid grid-2">
      <article class="card">
        <span class="card-icon">🎯</span>
        <h3>My Goals</h3>
        <ul class="bullet-list">
          <li>Graduate with a BSINT degree and build a strong IT portfolio.</li>
          <li>Become a confident full-stack developer, starting from my Java foundation.</li>
          <li>Earn more industry certifications, especially in networking and development.</li>
          <li>Contribute to a real-world software project that helps students and local communities.</li>
          <li>Keep improving my problem-solving speed through daily practice.</li>
        </ul>
      </article>

      <article class="card">
        <span class="card-icon">💚</span>
        <h3>Interests</h3>
        <ul class="bullet-list">
          <li><strong>Chess</strong> — my favourite way to train patience and strategy.</li>
          <li><strong>Mobile games</strong> — a fun break that also inspires game design ideas.</li>
          <li><strong>Exercise</strong> — I work out regularly to stay healthy and focused.</li>
          <li><strong>Animation</strong> — turning simple drawings into moving stories.</li>
          <li><strong>Learning new tech</strong> — always exploring a new language or tool.</li>
        </ul>
      </article>
    </div>
  </section>

  <section class="section reveal">
    <div class="section-head">
      <span class="eyebrow">Quick facts</span>
      <h2>A few things about me</h2>
    </div>
    <div class="grid grid-4">
      <div class="stat-card"><strong><?= count_rows($pdo, 'projects') ?></strong><span>Projects</span></div>
      <div class="stat-card"><strong><?= count_rows($pdo, 'skills') ?></strong><span>Skills</span></div>
      <div class="stat-card"><strong><?= count_rows($pdo, 'achievements') ?></strong><span>Certificates</span></div>
      <div class="stat-card"><strong><?= count_rows($pdo, 'education') ?></strong><span>Education records</span></div>
    </div>
  </section>

  <section class="section reveal">
    <div class="cta-banner">
      <div>
        <h2>Want to see what I've built?</h2>
        <p>Take a look at my Java, C++ and animation projects.</p>
      </div>
      <a href="projects.html" class="btn btn-primary">View projects →</a>
    </div>
  </section>

<?php require __DIR__ . '/includes/footer.php'; ?>