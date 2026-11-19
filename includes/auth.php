<?php
// Administrator session helpers.
require_once __DIR__ . '/security.php';

const ADMIN_IDLE_TIMEOUT = 1800; // seconds (30 minutes)
const LOGIN_MAX_ATTEMPTS = 5;    // failed logins allowed per IP
const LOGIN_LOCK_MINUTES = 15;   // how long the lock lasts

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

// Fully end the session and delete its cookie.
function destroy_admin_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// Put this at the top of every admin page.
function require_admin(): void
{
    // Stop the browser from caching admin pages (Back button after logout shows nothing).
    header('Cache-Control: no-store, no-cache, must-revalidate');

    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }

    // Log out admins who have been idle for too long.
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > ADMIN_IDLE_TIMEOUT) {
        destroy_admin_session();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// ----- Protection against repeated login attempts -----

// How many failed logins this IP made recently.
// (LOGIN_LOCK_MINUTES is a fixed constant, never user input, so it is safe inside the SQL.)
function login_failed_count(PDO $pdo, string $ip): int
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = :ip
           AND attempted_at > (NOW() - INTERVAL ' . LOGIN_LOCK_MINUTES . ' MINUTE)'
    );
    $stmt->execute([':ip' => $ip]);
    return (int) $stmt->fetchColumn();
}

function login_is_locked(PDO $pdo, string $ip): bool
{
    return login_failed_count($pdo, $ip) >= LOGIN_MAX_ATTEMPTS;
}

function record_failed_login(PDO $pdo, string $ip, string $username): void
{
    // Keep the table small: remove records older than one day.
    $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');

    $stmt = $pdo->prepare('INSERT INTO login_attempts (ip_address, username) VALUES (:ip, :u)');
    $stmt->execute([':ip' => $ip, ':u' => mb_substr($username, 0, 50)]);
}

function clear_failed_logins(PDO $pdo, string $ip): void
{
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = :ip');
    $stmt->execute([':ip' => $ip]);
}