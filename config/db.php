<?php
/**
 * Database connection.
 *
 * Reads credentials from environment variables (set these in your
 * hosting platform's dashboard, e.g. Railway/Render "Variables" tab).
 * Falls back to default XAMPP values when the env vars aren't set,
 * so this file works unchanged on your local machine too.
 */

   $DB_HOST = 'sql102.infinityfree.com';
   $DB_USER = 'if0_43155635';
   $DB_PASS = 'gvlDVukvCBt32';
   $DB_NAME = 'if0_43155635_cso_mis';
   $DB_PORT = 3306;

// Local Docker stack (htdocs/docker-compose.yml sets DB_HOST=db for the web container).
// Uses the database imported through http://localhost/phpmyadmin. The InfinityFree
// server does not set this variable, so it keeps using the values above.
$__docker = getenv('DB_HOST') === 'db';
if ($__docker) {
    $DB_HOST = '127.0.0.1';
    $DB_USER = 'root';
    $DB_PASS = '';
    $DB_NAME = 'cso_mis';
}

mysqli_report(MYSQLI_REPORT_OFF); // we handle errors ourselves, no uncaught warnings

// Safety net: on a real server (not your own computer) the app will not connect as 'root' with no
// password. That combination means the DB_* variables were never set, and it is how databases get stolen.
$__local = function_exists('cso_is_local') ? cso_is_local()
         : (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true) && empty($_SERVER['HTTP_X_FORWARDED_FOR']));
// Docker requests arrive from the gateway address (172.18.0.1), not 127.0.0.1, so they are exempted by
// the server-side variable above. A visitor cannot set it, and the stack only listens on 127.0.0.1.
if (!$__local && !$__docker && PHP_SAPI !== 'cli' && $DB_USER === 'root' && $DB_PASS === '') {
    http_response_code(500);
    error_log('CSO-MIS: refusing to connect as root with an empty password. Set DB_HOST, DB_USER, DB_PASS and DB_NAME.');
    exit('The site is not configured yet.');
}

$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

if ($conn->connect_error) {
    http_response_code(500);
    $error_detail = $conn->connect_error;
    require_once __DIR__ . '/../includes/error_page.php';
    render_error_page(
        'We can\'t reach the database right now',
        'This usually means MySQL isn\'t running, or the credentials in config/db.php don\'t match your setup. Please check XAMPP and try again.',
        $error_detail
    );
    exit;
}

$conn->set_charset('utf8mb4');

// ---------------------------------------------------------------
// Self-healing schema check. If this database was created from an
// older version of cso_mis_full.sql, the columns/tables below won't exist yet and every
// image-related query would fail. This adds anything missing,
// automatically, on the very next page load — no manual SQL needed.
// It's cheap after the first run: a single lookup confirms the
// schema is current and skips everything else.
// ---------------------------------------------------------------
require_once __DIR__ . '/../includes/schema_check.php';
cso_mis_ensure_schema($conn);

// Checked here, before any page starts its output, because a remembered login has to set cookies.
if (function_exists('is_logged_in')) is_logged_in();
