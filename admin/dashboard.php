<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminTitle = 'Dashboard';

$stats = [
    ['Portfolio projects', count_rows($pdo, 'projects')],
    ['Skills',             count_rows($pdo, 'skills')],
    ['Education records',  count_rows($pdo, 'education')],
    ['Experience entries', count_rows($pdo, 'experience')],
    ['Achievements',       count_rows($pdo, 'achievements')],
    ['Messages received',  count_rows($pdo, 'messages')],
    ['Unread messages',    count_unread_messages($pdo)],
];

$recent = $pdo->query(
    'SELECT name, subject, is_read, created_at FROM messages ORDER BY created_at DESC LIMIT 5'
)->fetchAll();

$stmt = $pdo->prepare('SELECT username, created_at FROM admins WHERE id = :id');
$stmt->execute([':id' => $_SESSION['admin_id']]);
$account = $stmt->fetch();

require __DIR__ . '/../includes/admin_header.php';
?>

    <h1>Dashboard</h1>
    <p>Welcome back, <strong><?= e($_SESSION['admin_username']) ?></strong>.</p>

    <div class="stats">
      <?php foreach ($stats as [$label, $value]): ?>
      <div class="stat">
        <strong><?= (int) $value ?></strong>
        <span><?= e($label) ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <section class="panel">
      <h2>Recent messages</h2>
      <?php if (!$recent): ?>
        <p>No messages yet.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>From</th><th>Subject</th><th>Received</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $msg): ?>
            <tr>
              <td><?= e($msg['name']) ?></td>
              <td><?= e($msg['subject']) ?></td>
              <td><?= e($msg['created_at']) ?></td>
              <td><?= $msg['is_read'] ? 'Read' : '<strong>Unread</strong>' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2>Administrator account</h2>
      <p>Signed in as <strong><?= e($account['username'] ?? '') ?></strong>.</p>
      <p>Account created: <?= e($account['created_at'] ?? '') ?></p>
      <p>Your session ends automatically after 30 minutes of inactivity.</p>
    </section>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>