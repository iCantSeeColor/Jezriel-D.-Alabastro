<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminTitle = 'Education';
$errors     = [];
$formError  = '';
$editId     = 0;
$data       = ['degree' => '', 'school' => '', 'period_label' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please reload the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int) ($_POST['id'] ?? 0);

        // ----- DELETE -----
        if ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare('DELETE FROM education WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $_SESSION['admin_flash'] = 'Education record deleted.';
            header('Location: education.php');
            exit;
        }

        // ----- CREATE / UPDATE -----
        if ($action === 'save') {
            foreach (array_keys($data) as $f) {
                $data[$f] = trim((string) ($_POST[$f] ?? ''));
            }

            // Server-side validation
            if (mb_strlen($data['degree']) < 2 || mb_strlen($data['degree']) > 150) {
                $errors['degree'] = 'Degree or title must be 2 to 150 characters.';
            }
            if (mb_strlen($data['school']) < 2 || mb_strlen($data['school']) > 150) {
                $errors['school'] = 'School must be 2 to 150 characters.';
            }
            if (mb_strlen($data['period_label']) < 1 || mb_strlen($data['period_label']) > 50) {
                $errors['period_label'] = 'Period must be 1 to 50 characters (example: 2023 - Present).';
            }
            if (mb_strlen($data['description']) > 1000) {
                $errors['description'] = 'Description must be 1000 characters or fewer.';
            }

            if (!$errors) {
                try {
                    $params = [
                        ':degree'       => $data['degree'],
                        ':school'       => $data['school'],
                        ':period_label' => $data['period_label'],
                        ':description'  => $data['description'] !== '' ? $data['description'] : null,
                    ];

                    if ($id > 0) {
                        $stmt = $pdo->prepare(
                            'UPDATE education SET degree = :degree, school = :school,
                                period_label = :period_label, description = :description
                             WHERE id = :id'
                        );
                        $stmt->execute($params + [':id' => $id]);
                        $_SESSION['admin_flash'] = 'Education record updated.';
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO education (degree, school, period_label, description)
                             VALUES (:degree, :school, :period_label, :description)'
                        );
                        $stmt->execute($params);
                        $_SESSION['admin_flash'] = 'Education record added.';
                    }
                    header('Location: education.php'); // redirect so a refresh does not resubmit
                    exit;
                } catch (PDOException $ex) {
                    error_log('Education save failed: ' . $ex->getMessage());
                    $formError = 'The record could not be saved. Please try again.';
                }
            }
            $editId = $id; // validation failed: stay in the same mode
        }
    }

} elseif (isset($_GET['edit'])) {
    // Load an existing record into the form.
    $stmt = $pdo->prepare('SELECT * FROM education WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $editId = (int) $row['id'];
        $data   = [
            'degree'       => $row['degree'],
            'school'       => $row['school'],
            'period_label' => $row['period_label'],
            'description'  => (string) $row['description'],
        ];
    }
}

$flash   = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

$records = get_education($pdo);

require __DIR__ . '/../includes/admin_header.php';
?>

    <h1>Education</h1>
    <p>Add, edit and delete the educational background shown on your Resume and About pages.</p>

    <?php if ($flash): ?>
      <div class="alert success" role="status"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($formError): ?>
      <div class="alert" role="alert"><?= e($formError) ?></div>
    <?php endif; ?>

    <section class="panel">
      <h2><?= $editId ? 'Edit education record' : 'Add an education record' ?></h2>

      <form method="post" action="education.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $editId ?>">

        <div class="form-grid">
          <div class="field<?= isset($errors['degree']) ? ' has-error' : '' ?>">
            <label for="degree">Degree or title</label>
            <input type="text" id="degree" name="degree" value="<?= e($data['degree']) ?>" required>
            <?php if (isset($errors['degree'])): ?><p class="err"><?= e($errors['degree']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['school']) ? ' has-error' : '' ?>">
            <label for="school">School</label>
            <input type="text" id="school" name="school" value="<?= e($data['school']) ?>" required>
            <?php if (isset($errors['school'])): ?><p class="err"><?= e($errors['school']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['period_label']) ? ' has-error' : '' ?>">
            <label for="period_label">Period (example: 2023 - Present)</label>
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
          <button type="submit" class="btn-admin btn-small"><?= $editId ? 'Update record' : 'Add record' ?></button>
          <?php if ($editId): ?>
            <a href="education.php" class="btn-link">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <section class="panel">
      <h2>All education records (<?= count($records) ?>)</h2>
      <?php if (!$records): ?>
        <p>No education records yet. Add your first one above.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>Degree / title</th><th>School</th><th>Period</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
              <td><?= e($r['degree']) ?></td>
              <td><?= e($r['school']) ?></td>
              <td><?= e($r['period_label']) ?></td>
              <td>
                <div class="row-actions">
                  <a href="education.php?edit=<?= (int) $r['id'] ?>" class="btn-link">Edit</a>
                  <form method="post" action="education.php" onsubmit="return confirm('Delete this education record?');">
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