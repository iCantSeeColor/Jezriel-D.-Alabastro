<?php
require __DIR__ . '/includes/db.php';
$pageTitle = 'Projects';
require __DIR__ . '/includes/header.php';

$projects = get_projects($pdo);

// Labels for the filter buttons (unknown categories fall back to a capitalized name).
$categoryLabels = [
    'java'      => 'Java',
    'cpp'       => 'C++',
    'animation' => 'Animation',
    'web'       => 'Web',
];

$categories = array_values(array_unique(array_column($projects, 'category')));
?>

  <header class="page-head reveal">
    <span class="eyebrow">Projects</span>
    <h1>Things I have built</h1>
    <p class="page-sub">A selection of school projects and personal practice builds made with Java, C++, Adobe Animate and web technologies.</p>
  </header>

  <div class="filter-bar reveal" role="group" aria-label="Filter projects by category">
    <button class="filter-btn active" type="button" data-filter="all">All</button>
    <?php foreach ($categories as $cat): ?>
    <button class="filter-btn" type="button" data-filter="<?= e($cat) ?>"><?= e($categoryLabels[$cat] ?? ucfirst($cat)) ?></button>
    <?php endforeach; ?>
  </div>

  <section class="section">
    <?php if (!$projects): ?>
      <p>No projects have been added yet.</p>
    <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($projects as $project): ?>
      <article class="card project-card reveal" data-category="<?= e($project['category']) ?>">
        <div class="img-slot" data-label="Project image">
          <img src="<?= e($project['image']) ?>" alt="Screenshot of <?= e($project['title']) ?>">
        </div>
        <div class="project-body">
          <span class="project-type"><?= e($project['project_type']) ?></span>
          <h3><?= e($project['title']) ?></h3>
          <p><?= e($project['description']) ?></p>
          <?php if (!empty($project['tags'])): ?>
          <ul class="tags">
            <?php foreach (explode(',', $project['tags']) as $tag): ?>
            <li class="tag"><?= e(trim($tag)) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <section class="section reveal">
    <div class="cta-banner">
      <div>
        <h2>Interested in working together?</h2>
        <p>Send me a message and let's talk about your idea.</p>
      </div>
      <a href="contact.php" class="btn btn-primary">Contact me →</a>
    </div>
  </section>

<?php require __DIR__ . '/includes/footer.php'; ?>