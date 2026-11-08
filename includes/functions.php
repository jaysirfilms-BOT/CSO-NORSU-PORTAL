<?php
/**
 * Shared helper functions: session bootstrap, auth guards, CSRF,
 * sanitization, and BASE_URL auto-detection.
 */

// ---------------------------------------------------------------
// Request helpers used by the security checks.
//  - cso_is_local(): true only for a genuine request on this same machine (XAMPP).
//    A request that came through a reverse proxy (it carries forwarding headers) is
//    never treated as local, even if the proxy itself runs on 127.0.0.1.
//    Set CSO_FORCE_PRODUCTION=1 to switch the local shortcuts off completely.
//  - cso_client_ip(): the visitor's address for rate limits. Behind a proxy (Railway,
//    Render...) REMOTE_ADDR is the proxy, so set CSO_TRUST_PROXY=1 to use the address the
//    proxy appended to X-Forwarded-For. Only turn that on when there is exactly one proxy.
//  - cso_origin(): "https://host" for links and QR codes. Set CSO_SITE_URL (for example
//    https://cso.example.com) so a forged Host header can never end up in an emailed link.
// ---------------------------------------------------------------
function cso_is_local() {
    if (in_array(strtolower((string) getenv('CSO_FORCE_PRODUCTION')), ['1', 'true', 'yes'], true)) return false;
    if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) return false;
    foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_HOST', 'HTTP_CF_CONNECTING_IP'] as $h) {
        if (!empty($_SERVER[$h])) return false;
    }
    return true;
}

function cso_client_ip() {
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    if (in_array(strtolower((string) getenv('CSO_TRUST_PROXY')), ['1', 'true', 'yes'], true) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = array_map('trim', explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']));
        $last  = end($parts);   // the entry our own proxy added; anything before it can be forged by the visitor
        if ($last !== false && filter_var($last, FILTER_VALIDATE_IP)) return $last;
    }
    return $ip;
}

function cso_origin() {
    $fixed = trim((string) getenv('CSO_SITE_URL'));
    if ($fixed !== '' && preg_match('#^https?://[A-Za-z0-9.-]+(:\d+)?$#', rtrim($fixed, '/'))) {
        return rtrim($fixed, '/');
    }
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host   = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if (!preg_match('/^[A-Za-z0-9.-]+(:\d{1,5})?$/', $host)) $host = 'localhost';   // forged or odd Host headers are dropped
    return ($https ? 'https' : 'http') . '://' . $host;
}

// ---------------------------------------------------------------
// Production hardening: never show PHP errors to visitors (they can reveal
// file paths and database details). On a local machine they stay visible.
// ---------------------------------------------------------------
$cso_is_local = cso_is_local();
if (!$cso_is_local) {
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
}

// ---------------------------------------------------------------
// Security headers, sent on every page.
//  - frame-ancestors / X-Frame-Options: nobody can put this site inside a frame (clickjacking)
//  - nosniff: browsers must not guess file types
//  - CSP: scripts, styles, fonts and images only load from this site and the few hosts listed
//  - camera is allowed for this site only (QR scanning); microphone, location etc. are off
// ---------------------------------------------------------------
if (!headers_sent()) {
    $hdr_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(self), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com data:; "
         . "img-src 'self' data: blob:; media-src 'self' blob:; connect-src 'self'; "
         . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    if ($hdr_https) header('Strict-Transport-Security: max-age=15552000');
}

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.use_strict_mode', '1');    // the server refuses session IDs it did not create
    @ini_set('session.use_only_cookies', '1');   // never accept a session ID from the URL
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,      // JavaScript cannot read the session cookie
        'secure'   => $is_https, // HTTPS only, when the site is served over HTTPS
        'samesite' => 'Lax',
    ]);
    session_start();
}

/**
 * Format a user's full name from first/middle/last/suffix, skipping
 * whichever pieces are empty. $row can be a DB row array or $_SESSION-style
 * array as long as the keys match.
 */
function full_name($row, $with_suffix = true) {
    $parts = [];
    if (!empty($row['first_name'])) $parts[] = $row['first_name'];
    if (!empty($row['middle_name'])) $parts[] = mb_substr($row['middle_name'], 0, 1) . '.';
    if (!empty($row['last_name'])) $parts[] = $row['last_name'];
    $name = implode(' ', $parts);
    if ($with_suffix && !empty($row['suffix'])) {
        $name .= ', ' . $row['suffix'];
    }
    return $name;
}

// ---------------------------------------------------------------
// BASE_URL auto-detection — works whether the app lives at
// http://localhost/CSO-MIS or at the web root http://localhost/
// ---------------------------------------------------------------
if (!defined('BASE_URL')) {
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Normalize: strip a trailing /dashboard if functions.php was required from there
    $script_dir = preg_replace('#/dashboard$#', '', $script_dir);
    $script_dir = rtrim($script_dir, '/');
    define('BASE_URL', $script_dir);
}

// ---------------------------------------------------------------
// Sanitization
// ---------------------------------------------------------------
function clean($value) {
    if ($value === null) return '';
    return trim(strip_tags($value));
}

// CSO cards carry the Student ID followed by "cso" (202403593cso). Returns the plain ID.
// Anything else, including a plain ID or a non-numeric one like ADMIN-001, comes back unchanged.
function student_id_from_card($value) {
    return preg_replace('/^(\d+)cso$/i', '$1', $value);
}

// The officer positions, in rank order. dashboard/members.php offers them as checkboxes and
// officers.php lists people in this same order. Keep each name free of commas.
function cso_position_groups() {
    return [
        'Core Officers' => ['President', 'Vice President', 'Secretary', 'Assistant Secretary', 'Treasurer', 'Auditor', 'PIO', 'CSO Comelec'],
        'Representatives' => ['4th Year Representative', '3rd Year Representative', '2nd Year Representative', '1st Year Representative'],
        'Committee Heads' => ['Documentation Head', 'Creative Head', 'Program Head', 'Marketing Head', 'Logistics Head', 'Secretariat Head'],
        'Committee Members' => ['Documentation Committee', 'Creative Committee', 'Program Committee', 'Marketing Committee', 'Logistics Committee', 'Secretariat Committee'],
    ];
}

// users.committee_role holds a person's positions as one comma-separated line. Returns them as a list.
function cso_position_list($value) {
    return array_values(array_filter(array_map('trim', explode(',', (string) $value)), 'strlen'));
}

// Where someone's highest position falls: rank 0 is President. Returns [rank, heading], or
// [PHP_INT_MAX, ''] for anyone without a listed position.
function cso_position_rank($value) {
    $held = cso_position_list($value);
    $rank = 0;
    foreach (cso_position_groups() as $group => $positions) {
        foreach ($positions as $position) {
            if (in_array($position, $held, true)) return [$rank, $group];
            $rank++;
        }
    }
    return [PHP_INT_MAX, ''];
}

// Sorts people under the position headings, highest position first. Someone with positions in
// two groups appears only under their highest one (a Secretary who is also Secretariat Head is
// listed with the core officers); anyone without a listed position goes under $rest_heading.
// Each row needs user_id and committee_role. Empty headings are left out.
function cso_group_by_position(array $people, $rest_heading) {
    $sections = [];
    $placed = [];
    foreach (cso_position_groups() as $group => $positions) {
        $sections[$group] = [];
        foreach ($positions as $position) {
            foreach ($people as $person) {
                $id = (int) $person['user_id'];
                if (!isset($placed[$id]) && in_array($position, cso_position_list($person['committee_role']), true)) {
                    $sections[$group][$id] = $person;
                    $placed[$id] = true;
                }
            }
        }
    }
    $sections[$rest_heading] = array_filter($people, function ($p) use ($placed) { return !isset($placed[(int) $p['user_id']]); });
    return array_filter($sections);
}

// ---------------------------------------------------------------
// Auth helpers
// ---------------------------------------------------------------
// "Remember me": a long-lived cookie that signs a person back in when their session ends.
// The cookie is "selector:validator". The database keeps only a hash of the validator, so a
// copy of the database cannot be used to log in. Changing a password raises token_version,
// which cancels every remembered device. Browsers cap cookies at about 400 days, so the
// date moves forward each time it is used: anyone who returns within that time stays in.
// Admins are never remembered.
const REMEMBER_COOKIE = 'cso_remember';
const REMEMBER_DAYS = 400;

function remember_set_cookie($value, $expires) {
    setcookie(REMEMBER_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => BASE_URL . '/',
        'httponly' => true,
        'secure'   => session_get_cookie_params()['secure'],
        'samesite' => 'Lax',
    ]);
}

function remember_selector() {
    return preg_match('/^([a-f0-9]{24}):[a-f0-9]{64}$/', (string) ($_COOKIE[REMEMBER_COOKIE] ?? ''), $m) ? $m[1] : '';
}

function remember_delete_token($selector) {
    global $conn;
    $del = $conn->prepare("DELETE FROM remember_tokens WHERE selector = ?");
    $del->bind_param('s', $selector);
    $del->execute();
}

/** Ends this browser's remembered login. */
function remember_forget() {
    $selector = remember_selector();
    if ($selector !== '') remember_delete_token($selector);
    if (isset($_COOKIE[REMEMBER_COOKIE])) {
        unset($_COOKIE[REMEMBER_COOKIE]);
        remember_set_cookie('', time() - 3600);
    }
}

/** Remembers this browser for the given account. Call it after a successful login. */
function remember_issue($user_id, $token_version) {
    global $conn;
    remember_forget();
    $user_id = (int) $user_id;
    $token_version = (int) $token_version;
    $old = $conn->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW() OR (user_id = ? AND token_version <> ?)");
    $old->bind_param('ii', $user_id, $token_version);
    $old->execute();

    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $hash = hash('sha256', $validator);
    $days = REMEMBER_DAYS;
    $ins = $conn->prepare("INSERT INTO remember_tokens (selector, validator_hash, user_id, token_version, expires_at)
                           VALUES (?, ?, ?, ?, NOW() + INTERVAL ? DAY)");
    $ins->bind_param('ssiii', $selector, $hash, $user_id, $token_version, $days);
    $ins->execute();
    remember_set_cookie($selector . ':' . $validator, time() + $days * 86400);
}

/** Signs the person in from their remember cookie. Runs at most once per request. */
function remember_restore() {
    static $tried = false;
    global $conn;
    if ($tried || !isset($_COOKIE[REMEMBER_COOKIE]) || !($conn instanceof mysqli) || headers_sent()) return false;
    $tried = true;

    $selector = remember_selector();
    $u = null;
    if ($selector !== '') {
        $st = $conn->prepare("SELECT t.validator_hash, t.token_version AS remembered_tv, u.user_id, u.token_version, u.first_name, u.middle_name,
                                     u.last_name, u.suffix, u.role, u.status, u.profile_photo
                              FROM remember_tokens t JOIN users u ON u.user_id = t.user_id
                              WHERE t.selector = ? AND t.expires_at > NOW()");
        $st->bind_param('s', $selector);
        $st->execute();
        $u = $st->get_result()->fetch_assoc();
    }
    $validator = substr((string) $_COOKIE[REMEMBER_COOKIE], 25);
    if (!$u || !hash_equals($u['validator_hash'], hash('sha256', $validator))
        || (int) $u['remembered_tv'] !== (int) $u['token_version']
        || $u['status'] !== 'active' || $u['role'] === 'admin') {
        remember_forget();
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id']       = $u['user_id'];
    $_SESSION['tv']            = (int) $u['token_version'];
    $_SESSION['first_name']    = $u['first_name'];
    $_SESSION['middle_name']   = $u['middle_name'] ?? '';
    $_SESSION['last_name']     = $u['last_name'];
    $_SESSION['suffix']        = $u['suffix'] ?? '';
    $_SESSION['role']          = $u['role'];
    $_SESSION['profile_photo'] = $u['profile_photo'];
    $_SESSION['started']       = time();
    $_SESSION['last_active']   = time();

    $days = REMEMBER_DAYS;
    $up = $conn->prepare("UPDATE remember_tokens SET expires_at = NOW() + INTERVAL ? DAY WHERE selector = ?");
    $up->bind_param('is', $days, $selector);
    $up->execute();
    remember_set_cookie($_COOKIE[REMEMBER_COOKIE], time() + $days * 86400);
    return true;
}

function is_logged_in() {
    if (!isset($_SESSION['user_id']) && !remember_restore()) return false;
    static $verified = null;
    if ($verified !== null) return $verified;
    global $conn;
    // Fail closed: without a database connection no session can be verified.
    if (!($conn instanceof mysqli)) return $verified = false;
    // Sessions from before this check, or whose password was changed, are signed out.
    if (!isset($_SESSION['tv'])) { unset($_SESSION['user_id']); return $verified = false; }
    // Automatic sign-out after 2 hours without activity, or 12 hours after logging in.
    $now = time();
    if (!isset($_SESSION['started'])) $_SESSION['started'] = $now;
    if (($now - ($_SESSION['last_active'] ?? $now)) > 7200 || ($now - $_SESSION['started']) > 43200) {
        unset($_SESSION['user_id'], $_SESSION['tv'], $_SESSION['started'], $_SESSION['last_active']);
        if (!remember_restore()) return $verified = false;
    }
    $_SESSION['last_active'] = $now;
    $stmt = $conn->prepare("SELECT token_version, status, role, user_type, id_status FROM users WHERE user_id = ?");
    if (!$stmt) return $verified = false;
    $uid = (int) $_SESSION['user_id'];
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $ok = $row && $row['status'] === 'active' && (int) $row['token_version'] === (int) $_SESSION['tv'];
    if (!$ok) { unset($_SESSION['user_id'], $_SESSION['tv']); return $verified = false; }
    // Always use the role stored in the database, so a promotion or demotion applies immediately.
    $_SESSION['role'] = $row['role'];
    $_SESSION['user_type'] = $row['user_type'] ?? 'student';
    $_SESSION['id_status'] = $row['id_status'] ?? 'approved';
    return $verified = true;
}

/**
 * True for a student whose NORSU ID has not been approved by an admin yet.
 * Staff, admins and faculty are never held back. The status comes from the database
 * on every request (see is_logged_in), so an approval takes effect immediately.
 */
function id_verification_required() {
    if (!is_logged_in()) return false;
    if (($_SESSION['role'] ?? '') !== 'member') return false;
    if (($_SESSION['user_type'] ?? 'student') !== 'student') return false;
    return ($_SESSION['id_status'] ?? 'approved') !== 'approved';
}

/** Pages an unverified student may still open: their profile (to send the ID), the ID image, notices, log out. */
function id_gate_allows_current_page() {
    $self = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    foreach (['/dashboard/profile.php', '/dashboard/id_image.php', '/dashboard/notifications.php', '/logout.php'] as $ok) {
        if (substr($self, -strlen($ok)) === $ok) return true;
    }
    return false;
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    // Students must send their NORSU ID (and wait for admin approval) before using the rest of the system.
    if (id_verification_required() && !id_gate_allows_current_page()) {
        set_flash('error', 'Please verify your account first: send the front and back of your NORSU ID below. You can use the rest of the system once an admin approves it.');
        header('Location: ' . BASE_URL . '/dashboard/profile.php#id-verify');
        exit;
    }
}

function require_role($roles) {
    require_login();
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    if (!in_array($_SESSION['role'], $roles, true)) {
        header('Location: ' . BASE_URL . '/unauthorized.php');
        exit;
    }
}

/** Where everyone lands after logging in (or if they open a guest-only page while logged in). */
function redirect_home() {
    header('Location: ' . BASE_URL . '/home.php');
    exit;
}

/**
 * After a successful login: go to the Home page. A deep link the person
 * was originally heading to (e.g. an event page) is honoured, but a
 * dashboard page never is — logging in always starts at Home.
 */
function redirect_after_login() {
    if (id_verification_required()) {   // an unverified student starts at the ID step
        unset($_SESSION['redirect_after_login']);
        set_flash('error', 'Welcome! To finish verifying your account, send the front and back of your NORSU ID below.');
        header('Location: ' . BASE_URL . '/dashboard/profile.php#id-verify');
        exit;
    }
    $target = '/home.php';
    if (!empty($_SESSION['redirect_after_login'])) {
        $r = (string) $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        if (BASE_URL !== '' && strpos($r, BASE_URL) === 0) {
            $r = substr($r, strlen(BASE_URL));
        }
        $safe = strlen($r) > 1 && $r[0] === '/' && $r[1] !== '/' && $r[1] !== '\\' && !preg_match('/[\x00-\x1f\\\\]/', $r);
        if ($safe && strpos($r, '/dashboard/') === false && strpos($r, 'login.php') === false && strpos($r, 'logout.php') === false) {
            $target = $r;
        }
    }
    header('Location: ' . BASE_URL . $target);
    exit;
}

// ---------------------------------------------------------------
// Check-in QR codes
// The QR shown at an event carries a short-lived token: an HMAC of the event and the current
// time slot, keyed with the event's secret qr_token. A photo of the QR therefore stops working
// after a few minutes. Set CHECKIN_SLOT_SECONDS to 0 to go back to one fixed QR per event.
// ---------------------------------------------------------------
if (!defined('CHECKIN_SLOT_SECONDS')) define('CHECKIN_SLOT_SECONDS', 60);   // length of one time slot
if (!defined('CHECKIN_SLOTS_VALID'))  define('CHECKIN_SLOTS_VALID', 5);     // slots (including the current one) that still count: about 4-5 minutes, enough to log in after scanning

function checkin_token_make($event_id, $qr_token, $slot = null) {
    if (CHECKIN_SLOT_SECONDS <= 0) return (string) $qr_token;
    if ($slot === null) $slot = (int) floor(time() / CHECKIN_SLOT_SECONDS);
    return substr(hash_hmac('sha256', (int) $event_id . '|' . $slot, (string) $qr_token), 0, 24);
}

function checkin_token_ok($event_id, $qr_token, $given) {
    $given = (string) $given;
    if ($given === '') return false;
    if (CHECKIN_SLOT_SECONDS <= 0) return hash_equals((string) $qr_token, $given);
    $now = (int) floor(time() / CHECKIN_SLOT_SECONDS);
    for ($i = 0; $i < CHECKIN_SLOTS_VALID; $i++) {
        if (hash_equals(checkin_token_make($event_id, $qr_token, $now - $i), $given)) return true;
    }
    return false;
}

// ---------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_check() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Security check failed. Please go back and try again.');
    }
}

// ---------------------------------------------------------------
// Flash messages (survive one redirect)
// ---------------------------------------------------------------
/** Builds an onsubmit attribute that asks before a destructive action. Text is escaped for HTML and JS. */
function confirm_attr($message) {
    return 'onsubmit="return confirm(' . htmlspecialchars(json_encode($message), ENT_QUOTES) . ');"';
}

// ---------------------------------------------------------------
// Small on/off settings kept in the app_state table.
// member_approval_required(): true means every new student sign-up must be approved by an
// officer/admin. It is OFF by default; admins can turn it on or off on the Admin dashboard.
// ---------------------------------------------------------------
function app_setting($conn, $key, $default = null) {
    $stmt = $conn->prepare("SELECT v FROM app_state WHERE k = ? LIMIT 1");
    if (!$stmt) return $default;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? $row['v'] : $default;
}

function set_app_setting($conn, $key, $value) {
    $value = (string) $value;
    $stmt = $conn->prepare("INSERT INTO app_state (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)");
    if (!$stmt) return false;
    $stmt->bind_param('ss', $key, $value);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

// Home page: officers, events and faculty glide sideways in a loop. Admins can switch that off
// (Admin dashboard or the sidebar Settings). Default: ON.
function home_marquee_enabled($conn) {
    return app_setting($conn, 'home_marquee', '1') !== '0';
}

function member_approval_required($conn) {
    // Default is OFF (no approval step) until an admin saves the setting on the Admin dashboard.
    return app_setting($conn, 'require_member_approval', '0') === '1';
}

function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (empty($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ---------------------------------------------------------------
// Image uploads (profile photos, event covers, announcement images,
// event galleries, About-CSO image) — all funnel through here so
// validation, naming, and storage stay consistent everywhere.
// ---------------------------------------------------------------
function handle_image_upload($field_name, $subdir, $max_bytes = 3 * 1024 * 1024) {
    if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null]; // nothing uploaded — not an error
    }
    return handle_image_upload_file($_FILES[$field_name], $subdir, $max_bytes);
}

// Same validation/storage logic, but takes a single-file $_FILES-shaped
// array directly — used for multi-file inputs like the event gallery,
// where each entry has to be pulled out of the PHP multi-file structure
// before this runs.
function handle_image_upload_file($file, $subdir, $max_bytes = 3 * 1024 * 1024) {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'The image failed to upload. Please try again.'];
    }
    if ($file['size'] > $max_bytes) {
        $mb = round($max_bytes / 1024 / 1024, 1);
        return ['ok' => false, 'error' => "Image is too large — please keep it under {$mb}MB."];
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Please upload a JPG, PNG, WEBP, or GIF image.'];
    }

    // The bytes must also parse as a real image (blocks files that only fake an image header).
    $dims = @getimagesize($file['tmp_name']);
    if ($dims === false || $dims[0] < 1 || $dims[1] < 1 || $dims[0] > 8000 || $dims[1] > 8000) {
        return ['ok' => false, 'error' => 'That file is not a valid image.'];
    }

    $ext = $allowed[$mime];
    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    $dest_dir = __DIR__ . '/../assets/uploads/' . $subdir . '/';
    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dest_dir . $filename)) {
        return ['ok' => false, 'error' => 'Could not save the uploaded image.'];
    }

    $sizes = ['profiles' => 800, 'covers' => 1600];
    $filename = basename(cso_optimize_image($dest_dir . $filename, $mime, $sizes[$subdir] ?? 2000));
    return ['ok' => true, 'path' => '/assets/uploads/' . $subdir . '/' . $filename];
}

/**
 * Shrinks a saved photo so a phone does not download megabytes for a small picture.
 * Returns the final path, which ends in .jpg if an opaque PNG was converted. The original
 * stays if anything goes wrong or the result would not be smaller. GIFs are left alone
 * so they keep their animation.
 */
function cso_optimize_image($path, $mime, $max_side) {
    if ($mime === 'image/gif' || !function_exists('imagecreatefromstring')) return $path;
    $info = @getimagesize($path);
    if (!$info) return $path;
    [$w, $h] = $info;
    if ((max($w, $h) <= $max_side && filesize($path) <= 500 * 1024) || $w * $h > 40000000) return $path;

    // Phones store a photo's rotation in its EXIF data, which is lost when it is saved again.
    // Without a way to read that, leave the photo alone rather than risk it turning sideways.
    $turn = 0;
    if ($mime === 'image/jpeg') {
        if (!function_exists('exif_read_data')) return $path;
        $exif = @exif_read_data($path);
        $turn = [3 => 180, 6 => -90, 8 => 90][(int) ($exif['Orientation'] ?? 1)] ?? 0;
    }
    $src = @imagecreatefromstring(file_get_contents($path));
    if (!$src) return $path;
    if ($turn !== 0) {
        $src = imagerotate($src, $turn, 0);
        if (!$src) return $path;
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, $max_side / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);

    $png_alpha = $mime === 'image/png' && in_array(ord((string) file_get_contents($path, false, null, 25, 1)), [4, 6], true);
    $keep_alpha = $png_alpha || $mime === 'image/webp';
    if ($keep_alpha) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    // Most PNGs only say they can be transparent, so look at the picture itself.
    if ($png_alpha) {
        $png_alpha = false;
        $step = max(1, (int) floor(min($nw, $nh) / 60));
        for ($y = 0; $y < $nh && !$png_alpha; $y += $step) {
            for ($x = 0; $x < $nw; $x += $step) {
                if ((imagecolorat($dst, $x, $y) >> 24) & 127) { $png_alpha = true; break; }
            }
        }
    }

    $target = $mime === 'image/png' && !$png_alpha ? preg_replace('/\.png$/', '.jpg', $path) : $path;
    $temp = $target . '.tmp';
    if ($mime === 'image/webp') {
        $saved = imagewebp($dst, $temp, 82);
    } elseif ($png_alpha) {
        $saved = imagepng($dst, $temp, 9);
    } else {
        imageinterlace($dst, true);   // shows a preview while it downloads
        $saved = imagejpeg($dst, $temp, 82);
    }
    if (!$saved || filesize($temp) >= filesize($path)) {
        @unlink($temp);
        return $path;
    }
    rename($temp, $target);
    if ($target !== $path) @unlink($path);
    return $target;
}

// ---------------------------------------------------------------
// NORSU ID photos. These are private: stored outside assets/uploads in a folder
// that the web server refuses to serve (private/.htaccess), and only shown through
// dashboard/id_image.php to the owner and to admins.
// ---------------------------------------------------------------
function private_id_dir() {
    $dir = __DIR__ . '/../private/ids/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir;
}

/** Same checks as handle_image_upload_file (real image, size cap) but saved to the private folder. Returns ['ok'=>bool,'file'=>name,'error'=>..]. */
function handle_private_id_upload($file, $max_bytes = 5 * 1024 * 1024) {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Please add both the front and the back photo of your ID.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'A photo failed to upload. Please try again.'];
    }
    if ($file['size'] > $max_bytes) {
        return ['ok' => false, 'error' => 'Each ID photo must be under ' . round($max_bytes / 1024 / 1024) . 'MB.'];
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Please upload JPG, PNG or WEBP photos of your ID.'];
    }
    $dims = @getimagesize($file['tmp_name']);
    if ($dims === false || $dims[0] < 200 || $dims[1] < 120 || $dims[0] > 8000 || $dims[1] > 8000) {
        return ['ok' => false, 'error' => 'That photo is too small or not a valid image. Take a clear, close photo of the ID.'];
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], private_id_dir() . $name)) {
        return ['ok' => false, 'error' => 'Could not save the photo. Please try again.'];
    }
    return ['ok' => true, 'file' => $name];
}

function delete_private_id($name) {
    $name = basename((string) $name);
    if ($name === '') return;
    $abs = private_id_dir() . $name;
    if (is_file($abs)) @unlink($abs);
}

function delete_uploaded_image($relative_path) {
    if (empty($relative_path)) return;
    // Only ever delete inside assets/uploads (a bad value in the database cannot reach other files).
    $base = realpath(__DIR__ . '/../assets/uploads');
    $abs  = realpath(__DIR__ . '/..' . $relative_path);
    if ($base && $abs && strpos($abs, $base . DIRECTORY_SEPARATOR) === 0 && is_file($abs)) {
        @unlink($abs);
    }
}

// ---------------------------------------------------------------
// Small display helpers
// ---------------------------------------------------------------
function initials($first, $last) {
    $a = mb_substr(trim($first), 0, 1);
    $b = mb_substr(trim($last), 0, 1);
    return mb_strtoupper($a . $b);
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------
// Event / announcement display helpers (used by the detail pages and
// the shared card renderers in includes/cards.php)
// ---------------------------------------------------------------
/** Date for display; the fallback when an event has no date set. */
function fmt_date($d, $fmt = 'M j, Y', $fallback = 'Date TBA') {
    return empty($d) ? $fallback : date($fmt, strtotime($d));
}
/** Clock time for display; empty string when no time is set. */
function fmt_clock($t, $fallback = '') {
    return empty($t) ? $fallback : date('g:i A', strtotime($t));
}
/** "Oct 26, 2026 · 2:00 PM", or just the date, or "Date TBA". */
function fmt_when($d, $t, $fmt = 'M j, Y', $sep = ' · ', $fallback = 'Date TBA') {
    $out = fmt_date($d, $fmt, $fallback);
    if (!empty($d) && !empty($t)) $out .= $sep . fmt_clock($t);
    return $out;
}

function event_range_text($ev) {
    if (empty($ev['event_date'])) return 'Date to be announced';
    $text = date('F j, Y', strtotime($ev['event_date']));
    if (!empty($ev['event_end_date']) && $ev['event_end_date'] !== $ev['event_date']) {
        $text .= ' – ' . date('F j, Y', strtotime($ev['event_end_date']));
    }
    return $text;
}

function event_time_text($ev) {
    if (empty($ev['event_time'])) return '';
    $text = date('g:i A', strtotime($ev['event_time']));
    if (!empty($ev['event_end_time'])) {
        $text .= ' – ' . date('g:i A', strtotime($ev['event_end_time']));
    }
    return $text;
}

/** [start_timestamp, end_timestamp] for an event row. */
function event_timestamps($ev) {
    if (empty($ev['event_date'])) return [0, 0];
    $start    = strtotime($ev['event_date'] . ' ' . (!empty($ev['event_time']) ? $ev['event_time'] : '00:00:00'));
    $end_date = !empty($ev['event_end_date']) ? $ev['event_end_date'] : $ev['event_date'];
    $end      = !empty($ev['event_end_time'])
        ? strtotime($end_date . ' ' . $ev['event_end_time'])
        : strtotime($end_date . ' 23:59:59');
    if ($end < $start) $end = $start + 3600;
    return [$start, $end];
}

/** 'tba' (no date yet) | 'upcoming' | 'ongoing' | 'ended' */
function event_state($ev) {
    if (empty($ev['event_date'])) return 'tba';
    list($start, $end) = event_timestamps($ev);
    $now = time();
    if ($now < $start) return 'upcoming';
    return $now <= $end ? 'ongoing' : 'ended';
}

function event_state_chip($ev) {
    $state = event_state($ev);
    $map = [
        'upcoming' => ['chip', 'Upcoming'],
        'ongoing'  => ['chip chip-live', 'Happening now'],
        'ended'    => ['chip chip-muted', 'Ended'],
        'tba'      => ['chip chip-muted', 'Date to be announced'],
    ];
    return '<span class="' . $map[$state][0] . '">' . $map[$state][1] . '</span>';
}

/** "Today", "Tomorrow", "In 5 days", "3 days ago"… for an event's start date. */
function event_relative_text($ev) {
    if (empty($ev['event_date'])) return '';
    $days = (int) round((strtotime($ev['event_date']) - strtotime(date('Y-m-d'))) / 86400);
    if ($days === 0)  return 'Today';
    if ($days === 1)  return 'Tomorrow';
    if ($days === -1) return 'Yesterday';
    return $days > 0 ? 'In ' . $days . ' days' : abs($days) . ' days ago';
}

function time_left_text($ts) {
    $diff = $ts - time();
    if ($diff <= 0) return 'Closed';
    $d = (int) floor($diff / 86400);
    $h = (int) floor(($diff % 86400) / 3600);
    if ($d >= 1) return $d . ' day' . ($d > 1 ? 's' : '') . ' left';
    if ($h >= 1) return $h . ' hour' . ($h > 1 ? 's' : '') . ' left';
    return 'Less than an hour left';
}

// ---------------------------------------------------------------
// Notifications: a reminder counts as "unread" until the person opens
// the Notifications page. Opening it stamps users.notif_seen_at, which
// clears the badge, the red dot and the dashboard reminder panel.
// ---------------------------------------------------------------
function notif_seen_at($conn) {
    $seen = '1970-01-01 00:00:00';
    if (is_logged_in() && $conn instanceof mysqli) {
        $uid = (int) $_SESSION['user_id'];
        $res = @$conn->query("SELECT notif_seen_at FROM users WHERE user_id = $uid");
        if ($res && ($row = $res->fetch_assoc()) && !empty($row['notif_seen_at'])) {
            $seen = $row['notif_seen_at'];
        }
    }
    return $seen;
}

/** When this user last cleared their notification history (items before it are hidden for them only). */
function notif_cleared_at($conn) {
    $cleared = '1970-01-01 00:00:00';
    if (is_logged_in() && $conn instanceof mysqli) {
        $uid = (int) $_SESSION['user_id'];
        $res = @$conn->query("SELECT notif_cleared_at FROM users WHERE user_id = $uid");
        if ($res && ($row = $res->fetch_assoc()) && !empty($row['notif_cleared_at'])) {
            $cleared = $row['notif_cleared_at'];
        }
    }
    return $cleared;
}

function clear_notification_history($conn) {
    if (is_logged_in() && $conn instanceof mysqli) {
        $uid = (int) $_SESSION['user_id'];
        @$conn->query("UPDATE users SET notif_cleared_at = NOW(), notif_seen_at = NOW() WHERE user_id = $uid");
    }
}

function mark_notifications_seen($conn) {
    if (is_logged_in() && $conn instanceof mysqli) {
        $uid = (int) $_SESSION['user_id'];
        @$conn->query("UPDATE users SET notif_seen_at = NOW() WHERE user_id = $uid");
    }
}

/* ---------- Dashboard metric helpers (design system v5) ---------- */

require_once __DIR__ . '/post_ui.php';


/** Verified badges, granted only by an admin. Faculty get the faculty badge, officers the officer badge, other students the member badge. */
function verified_options($user_type, $role = 'member') {
    $allowed = ['none' => 'No badge'];
    if ($user_type === 'faculty') {
        $allowed['faculty'] = 'Verified faculty';
    } elseif ($role === 'officer') {
        $allowed['officer'] = 'Verified officer';
    } else {
        $allowed['member'] = 'Verified member';
    }
    // The developer badge can be given to any account (an account has one badge at a time).
    $allowed['developer'] = 'Developer';
    return $allowed;
}

function verified_badge_html($verified_as) {
    if ($verified_as === 'member') {
        return '<span class="vbadge vbadge-member" title="Verified CSO member. Checked by a CSO admin.">Verified member</span>';
    }
    if ($verified_as === 'faculty') {
        return '<span class="vbadge vbadge-faculty" title="Verified CSO faculty or adviser. Checked by a CSO admin.">'
            . '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.4 8.4-8 9-4.6-.6-8-4-8-9V6z"/></svg>'
            . 'Verified faculty</span>';
    }
    if ($verified_as === 'officer') {
        return '<span class="vbadge vbadge-officer" title="Verified CSO officer. Checked by a CSO admin.">'
            . '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.5 6.6 19.5l1.2-6-4.5-4.2 6.1-.7z"/></svg>'
            . 'Verified officer</span>';
    }
    if ($verified_as === 'developer') {
        return '<span class="vbadge vbadge-developer" title="Developer of the CSO-NORSU portal.">'
            . '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7l-5 5 5 5M16 7l5 5-5 5M14 4l-4 16"/></svg>'
            . 'Developer</span>';
    }
    return '';
}
