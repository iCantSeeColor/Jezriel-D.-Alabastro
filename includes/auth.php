<?php
// Administrator session helpers.
require_once __DIR__ . '/security.php';

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

// Put this at the top of every admin page. Visitors who are not logged in are sent to the login page.
function require_admin(): void
{
    // Stop the browser from caching admin pages (the Back button after logout shows nothing).
    header('Cache-Control: no-store, no-cache, must-revalidate');

    if (!admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}