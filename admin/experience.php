<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminTitle = 'Experience';
$errors     = [];
$formError  = '';
$editId     = 0;
$data       = ['title' => '', 'organization' => '', 'period_label' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please reload the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int) ($_POST['id'] ?? 0);

        // ----- DELETE -----
        if ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare('DELETE FROM experience WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $_SESSION['admin_flash'] = 'Experience entry deleted.';
            header('Location: experience.php');
            exit;
        }

        // ----- CREATE / UPDATE -----
        if ($action === 'save') {
            foreach (array_keys($data) as $f) {
                $data[$f] = trim((string) ($_POST[$f] ?? ''));
            }

            // Server-side validation
            if (mb_strlen($data['title']) < 2 || mb_strlen($data['title']) > 150) {
                $errors['title'] = 'Title must be 2 to 150 characters.';
            }
            if (mb_strlen($data['organization']) < 2 || mb_strlen($data['organization']) > 150) {
                $errors['organization'] = 'Organization must be 2 to 150 characters.';
            }
            if (mb_strlen($data['period_label']) < 1 || mb_strlen($data['period_label']) > 50) {
                $errors['period_label'] = 'Period must be 1 to 50 characters (example: 2024 - Present).';
            }
            if (mb_strlen($data['description']) > 1000) {
                $errors['description'] = 'Description must be 1000 characters or fewer.';
            }

            if (!$errors) {
                try {
                    $params = [
                        ':title'        => $data['title'],
                        ':organization' => $data['organization'],
                        ':period_label' => $data['period_label'],
                        ':description'  => $data['description'] !== '' ? $data['description'] : null,
                    ];

                    if ($id > 0) {
                        $stmt = $pdo->prepare(
                            'UPDATE experience SET title = :title, organization = :organization,
                                period_label = :period_label, description = :description
                             WHERE id = :id'
                        );
                        $stmt->execute($params + [':id' => $id]);
                        $_SESSION['admin_flash'] = 'Experience entry updated.';
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO experience (title, organization, period_label, description)
                             VALUES (:title, :organization, :period_label, :description)'
                        );
                        $stmt->execute($params);
                        $_SESSION['admin_flash'] = 'Experience entry added.';
                    }
                    header('Location: experience.php'); // redirect so a refresh does not resubmit
                    exit;
                } catch (PDOException $ex) {
                    error_log('Experience save failed: ' . $ex->getMessage());
                    $formError = 'The entry could not be saved. Please try again.';
                }
            }
            $editId = $id; // validation failed: stay in the same mode
        }
    }

} elseif (isset($_GET['edit'])) {
    // Load an existing entry into the form.
    $stmt = $pdo->prepare('SELECT * FROM experience WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $editId = (int) $row['id'];
        $data   = [
            'title'        => $row['title'],
            'organization' => $row['organization'],
            'period_label' => $row['period_label'],
            'description'  => (string) $row['description'],
        ];
    }
}

$flash   = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

$records = get_experience($pdo);

require __DIR__ . '/../includes/admin_header.php';
?>

    <h1>Experience</h1>
    <p>Add, edit and delete the work experience and involvement shown on your Resume page.</p>

    <?php if ($flash): ?>
      <div class="alert success" role="status"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($formError): ?>
      <div class="alert" role="alert"><?= e($formError) ?></div>
    <?php endif; ?>

    <section class="panel">
      <h2><?= $editId ? 'Edit experience entry' : 'Add an experience entry' ?></h2>

      <form method="post" action="experience.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $editId ?>">

        <div class="form-grid">
          <div class="field<?= isset($errors['title']) ? ' has-error' : '' ?>">
            <label for="title">Title or role</label>
            <input type="text" id="title" name="title" value="<?= e($data['title']) ?>" required>
            <?php if (isset($errors['title'])): ?><p class="err"><?= e($errors['title']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['organization']) ? ' has-error' : '' ?>">
            <label for="organization">Organization</label>
            <input type="text" id="organization" name="organization" value="<?= e($data['organization']) ?>" required>
            <?php if (isset($errors['organization'])): ?><p class="err"><?= e($errors['organization']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['period_label']) ? ' has-error' : '' ?>">
            <label for="period_label">Period (example: 2024 - Present)</label>
            <input type="text" id="period_label" name="period_label" value="<?= e($data['period_label']) ?>" required>
            <?php if (isset($errors['period_label'])): ?><p class="err"><?= e($errors['period_label']) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="field<?= isset($errors['description']) ? ' has-error' : '' ?>">
          <label for="description">Description (optional)</label>
          <textarea id="description" name="description" rows="4"><?= e($data['description']) ?></textarea>
          <?php if (isset($errors['description'])): ?><p class="err"><?= e($errors['description']) ?></p><?php endif; ?>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-admin btn-small"><?= $editId ? 'Update entry' : 'Add entry' ?></button>
          <?php if ($editId): ?>
            <a href="experience.php" class="btn-link">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <section class="panel">
      <h2>All experience entries (<?= count($records) ?>)</h2>
      <?php if (!$records): ?>
        <p>No experience entries yet. Add your first one above.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>Title / role</th><th>Organization</th><th>Period</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
              <td><?= e($r['title']) ?></td>
              <td><?= e($r['organization']) ?></td>
              <td><?= e($r['period_label']) ?></td>
              <td>
                <div class="row-actions">
                  <a href="experience.php?edit=<?= (int) $r['id'] ?>" class="btn-link">Edit</a>
                  <form method="post" action="experience.php" onsubmit="return confirm('Delete this experience entry?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
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