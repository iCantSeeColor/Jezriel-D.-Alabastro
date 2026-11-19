<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_admin();

$adminTitle   = 'Projects';
$errors       = [];
$formError    = '';
$editId       = 0;
$currentImage = null;
$data         = ['title' => '', 'category' => '', 'project_type' => '', 'description' => '', 'tags' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please reload the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int) ($_POST['id'] ?? 0);

        // ----- DELETE -----
        if ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare('SELECT image FROM projects WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();

            $stmt = $pdo->prepare('DELETE FROM projects WHERE id = :id');
            $stmt->execute([':id' => $id]);

            // Remove the uploaded image too (files in images/ are never deleted).
            if ($row) {
                delete_uploaded_image($row['image']);
            }
            $_SESSION['admin_flash'] = 'Project deleted.';
            header('Location: projects.php');
            exit;
        }

        // ----- CREATE / UPDATE -----
        if ($action === 'save') {
            foreach (array_keys($data) as $f) {
                $data[$f] = trim((string) ($_POST[$f] ?? ''));
            }
            $data['category'] = strtolower($data['category']);

            // Tags: tidy "a, b,,c" into "a,b,c".
            $tagList      = array_filter(array_map('trim', explode(',', $data['tags'])), 'strlen');
            $data['tags'] = implode(',', $tagList);

            // Server-side validation
            if (mb_strlen($data['title']) < 2 || mb_strlen($data['title']) > 120) {
                $errors['title'] = 'Title must be 2 to 120 characters.';
            }
            if (!preg_match('/^[a-z0-9_-]{2,30}$/', $data['category'])) {
                $errors['category'] = 'Category: 2 to 30 lowercase letters, numbers or dashes (example: java).';
            }
            if (mb_strlen($data['project_type']) < 2 || mb_strlen($data['project_type']) > 60) {
                $errors['project_type'] = 'Project type must be 2 to 60 characters.';
            }
            if (mb_strlen($data['description']) < 10 || mb_strlen($data['description']) > 2000) {
                $errors['description'] = 'Description must be 10 to 2000 characters.';
            }
            if (mb_strlen($data['tags']) > 255) {
                $errors['tags'] = 'Tags must be 255 characters or fewer in total.';
            }

            // Secure image upload (only when the text fields are valid and a file was chosen)
            $newImage = null;
            if (!$errors && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $result = save_uploaded_image($_FILES['image']);
                if (isset($result['error'])) {
                    $errors['image'] = $result['error'];
                } else {
                    $newImage = $result['path'];
                }
            }

            // Save with prepared statements
            if (!$errors) {
                try {
                    if ($id > 0) {
                        $stmt = $pdo->prepare('SELECT image FROM projects WHERE id = :id');
                        $stmt->execute([':id' => $id]);
                        $old = $stmt->fetch();

                        if (!$old) {
                            $formError = 'That project no longer exists.';
                            delete_uploaded_image($newImage);
                        } else {
                            $stmt = $pdo->prepare(
                                'UPDATE projects SET title = :title, category = :category,
                                    project_type = :ptype, description = :description,
                                    tags = :tags, image = :image
                                 WHERE id = :id'
                            );
                            $stmt->execute([
                                ':title' => $data['title'], ':category' => $data['category'],
                                ':ptype' => $data['project_type'], ':description' => $data['description'],
                                ':tags'  => $data['tags'] !== '' ? $data['tags'] : null,
                                ':image' => $newImage ?? $old['image'],
                                ':id'    => $id,
                            ]);
                            if ($newImage !== null) {
                                delete_uploaded_image($old['image']);
                            }
                            $_SESSION['admin_flash'] = 'Project updated.';
                            header('Location: projects.php');
                            exit;
                        }
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO projects (title, category, project_type, description, tags, image)
                             VALUES (:title, :category, :ptype, :description, :tags, :image)'
                        );
                        $stmt->execute([
                            ':title' => $data['title'], ':category' => $data['category'],
                            ':ptype' => $data['project_type'], ':description' => $data['description'],
                            ':tags'  => $data['tags'] !== '' ? $data['tags'] : null,
                            ':image' => $newImage,
                        ]);
                        $_SESSION['admin_flash'] = 'Project added.';
                        header('Location: projects.php');
                        exit;
                    }
                } catch (PDOException $ex) {
                    error_log('Project save failed: ' . $ex->getMessage());
                    delete_uploaded_image($newImage); // do not leave an orphan file behind
                    $formError = 'The project could not be saved. Please try again.';
                }
            }
            $editId = $id; // validation failed: stay in the same mode
        }
    }

} elseif (isset($_GET['edit'])) {
    // Load an existing project into the form.
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $editId       = (int) $row['id'];
        $currentImage = $row['image'];
        $data = [
            'title'        => $row['title'],
            'category'     => $row['category'],
            'project_type' => $row['project_type'],
            'description'  => $row['description'],
            'tags'         => (string) $row['tags'],
        ];
    }
}

// When the form is redisplayed after an error in edit mode, show the stored image.
if ($editId > 0 && $currentImage === null) {
    $stmt = $pdo->prepare('SELECT image FROM projects WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $currentImage = $stmt->fetchColumn() ?: null;
}

$flash      = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

$projects   = $pdo->query('SELECT * FROM projects ORDER BY id ASC')->fetchAll();
$categories = $pdo->query('SELECT DISTINCT category FROM projects ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/../includes/admin_header.php';
?>

    <h1>Projects</h1>
    <p>Add, edit and delete the projects shown on your public Projects page.</p>

    <?php if ($flash): ?>
      <div class="alert success" role="status"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($formError): ?>
      <div class="alert" role="alert"><?= e($formError) ?></div>
    <?php endif; ?>

    <section class="panel">
      <h2><?= $editId ? 'Edit project' : 'Add a new project' ?></h2>

      <form method="post" action="projects.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $editId ?>">

        <div class="form-grid">
          <div class="field<?= isset($errors['title']) ? ' has-error' : '' ?>">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= e($data['title']) ?>" required>
            <?php if (isset($errors['title'])): ?><p class="err"><?= e($errors['title']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['project_type']) ? ' has-error' : '' ?>">
            <label for="project_type">Project type (example: Desktop Application)</label>
            <input type="text" id="project_type" name="project_type" value="<?= e($data['project_type']) ?>" required>
            <?php if (isset($errors['project_type'])): ?><p class="err"><?= e($errors['project_type']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['category']) ? ' has-error' : '' ?>">
            <label for="category">Category (used by the filter buttons)</label>
            <input type="text" id="category" name="category" value="<?= e($data['category']) ?>" list="categoryList" required>
            <datalist id="categoryList">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat) ?>">
              <?php endforeach; ?>
            </datalist>
            <?php if (isset($errors['category'])): ?><p class="err"><?= e($errors['category']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['tags']) ? ' has-error' : '' ?>">
            <label for="tags">Tags (separated by commas)</label>
            <input type="text" id="tags" name="tags" value="<?= e($data['tags']) ?>" placeholder="Java, Swing, MySQL">
            <?php if (isset($errors['tags'])): ?><p class="err"><?= e($errors['tags']) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="field<?= isset($errors['description']) ? ' has-error' : '' ?>">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="5" required><?= e($data['description']) ?></textarea>
          <?php if (isset($errors['description'])): ?><p class="err"><?= e($errors['description']) ?></p><?php endif; ?>
        </div>

        <div class="field<?= isset($errors['image']) ? ' has-error' : '' ?>">
          <label for="image">Screenshot (JPG, PNG, WEBP or GIF, max 2 MB)<?= $editId ? ' - leave empty to keep the current one' : '' ?></label>
          <?php if ($currentImage): ?>
            <img src="../<?= e($currentImage) ?>" alt="Current screenshot" class="img-preview">
          <?php endif; ?>
          <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
          <?php if (isset($errors['image'])): ?><p class="err"><?= e($errors['image']) ?></p><?php endif; ?>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-admin btn-small"><?= $editId ? 'Update project' : 'Add project' ?></button>
          <?php if ($editId): ?>
            <a href="projects.php" class="btn-link">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <section class="panel">
      <h2>All projects (<?= count($projects) ?>)</h2>
      <?php if (!$projects): ?>
        <p>No projects yet. Add your first one above.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>Image</th><th>Title</th><th>Category</th><th>Type</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($projects as $p): ?>
            <tr>
              <td>
                <?php if (!empty($p['image'])): ?>
                  <img src="../<?= e($p['image']) ?>" alt="" class="thumb">
                <?php endif; ?>
              </td>
              <td><?= e($p['title']) ?></td>
              <td><?= e($p['category']) ?></td>
              <td><?= e($p['project_type']) ?></td>
              <td>
                <div class="row-actions">
                  <a href="projects.php?edit=<?= (int) $p['id'] ?>" class="btn-link">Edit</a>
                  <form method="post" action="projects.php" onsubmit="return confirm('Delete this project?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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