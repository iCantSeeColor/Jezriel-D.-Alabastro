<?php
require __DIR__ . '/includes/db.php';
$pageTitle = 'Skills';
require __DIR__ . '/includes/header.php';

$skills = get_skills($pdo);

// Icon shown for each skill category.
$categoryIcons = [
    'Programming' => '💻',
    'Web'         => '🌐',
    'Database'    => '🗄️',
    'Creative'    => '🎬',
    'Networking'  => '🛰️',
    'Tools'       => '🔧',
    'Soft Skills' => '🧠',
];
?>

  <header class="page-head reveal">
    <span class="eyebrow">Skills</span>
    <h1>What I can work with</h1>
    <p class="page-sub">A mix of programming languages, web technologies, databases and creative tools that I use in my studies and personal projects.</p>
  </header>

  <section class="section reveal">
    <div class="skills-box">
      <?php if (!$skills): ?>
        <p>No skills have been added yet.</p>
      <?php else: ?>
      <div class="grid grid-2">
        <?php foreach ($skills as $skill): ?>
        <?php $level = max(0, min(100, (int) $skill['level'])); ?>
        <article class="skill-card">
          <div class="skill-top">
            <span class="skill-icon"><?= $categoryIcons[$skill['category']] ?? '⚡' ?></span>
            <div>
              <h3><?= e($skill['name']) ?></h3>
              <p class="skill-note"><?= e($skill['note']) ?></p>
            </div>
            <span class="skill-percent"><?= $level ?>%</span>
          </div>
          <div class="skill-bar"><span data-level="<?= $level ?>"></span></div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

<?php require __DIR__ . '/includes/footer.php'; ?>