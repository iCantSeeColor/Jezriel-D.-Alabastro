<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$adminTitle = 'Skills';
$errors     = [];
$formError  = '';
$editId     = 0;
$data       = ['name' => '', 'category' => '', 'level' => '50', 'note' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please reload the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int) ($_POST['id'] ?? 0);

        // ----- DELETE -----
        if ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare('DELETE FROM skills WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $_SESSION['admin_flash'] = 'Skill deleted.';
            header('Location: skills.php');
            exit;
        }

        // ----- CREATE / UPDATE -----
        if ($action === 'save') {
            foreach (['name', 'category', 'level', 'note'] as $f) {
                $data[$f] = trim((string) ($_POST[$f] ?? ''));
            }

            // Server-side validation
            if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 80) {
                $errors['name'] = 'Skill name must be 2 to 80 characters.';
            }
            if (mb_strlen($data['category']) < 2 || mb_strlen($data['category']) > 50) {
                $errors['category'] = 'Category must be 2 to 50 characters.';
            }
            $level = filter_var($data['level'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
            if ($level === false) {
                $errors['level'] = 'Level must be a whole number from 0 to 100.';
            }
            if (mb_strlen($data['note']) > 150) {
                $errors['note'] = 'Note must be 150 characters or fewer.';
            }

            if (!$errors) {
                try {
                    if ($id > 0) {
                        $stmt = $pdo->prepare(
                            'UPDATE skills SET name = :name, category = :category, level = :level, note = :note
                             WHERE id = :id'
                        );
                        $stmt->execute([
                            ':name' => $data['name'], ':category' => $data['category'],
                            ':level' => $level, ':note' => $data['note'] !== '' ? $data['note'] : null,
                            ':id' => $id,
                        ]);
                        $_SESSION['admin_flash'] = 'Skill updated.';
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO skills (name, category, level, note)
                             VALUES (:name, :category, :level, :note)'
                        );
                        $stmt->execute([
                            ':name' => $data['name'], ':category' => $data['category'],
                            ':level' => $level, ':note' => $data['note'] !== '' ? $data['note'] : null,
                        ]);
                        $_SESSION['admin_flash'] = 'Skill added.';
                    }
                    header('Location: skills.php'); // redirect so a refresh does not resubmit
                    exit;
                } catch (PDOException $ex) {
                    error_log('Skill save failed: ' . $ex->getMessage());
                    $formError = 'The skill could not be saved. Please try again.';
                }
            }
            $editId = $id; // validation failed: stay in the same mode
        }
    }

} elseif (isset($_GET['edit'])) {
    // Load an existing skill into the form.
    $stmt = $pdo->prepare('SELECT * FROM skills WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $editId = (int) $row['id'];
        $data   = [
            'name'     => $row['name'],
            'category' => $row['category'],
            'level'    => (string) $row['level'],
            'note'     => (string) $row['note'],
        ];
    }
}

$flash      = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

$skills     = $pdo->query('SELECT * FROM skills ORDER BY category, name')->fetchAll();
$categories = $pdo->query('SELECT DISTINCT category FROM skills ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/../includes/admin_header.php';
?>

    <h1>Skills</h1>
    <p>Add, edit, delete and categorize the skills shown on your public Skills page.</p>

    <?php if ($flash): ?>
      <div class="alert success" role="status"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($formError): ?>
      <div class="alert" role="alert"><?= e($formError) ?></div>
    <?php endif; ?>

    <section class="panel">
      <h2><?= $editId ? 'Edit skill' : 'Add a new skill' ?></h2>

      <form method="post" action="skills.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $editId ?>">

        <div class="form-grid">
          <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
            <label for="name">Skill name</label>
            <input type="text" id="name" name="name" value="<?= e($data['name']) ?>" required>
            <?php if (isset($errors['name'])): ?><p class="err"><?= e($errors['name']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['category']) ? ' has-error' : '' ?>">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" value="<?= e($data['category']) ?>" list="categoryList" required>
            <datalist id="categoryList">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>">
              <?php endforeach; ?>
            </datalist>
            <?php if (isset($errors['category'])): ?><p class="err"><?= e($errors['category']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['level']) ? ' has-error' : '' ?>">
            <label for="level">Level (0 to 100)</label>
            <input type="number" id="level" name="level" min="0" max="100" value="<?= e($data['level']) ?>" required>
            <?php if (isset($errors['level'])): ?><p class="err"><?= e($errors['level']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['note']) ? ' has-error' : '' ?>">
            <label for="note">Short note (optional)</label>
            <input type="text" id="note" name="note" value="<?= e($data['note']) ?>">
            <?php if (isset($errors['note'])): ?><p class="err"><?= e($errors['note']) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-admin btn-small"><?= $editId ? 'Update skill' : 'Add skill' ?></button>
          <?php if ($editId): ?>
            <a href="skills.php" class="btn-link">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <section class="panel">
      <h2>All skills (<?= count($skills) ?>)</h2>
      <?php if (!$skills): ?>
        <p>No skills yet. Add your first one above.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>Name</th><th>Category</th><th>Level</th><th>Note</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($skills as $s): ?>
            <tr>
              <td><?= e($s['name']) ?></td>
              <td><?= e($s['category']) ?></td>
              <td><?= (int) $s['level'] ?>%</td>
              <td><?= e($s['note']) ?></td>
              <td>
                <div class="row-actions">
                  <a href="skills.php?edit=<?= (int) $s['id'] ?>" class="btn-link">Edit</a>
                  <form method="post" action="skills.php" onsubmit="return confirm('Delete this skill?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
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