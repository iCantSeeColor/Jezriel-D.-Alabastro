<?php
require __DIR__ . '/includes/db.php';
$pageTitle = 'Resume';
require __DIR__ . '/includes/header.php';

$educationList = get_education($pdo);
$experienceList = get_experience($pdo);
$achievements = get_achievements($pdo);
?>

  <header class="page-head reveal">
    <span class="eyebrow">Resume</span>
    <h1>Education, experience &amp; certifications</h1>
    <p class="page-sub">A summary of my academic journey, the activities I take part in, and the training and certifications I have earned so far.</p>
    <div class="hero-actions">
      <button class="btn btn-primary" id="printResume" type="button">🖨️ Print / Save as PDF</button>
    </div>
  </header>

  <section class="section reveal">
    <div class="section-head">
      <span class="eyebrow">Education</span>
      <h2>Academic background</h2>
    </div>

    <?php if (!$educationList): ?>
      <p>No education records have been added yet.</p>
    <?php else: ?>
    <div class="timeline">
      <?php foreach ($educationList as $edu): ?>
      <article class="timeline-item">
        <span class="timeline-date"><?= e($edu['period_label']) ?></span>
        <h3><?= e($edu['degree']) ?></h3>
        <p class="timeline-place"><?= e($edu['school']) ?></p>
        <?php if (!empty($edu['description'])): ?>
        <p><?= e($edu['description']) ?></p>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <section class="section reveal">
    <div class="section-head">
      <span class="eyebrow">Experience</span>
      <h2>Experience &amp; involvement</h2>
    </div>

    <?php if (!$experienceList): ?>
      <p>No experience has been added yet.</p>
    <?php else: ?>
    <div class="grid grid-2">
      <?php foreach ($experienceList as $exp): ?>
      <article class="card">
        <span class="card-icon">💼</span>
        <h3><?= e($exp['title']) ?></h3>
        <p class="timeline-place"><?= e($exp['organization']) ?> — <?= e($exp['period_label']) ?></p>
        <?php if (!empty($exp['description'])): ?>
        <p><?= e($exp['description']) ?></p>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <section class="section reveal">
    <div class="section-head">
      <span class="eyebrow">Training &amp; Certifications</span>
      <h2>Certificates and training</h2>
    </div>

    <?php if (!$achievements): ?>
      <p>No certificates have been added yet.</p>
    <?php else: ?>
    <div class="accordion">
      <?php foreach ($achievements as $item): ?>
      <div class="accordion-item">
        <button class="accordion-header" type="button" aria-expanded="false">
          <?= e($item['title']) ?>
          <span class="accordion-icon">+</span>
        </button>
        <div class="accordion-panel">
          <div class="accordion-panel-inner">
            <?php if (!empty($item['issuer'])): ?>
            <p><strong>Issued by:</strong> <?= e($item['issuer']) ?></p>
            <?php endif; ?>
            <p><?= e($item['description']) ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <section class="section reveal">
    <div class="cta-banner">
      <div>
        <h2>Want a copy of my resume?</h2>
        <p>Use the print button above, or send me a message and I'll get back to you.</p>
      </div>
      <a href="contact.php" class="btn btn-primary">Contact me →</a>
    </div>
  </section>

<?php require __DIR__ . '/includes/footer.php'; ?>