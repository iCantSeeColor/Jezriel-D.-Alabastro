<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_admin();

$adminTitle = 'Settings';
$settings   = get_settings($pdo);
$errors     = [];
$formError  = '';

$data = [
    'site_title'    => (string) ($settings['site_title'] ?? ''),
    'logo_text'     => (string) ($settings['logo_text'] ?? ''),
    'footer_text'   => (string) ($settings['footer_text'] ?? ''),
    'default_theme' => (string) ($settings['default_theme'] ?? 'light'),
];

if ($settings && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please reload the page and try again.';
    } else {
        foreach (array_keys($data) as $f) {
            $data[$f] = trim((string) ($_POST[$f] ?? ''));
        }
        $removeLogo = !empty($_POST['remove_logo']);

        // ----- Server-side validation -----
        if (mb_strlen($data['site_title']) < 2 || mb_strlen($data['site_title']) > 150) {
            $errors['site_title'] = 'Website title must be 2 to 150 characters.';
        }
        if (mb_strlen($data['logo_text']) < 1 || mb_strlen($data['logo_text']) > 10) {
            $errors['logo_text'] = 'Logo text must be 1 to 10 characters (example: JA).';
        }
        if (mb_strlen($data['footer_text']) < 2 || mb_strlen($data['footer_text']) > 255) {
            $errors['footer_text'] = 'Footer text must be 2 to 255 characters.';
        }
        if (!in_array($data['default_theme'], ['light', 'dark'], true)) {
            $errors['default_theme'] = 'Choose light or dark.';
        }

        // ----- Secure logo upload (only when the text fields are valid and a file was chosen) -----
        $newImage = null;
        if (!$errors && isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = save_uploaded_image($_FILES['logo_image']);
            if (isset($result['error'])) {
                $errors['logo_image'] = $result['error'];
            } else {
                $newImage = $result['path'];
            }
        }

        // ----- Save with a prepared statement -----
        if (!$errors) {
            $oldImage  = $settings['logo_image'] ?? null;
            $logoImage = $oldImage;
            if ($newImage !== null) {
                $logoImage = $newImage;   // a new logo replaces the old one
            } elseif ($removeLogo) {
                $logoImage = null;        // back to the text logo
            }

            try {
                $stmt = $pdo->prepare(
                    'UPDATE settings SET site_title = :site_title, logo_text = :logo_text,
                        logo_image = :logo_image, footer_text = :footer_text,
                        default_theme = :default_theme
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':site_title'    => $data['site_title'],
                    ':logo_text'     => $data['logo_text'],
                    ':logo_image'    => $logoImage,
                    ':footer_text'   => $data['footer_text'],
                    ':default_theme' => $data['default_theme'],
                    ':id'            => $settings['id'],
                ]);

                // The database now points to the new state: remove the old uploaded logo file.
                if (($newImage !== null || $removeLogo) && !empty($oldImage)) {
                    delete_uploaded_image($oldImage);
                }

                $_SESSION['admin_flash'] = 'Settings saved.';
                header('Location: settings.php'); // redirect so a refresh does not resubmit
                exit;
            } catch (PDOException $ex) {
                error_log('Settings update failed: ' . $ex->getMessage());
                delete_uploaded_image($newImage); // do not leave an orphan file behind
                $formError = 'The settings could not be saved. Please try again.';
            }
        }
    }
}

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

// Draws one text field with its error message.
$field = function (string $name, string $label, string $hint = '') use (&$data, &$errors): void {
    $err = $errors[$name] ?? '';
    echo '<div class="field' . ($err ? ' has-error' : '') . '">';
    echo '<label for="' . e($name) . '">' . e($label) . '</label>';
    echo '<input type="text" id="' . e($name) . '" name="' . e($name) . '" value="' . e($data[$name]) . '">';
    if ($hint !== '') {
        echo '<p class="hint">' . e($hint) . '</p>';
    }
    if ($err) {
        echo '<p class="err">' . e($err) . '</p>';
    }
    echo '</div>';
};

require __DIR__ . '/../includes/admin_header.php';
?>

    <h1>Website settings</h1>
    <p>Change the title, logo and appearance of your public website.</p>

    <?php if ($flash): ?>
      <div class="alert success" role="status"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($formError): ?>
      <div class="alert" role="alert"><?= e($formError) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert" role="alert">Please fix the highlighted fields.</div>
    <?php endif; ?>

    <?php if (!$settings): ?>
      <div class="alert" role="alert">Settings record not found. Re-import database/portfolio.sql.</div>
    <?php else: ?>
    <form method="post" action="settings.php" class="panel" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <h2>General</h2>
      <?php $field('site_title', 'Website title (shown in the browser tab)'); ?>
      <?php $field('footer_text', 'Footer text'); ?>

      <h2>Logo</h2>
      <?php $field('logo_text', 'Logo text (1 to 10 characters)', 'Shown in the sidebar when no logo image is set.'); ?>

      <div class="field<?= isset($errors['logo_image']) ? ' has-error' : '' ?>">
        <label for="logo_image">Logo image (optional; JPG, PNG, WEBP or GIF, max 2 MB)</label>
        <?php if (!empty($settings['logo_image'])): ?>
          <img src="../<?= e($settings['logo_image']) ?>" alt="Current logo" class="img-preview">
          <label class="radio"><input type="checkbox" name="remove_logo" value="1"> Remove the logo image (use the logo text instead)</label>
        <?php endif; ?>
        <input type="file" id="logo_image" name="logo_image" accept="image/jpeg,image/png,image/webp,image/gif">
        <?php if (isset($errors['logo_image'])): ?><p class="err"><?= e($errors['logo_image']) ?></p><?php endif; ?>
      </div>

      <h2>Appearance</h2>
      <div class="field<?= isset($errors['default_theme']) ? ' has-error' : '' ?>">
        <label>Default theme</label>
        <label class="radio"><input type="radio" name="default_theme" value="light"<?= $data['default_theme'] === 'light' ? ' checked' : '' ?>> Light mode</label>
        <label class="radio"><input type="radio" name="default_theme" value="dark"<?= $data['default_theme'] === 'dark' ? ' checked' : '' ?>> Dark mode</label>
        <p class="hint">Visitors can still switch with the theme button, and their own choice is remembered.</p>
        <?php if (isset($errors['default_theme'])): ?><p class="err"><?= e($errors['default_theme']) ?></p><?php endif; ?>
      </div>

      <button type="submit" class="btn-admin btn-small">Save settings</button>
    </form>
    <?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>