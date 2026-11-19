<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminTitle = 'Messages';
$formError  = '';

// Only two filter values are ever accepted.
$filter = (($_GET['filter'] ?? '') === 'unread') ? 'unread' : 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please reload the page and try again.';
    } else {
        $action     = $_POST['action'] ?? '';
        $id         = (int) ($_POST['id'] ?? 0);
        $viewId     = (int) ($_POST['view'] ?? 0);
        $postFilter = (($_POST['filter'] ?? '') === 'unread') ? 'unread' : 'all';

        if ($action === 'toggle' && $id > 0) {
            $isRead = (($_POST['to'] ?? '') === 'read') ? 1 : 0;
            $stmt = $pdo->prepare('UPDATE messages SET is_read = :r WHERE id = :id');
            $stmt->execute([':r' => $isRead, ':id' => $id]);
            $_SESSION['admin_flash'] = $isRead ? 'Message marked as read.' : 'Message marked as unread.';

        } elseif ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare('DELETE FROM messages WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $_SESSION['admin_flash'] = 'Message deleted.';
            if ($viewId === $id) {
                $viewId = 0; // the open message was deleted, so go back to the list
            }
        }

        // Go back to the same view (filter and open message are preserved).
        $query = [];
        if ($postFilter === 'unread') {
            $query['filter'] = 'unread';
        }
        if ($viewId > 0) {
            $query['view'] = $viewId;
        }
        header('Location: messages.php' . ($query ? '?' . http_build_query($query) : ''));
        exit;
    }
}

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

// The message opened for reading (if any).
$viewMsg = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare('SELECT * FROM messages WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['view']]);
    $viewMsg = $stmt->fetch() ?: null;
}

// The SQL text depends only on the whitelisted $filter value, never on raw user input.
$sql = 'SELECT id, name, email, subject, is_read, created_at FROM messages'
     . ($filter === 'unread' ? ' WHERE is_read = 0' : '')
     . ' ORDER BY created_at DESC, id DESC LIMIT 200';
$messages = $pdo->query($sql)->fetchAll();

$totalCount  = count_rows($pdo, 'messages');
$unreadCount = count_unread_messages($pdo);
$hiddenFilter = $filter === 'unread' ? 'unread' : '';

require __DIR__ . '/../includes/admin_header.php';
?>

    <h1>Messages</h1>
    <p>Messages sent through the contact form on your website.</p>

    <?php if ($flash): ?>
      <div class="alert success" role="status"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($formError): ?>
      <div class="alert" role="alert"><?= e($formError) ?></div>
    <?php endif; ?>

    <?php if ($viewMsg): ?>
    <section class="panel">
      <h2><?= e($viewMsg['subject']) ?></h2>
      <p class="msg-meta">
        From <strong><?= e($viewMsg['name']) ?></strong>
        (<a href="mailto:<?= e($viewMsg['email']) ?>?subject=<?= e(rawurlencode('Re: ' . $viewMsg['subject'])) ?>"><?= e($viewMsg['email']) ?></a>)
        &middot; <?= e($viewMsg['created_at']) ?>
        &middot; <?= $viewMsg['is_read'] ? 'Read' : '<strong>Unread</strong>' ?>
        &middot; IP <?= e($viewMsg['ip_address']) ?>
      </p>
      <div class="msg-body"><?= nl2br(e($viewMsg['message'])) ?></div>

      <div class="row-actions">
        <form method="post" action="messages.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?= (int) $viewMsg['id'] ?>">
          <input type="hidden" name="to" value="<?= $viewMsg['is_read'] ? 'unread' : 'read' ?>">
          <input type="hidden" name="view" value="<?= (int) $viewMsg['id'] ?>">
          <input type="hidden" name="filter" value="<?= e($hiddenFilter) ?>">
          <button type="submit" class="btn-link"><?= $viewMsg['is_read'] ? 'Mark as unread' : 'Mark as read' ?></button>
        </form>
        <form method="post" action="messages.php" onsubmit="return confirm('Delete this message?');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $viewMsg['id'] ?>">
          <input type="hidden" name="view" value="<?= (int) $viewMsg['id'] ?>">
          <input type="hidden" name="filter" value="<?= e($hiddenFilter) ?>">
          <button type="submit" class="btn-danger">Delete</button>
        </form>
        <a href="messages.php<?= $filter === 'unread' ? '?filter=unread' : '' ?>" class="btn-link">Close</a>
      </div>
    </section>
    <?php elseif (isset($_GET['view'])): ?>
      <div class="alert" role="alert">That message no longer exists.</div>
    <?php endif; ?>

    <section class="panel">
      <div class="tabs">
        <a href="messages.php" class="<?= $filter === 'all' ? 'active' : '' ?>">All (<?= $totalCount ?>)</a>
        <a href="messages.php?filter=unread" class="<?= $filter === 'unread' ? 'active' : '' ?>">Unread (<?= $unreadCount ?>)</a>
      </div>

      <?php if (!$messages): ?>
        <p><?= $filter === 'unread' ? 'No unread messages.' : 'No messages yet.' ?></p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>From</th><th>Subject</th><th>Received</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($messages as $m): ?>
            <?php
              $viewQuery = ['view' => (int) $m['id']];
              if ($filter === 'unread') {
                  $viewQuery['filter'] = 'unread';
              }
            ?>
            <tr class="<?= $m['is_read'] ? '' : 'unread' ?>">
              <td><?= e($m['name']) ?><br><small><?= e($m['email']) ?></small></td>
              <td><?= e($m['subject']) ?></td>
              <td><?= e($m['created_at']) ?></td>
              <td><?= $m['is_read'] ? 'Read' : 'Unread' ?></td>
              <td>
                <div class="row-actions">
                  <a href="messages.php?<?= e(http_build_query($viewQuery)) ?>" class="btn-link">View</a>
                  <form method="post" action="messages.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <input type="hidden" name="to" value="<?= $m['is_read'] ? 'unread' : 'read' ?>">
                    <input type="hidden" name="filter" value="<?= e($hiddenFilter) ?>">
                    <button type="submit" class="btn-link"><?= $m['is_read'] ? 'Mark unread' : 'Mark read' ?></button>
                  </form>
                  <form method="post" action="messages.php" onsubmit="return confirm('Delete this message?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                    <input type="hidden" name="filter" value="<?= e($hiddenFilter) ?>">
                    <button type="submit" class="btn-danger">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </section>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>