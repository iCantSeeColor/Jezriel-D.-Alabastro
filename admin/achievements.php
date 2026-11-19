<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_admin();

$adminTitle   = 'Achievements';
$errors       = [];
$formError    = '';
$editId       = 0;
$currentImage = null;
$data         = ['title' => '', 'issuer' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please reload the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id     = (int) ($_POST['id'] ?? 0);

        // ----- DELETE -----
        if ($action === 'delete' && $id > 0) {
            $stmt = $pdo->prepare('SELECT image FROM achievements WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();

            $stmt = $pdo->prepare('DELETE FROM achievements WHERE id = :id');
            $stmt->execute([':id' => $id]);

            // Remove the uploaded certificate image too (files in images/ are never deleted).
            if ($row) {
                delete_uploaded_image($row['image']);
            }
            $_SESSION['admin_flash'] = 'Achievement deleted.';
            header('Location: achievements.php');
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
            if (mb_strlen($data['issuer']) > 150) {
                $errors['issuer'] = 'Issuer must be 150 characters or fewer.';
            }
            if (mb_strlen($data['description']) > 1000) {
                $errors['description'] = 'Description must be 1000 characters or fewer.';
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
                    $params = [
                        ':title'       => $data['title'],
                        ':issuer'      => $data['issuer'] !== '' ? $data['issuer'] : null,
                        ':description' => $data['description'] !== '' ? $data['description'] : null,
                    ];

                    if ($id > 0) {
                        $stmt = $pdo->prepare('SELECT image FROM achievements WHERE id = :id');
                        $stmt->execute([':id' => $id]);
                        $old = $stmt->fetch();

                        if (!$old) {
                            $formError = 'That achievement no longer exists.';
                            delete_uploaded_image($newImage);
                        } else {
                            $stmt = $pdo->prepare(
                                'UPDATE achievements SET title = :title, issuer = :issuer,
                                    description = :description, image = :image
                                 WHERE id = :id'
                            );
                            $stmt->execute($params + [':image' => $newImage ?? $old['image'], ':id' => $id]);
                            if ($newImage !== null) {
                                delete_uploaded_image($old['image']);
                            }
                            $_SESSION['admin_flash'] = 'Achievement updated.';
                            header('Location: achievements.php');
                            exit;
                        }
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO achievements (title, issuer, description, image)
                             VALUES (:title, :issuer, :description, :image)'
                        );
                        $stmt->execute($params + [':image' => $newImage]);
                        $_SESSION['admin_flash'] = 'Achievement added.';
                        header('Location: achievements.php');
                        exit;
                    }
                } catch (PDOException $ex) {
                    error_log('Achievement save failed: ' . $ex->getMessage());
                    delete_uploaded_image($newImage); // do not leave an orphan file behind
                    $formError = 'The achievement could not be saved. Please try again.';
                }
            }
            $editId = $id; // validation failed: stay in the same mode
        }
    }

} elseif (isset($_GET['edit'])) {
    // Load an existing achievement into the form.
    $stmt = $pdo->prepare('SELECT * FROM achievements WHERE id = :id');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $editId       = (int) $row['id'];
        $currentImage = $row['image'];
        $data = [
            'title'       => $row['title'],
            'issuer'      => (string) $row['issuer'],
            'description' => (string) $row['description'],
        ];
    }
}

// When the form is redisplayed after an error in edit mode, show the stored image.
if ($editId > 0 && $currentImage === null) {
    $stmt = $pdo->prepare('SELECT image FROM achievements WHERE id = :id');
    $stmt->execute([':id' => $editId]);
    $currentImage = $stmt->fetchColumn() ?: null;
}

$flash   = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

$records = get_achievements($pdo);

require __DIR__ . '/../includes/admin_header.php';
?>

    <h1>Achievements</h1>
    <p>Add, edit and delete the certificates, awards and training shown on your Resume page.</p>

    <?php if ($flash): ?>
      <div class="alert success" role="status"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($formError): ?>
      <div class="alert" role="alert"><?= e($formError) ?></div>
    <?php endif; ?>

    <section class="panel">
      <h2><?= $editId ? 'Edit achievement' : 'Add an achievement' ?></h2>

      <form method="post" action="achievements.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int) $editId ?>">

        <div class="form-grid">
          <div class="field<?= isset($errors['title']) ? ' has-error' : '' ?>">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?= e($data['title']) ?>" required>
            <?php if (isset($errors['title'])): ?><p class="err"><?= e($errors['title']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['issuer']) ? ' has-error' : '' ?>">
            <label for="issuer">Issued by (optional)</label>
            <input type="text" id="issuer" name="issuer" value="<?= e($data['issuer']) ?>">
            <?php if (isset($errors['issuer'])): ?><p class="err"><?= e($errors['issuer']) ?></p><?php endif; ?>
          </div>
        </div>

        <div class="field<?= isset($errors['description']) ? ' has-error' : '' ?>">
          <label for="description">Description (optional)</label>
          <textarea id="description" name="description" rows="4"><?= e($data['description']) ?></textarea>
          <?php if (isset($errors['description'])): ?><p class="err"><?= e($errors['description']) ?></p><?php endif; ?>
        </div>

        <div class="field<?= isset($errors['image']) ? ' has-error' : '' ?>">
          <label for="image">Certificate image (optional; JPG, PNG, WEBP or GIF, max 2 MB)<?= $editId ? ' - leave empty to keep the current one' : '' ?></label>
          <?php if ($currentImage): ?>
            <img src="../<?= e($currentImage) ?>" alt="Current certificate image" class="img-preview">
          <?php endif; ?>
          <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
          <?php if (isset($errors['image'])): ?><p class="err"><?= e($errors['image']) ?></p><?php endif; ?>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-admin btn-small"><?= $editId ? 'Update achievement' : 'Add achievement' ?></button>
          <?php if ($editId): ?>
            <a href="achievements.php" class="btn-link">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <section class="panel">
      <h2>All achievements (<?= count($records) ?>)</h2>
      <?php if (!$records): ?>
        <p>No achievements yet. Add your first one above.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="data">
          <thead>
            <tr><th>Image</th><th>Title</th><th>Issued by</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
              <td>
                <?php if (!empty($r['image'])): ?>
                  <img src="../<?= e($r['image']) ?>" alt="" class="thumb">
                <?php endif; ?>
              </td>
              <td><?= e($r['title']) ?></td>
              <td><?= e($r['issuer']) ?></td>
              <td>
                <div class="row-actions">
                  <a href="achievements.php?edit=<?= (int) $r['id'] ?>" class="btn-link">Edit</a>
                  <form method="post" action="achievements.php" onsubmit="return confirm('Delete this achievement?');">
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