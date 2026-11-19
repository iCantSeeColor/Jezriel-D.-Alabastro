<?php
require __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/security.php';

$pageTitle = 'Contact';
$errors    = [];
$formError = '';
$old       = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        // 1. CSRF check failed
        $formError = 'Your session expired. Please reload the page and try again.';

    } elseif (!empty($_POST['website'])) {
        // 2. Honeypot: real visitors never see or fill this field, so it is a bot.
        //    Pretend success so the bot learns nothing.
        $_SESSION['flash'] = 'Thank you! Your message has been sent.';
        header('Location: contact.php');
        exit;

    } else {
        foreach (array_keys($old) as $field) {
            $old[$field] = trim((string) ($_POST[$field] ?? ''));
        }

        // 3. Server-side validation
        if (mb_strlen($old['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters long.';
        } elseif (mb_strlen($old['name']) > 100 || !preg_match('/^[\p{L}.\'\- ]+$/u', $old['name'])) {
            $errors['name'] = 'Name should contain letters only (max 100 characters).';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 100) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (mb_strlen($old['subject']) < 3 || mb_strlen($old['subject']) > 150) {
            $errors['subject'] = 'Subject must be 3 to 150 characters long.';
        }
        if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 2000) {
            $errors['message'] = 'Message must be 10 to 2000 characters long.';
        }

        // 4. Rate limit: at most 3 messages per IP every 10 minutes
        if (!$errors) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM messages
                 WHERE ip_address = :ip AND created_at > (NOW() - INTERVAL 10 MINUTE)'
            );
            $stmt->execute([':ip' => client_ip()]);
            if ((int) $stmt->fetchColumn() >= 3) {
                $formError = 'Too many messages in a short time. Please try again later.';
            }
        }

        // 5. Save with a prepared statement (SQL injection protection)
        if (!$errors && $formError === '') {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO messages (name, email, subject, message, ip_address)
                     VALUES (:name, :email, :subject, :message, :ip)'
                );
                $stmt->execute([
                    ':name'    => $old['name'],
                    ':email'   => $old['email'],
                    ':subject' => $old['subject'],
                    ':message' => $old['message'],
                    ':ip'      => client_ip(),
                ]);
                $_SESSION['flash'] = 'Thank you, ' . $old['name'] . '! Your message has been sent. I will get back to you soon.';
                header('Location: contact.php'); // redirect so a refresh does not resend the form
                exit;
            } catch (PDOException $ex) {
                error_log('Contact form insert failed: ' . $ex->getMessage());
                $formError = 'Sorry, your message could not be sent. Please try again later.';
            }
        }
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require __DIR__ . '/includes/header.php';
?>

  <header class="page-head reveal">
    <span class="eyebrow">Contact</span>
    <h1>Let's get in touch</h1>
    <p class="page-sub">Have a question, a project idea, or just want to say hello? Fill in the form below or reach me through my email and social links.</p>
  </header>

  <section class="section">
    <div class="contact-grid">

      <aside class="contact-info reveal">
        <h2 class="contact-title">Contact details</h2>

        <ul class="info-list">
          <li class="info-item">
            <span class="info-icon">✉️</span>
            <div>
              <strong>Email</strong>
              <a href="mailto:<?= e($profile['email'] ?? '') ?>"><?= e($profile['email'] ?? '') ?></a>
            </div>
          </li>
          <?php if (!empty($profile['phone'])): ?>
          <li class="info-item">
            <span class="info-icon">📞</span>
            <div>
              <strong>Phone</strong>
              <span><?= e($profile['phone']) ?></span>
            </div>
          </li>
          <?php endif; ?>
          <li class="info-item">
            <span class="info-icon">📍</span>
            <div>
              <strong>Location</strong>
              <span><?= e($profile['location'] ?? '') ?></span>
            </div>
          </li>
          <li class="info-item">
            <span class="info-icon">🎓</span>
            <div>
              <strong>School</strong>
              <span><?= e($profile['school'] ?? '') ?></span>
            </div>
          </li>
        </ul>

        <h2 class="contact-title">Follow me</h2>
        <ul class="social-links">
          <li><a class="social-link" href="<?= e(safe_url($profile['github_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer" aria-label="GitHub">GH</a></li>
          <li><a class="social-link" href="<?= e(safe_url($profile['facebook_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">FB</a></li>
          <li><a class="social-link" href="<?= e(safe_url($profile['linkedin_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">IN</a></li>
          <li><a class="social-link" href="<?= e(safe_url($profile['instagram_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">IG</a></li>
        </ul>

        <div class="note-box">
          <strong>Quick reply</strong>
          <p>I usually respond to messages within 1–2 days. For school-related concerns, please include your subject or section.</p>
        </div>
      </aside>

      <section class="card reveal">
        <h2 class="contact-title">Send me a message</h2>

        <form id="contactForm" class="contact-form" method="post" action="contact.php" novalidate>
          <?= csrf_field() ?>

          <!-- Honeypot: hidden from people, bots tend to fill it in -->
          <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <label for="website">Leave this field empty</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
          </div>

          <div class="form-row">
            <div class="form-group<?= isset($errors['name']) ? ' error' : '' ?>">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" placeholder="e.g. Juan Dela Cruz" autocomplete="name">
              <p class="error-msg" id="nameError" aria-live="polite"><?= e($errors['name'] ?? '') ?></p>
            </div>

            <div class="form-group<?= isset($errors['email']) ? ' error' : '' ?>">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" placeholder="you@example.com" autocomplete="email">
              <p class="error-msg" id="emailError" aria-live="polite"><?= e($errors['email'] ?? '') ?></p>
            </div>
          </div>

          <div class="form-group<?= isset($errors['subject']) ? ' error' : '' ?>">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?= e($old['subject']) ?>" placeholder="What is this about?">
            <p class="error-msg" id="subjectError" aria-live="polite"><?= e($errors['subject'] ?? '') ?></p>
          </div>

          <div class="form-group<?= isset($errors['message']) ? ' error' : '' ?>">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="6" placeholder="Write your message here..."><?= e($old['message']) ?></textarea>
            <p class="error-msg" id="messageError" aria-live="polite"><?= e($errors['message'] ?? '') ?></p>
          </div>

          <button type="submit" class="btn btn-primary">Send Message →</button>
          <p class="form-status<?= $flash ? ' success' : (($formError || $errors) ? ' error' : '') ?>" id="formStatus" role="status" aria-live="polite"><?php
            if ($flash) {
                echo '✓ ' . e($flash);
            } elseif ($formError) {
                echo '⚠ ' . e($formError);
            } elseif ($errors) {
                echo '⚠ Please fix the highlighted fields and try again.';
            }
          ?></p>
        </form>
      </section>

    </div>
  </section>

<?php require __DIR__ . '/includes/footer.php'; ?>