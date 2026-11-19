<?php
require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';
require_admin();

$adminTitle = 'Profile';
$profile    = get_profile($pdo);
$errors     = [];
$formError  = '';

$fields = [
    'full_name', 'headline', 'introduction', 'biography', 'email', 'phone',
    'location', 'school', 'github_url', 'facebook_url', 'linkedin_url', 'instagram_url',
];

// Start with the values currently in the database.
$data = [];
foreach ($fields as $f) {
    $data[$f] = (string) ($profile[$f] ?? '');
}

if ($profile && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $formError = 'Your session expired. Please reload the page and try again.';
    } else {
        foreach ($fields as $f) {
            $data[$f] = trim((string) ($_POST[$f] ?? ''));
        }

        // ----- Server-side validation -----
        $len = fn(string $v): int => mb_strlen($v);

        if ($len($data['full_name']) < 2 || $len($data['full_name']) > 100) {
            $errors['full_name'] = 'Full name must be 2 to 100 characters.';
        }
        if ($len($data['headline']) < 3 || $len($data['headline']) > 150) {
            $errors['headline'] = 'Headline must be 3 to 150 characters.';
        }
        if ($len($data['introduction']) < 10 || $len($data['introduction']) > 1000) {
            $errors['introduction'] = 'Introduction must be 10 to 1000 characters.';
        }
        if ($len($data['biography']) < 10 || $len($data['biography']) > 3000) {
            $errors['biography'] = 'Biography must be 10 to 3000 characters.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || $len($data['email']) > 100) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if ($data['phone'] !== '' && (!preg_match('/^[0-9+\-() ]+$/', $data['phone']) || $len($data['phone']) > 30)) {
            $errors['phone'] = 'Phone may contain only digits, spaces and + - ( ), up to 30 characters.';
        }
        if ($len($data['location']) < 2 || $len($data['location']) > 150) {
            $errors['location'] = 'Location must be 2 to 150 characters.';
        }
        if ($len($data['school']) > 150) {
            $errors['school'] = 'School must be 150 characters or fewer.';
        }
        foreach (['github_url', 'facebook_url', 'linkedin_url', 'instagram_url'] as $f) {
            if ($data[$f] !== '' && (
                !preg_match('#^https?://#i', $data[$f])
                || !filter_var($data[$f], FILTER_VALIDATE_URL)
                || $len($data[$f]) > 255
            )) {
                $errors[$f] = 'Enter a full link starting with http:// or https://';
            }
        }

        // ----- Secure image upload (only when the text fields are valid and a file was chosen) -----
        $newImage = null;
        if (!$errors && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = save_uploaded_image($_FILES['profile_image']);
            if (isset($result['error'])) {
                $errors['profile_image'] = $result['error'];
            } else {
                $newImage = $result['path'];
            }
        }

        // ----- Save with a prepared statement -----
        if (!$errors) {
            try {
                $stmt = $pdo->prepare(
                    'UPDATE profile SET
                        full_name = :full_name, headline = :headline,
                        introduction = :introduction, biography = :biography,
                        email = :email, phone = :phone, location = :location,
                        school = :school, github_url = :github_url,
                        facebook_url = :facebook_url, linkedin_url = :linkedin_url,
                        instagram_url = :instagram_url, profile_image = :profile_image
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':full_name'     => $data['full_name'],
                    ':headline'      => $data['headline'],
                    ':introduction'  => $data['introduction'],
                    ':biography'     => $data['biography'],
                    ':email'         => $data['email'],
                    ':phone'         => $data['phone'] !== '' ? $data['phone'] : null,
                    ':location'      => $data['location'],
                    ':school'        => $data['school'] !== '' ? $data['school'] : null,
                    ':github_url'    => $data['github_url'] !== '' ? $data['github_url'] : null,
                    ':facebook_url'  => $data['facebook_url'] !== '' ? $data['facebook_url'] : null,
                    ':linkedin_url'  => $data['linkedin_url'] !== '' ? $data['linkedin_url'] : null,
                    ':instagram_url' => $data['instagram_url'] !== '' ? $data['instagram_url'] : null,
                    ':profile_image' => $newImage ?? ($profile['profile_image'] ?? null),
                    ':id'            => $profile['id'],
                ]);

                // The new photo is saved: remove the old uploaded photo (if it was one of our uploads).
                if ($newImage !== null) {
                    delete_uploaded_image($profile['profile_image'] ?? null);
                }

                $_SESSION['admin_flash'] = 'Profile updated successfully.';
                header('Location: profile.php'); // redirect so a refresh does not resubmit
                exit;
            } catch (PDOException $ex) {
                error_log('Profile update failed: ' . $ex->getMessage());
                if ($newImage !== null) {
                    delete_uploaded_image($newImage); // do not leave an orphan file behind
                }
                $formError = 'The profile could not be saved. Please try again.';
            }
        }
    }
}

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);

// Draws one form field with its error message.
$field = function (string $name, string $label, string $type = 'text', bool $textarea = false, int $rows = 4) use (&$data, &$errors): void {
    $err = $errors[$name] ?? '';
    echo '<div class="field' . ($err ? ' has-error' : '') . '">';
    echo '<label for="' . e($name) . '">' . e($label) . '</label>';
    if ($textarea) {
        echo '<textarea id="' . e($name) . '" name="' . e($name) . '" rows="' . $rows . '">' . e($data[$name]) . '</textarea>';
    } else {
        echo '<input type="' . e($type) . '" id="' . e($name) . '" name="' . e($name) . '" value="' . e($data[$name]) . '">';
    }
    if ($err) {
        echo '<p class="err">' . e($err) . '</p>';
    }
    echo '</div>';
};

require __DIR__ . '/../includes/admin_header.php';
?>

    <h1>Profile</h1>
    <p>Everything here appears on your public website.</p>

    <?php if ($flash): ?>
      <div class="alert success" role="status"><?= e($flash) ?></div>
    <?php endif; ?>
    <?php if ($formError): ?>
      <div class="alert" role="alert"><?= e($formError) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert" role="alert">Please fix the highlighted fields.</div>
    <?php endif; ?>

    <?php if (!$profile): ?>
      <div class="alert" role="alert">Profile record not found. Re-import database/portfolio.sql.</div>
    <?php else: ?>
    <form method="post" action="profile.php" class="panel" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <h2>Profile photo</h2>
      <?php if (!empty($profile['profile_image'])): ?>
        <img src="../<?= e($profile['profile_image']) ?>" alt="Current profile photo" class="avatar-preview">
      <?php endif; ?>
      <div class="field<?= isset($errors['profile_image']) ? ' has-error' : '' ?>">
        <label for="profile_image">Upload a new photo (JPG, PNG, WEBP or GIF, max 2 MB)</label>
        <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/webp,image/gif">
        <?php if (isset($errors['profile_image'])): ?><p class="err"><?= e($errors['profile_image']) ?></p><?php endif; ?>
      </div>

      <h2>Personal information</h2>
      <div class="form-grid">
        <?php $field('full_name', 'Full name'); ?>
        <?php $field('headline', 'Headline (short title)'); ?>
      </div>
      <?php $field('introduction', 'Introduction (shown on the home page)', 'text', true, 4); ?>
      <?php $field('biography', 'Biography (shown on the About page)', 'text', true, 7); ?>

      <h2>Contact details</h2>
      <div class="form-grid">
        <?php $field('email', 'Email', 'email'); ?>
        <?php $field('phone', 'Phone (optional)'); ?>
        <?php $field('location', 'Location'); ?>
        <?php $field('school', 'School (optional)'); ?>
      </div>

      <h2>Social links</h2>
      <div class="form-grid">
        <?php $field('github_url', 'GitHub link', 'url'); ?>
        <?php $field('facebook_url', 'Facebook link', 'url'); ?>
        <?php $field('linkedin_url', 'LinkedIn link', 'url'); ?>
        <?php $field('instagram_url', 'Instagram link', 'url'); ?>
      </div>

      <button type="submit" class="btn-admin btn-small">Save changes</button>
    </form>
    <?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>