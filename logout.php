<?php
require_once __DIR__ . '/includes/functions.php';

// The remember cookie goes first: otherwise loading the database below would sign them straight back in.
$remembered = remember_selector();
if (isset($_COOKIE[REMEMBER_COOKIE])) {
    unset($_COOKIE[REMEMBER_COOKIE]);
    remember_set_cookie('', time() - 3600);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
}
session_destroy();

// Back to the public Home page (the site's landing page). Not redirect_home():
// that exits at once, and the remember token below still has to be deleted.
header('Location: ' . BASE_URL . '/home.php');
if ($remembered !== '') {
    require_once __DIR__ . '/config/db.php';
    remember_delete_token($remembered);
}
exit;
