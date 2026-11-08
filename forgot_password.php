<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/otp.php';

if (is_logged_in()) redirect_home();

$page_title = 'Forgot password';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $identifier = clean($_POST['identifier'] ?? '');
    $ip = cso_client_ip();

    // "I already have a code": skip sending and go straight to the code page.
    // Also covers codes an officer issued in person from the Reset Codes page.
    if (($_POST['action'] ?? '') === 'have_code' && $identifier !== '') {
        $_SESSION['reset_identifier'] = $identifier;
        header('Location: ' . BASE_URL . '/verify_code.php');
        exit;
    }

    if (!otp_email_available()) {
        $notice = 'Emailed codes are not turned on yet. Ask a CSO officer to issue you a reset code in person.';
    } elseif ($identifier !== '') {
        $stmt = $conn->prepare("SELECT user_id, first_name, middle_name, last_name, suffix, email, role, status
                                FROM users WHERE student_id = ? OR email = ? LIMIT 1");
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Admin accounts are never reset from this form. Only active accounts with an email get a code.
        if ($u && $u['status'] === 'active' && $u['role'] !== 'admin' && !otp_too_many($conn, $u['user_id'], $ip)) {
            $code = otp_issue($conn, $u['user_id'], 'email', $ip);
            otp_send_email($u['email'], full_name($u), $code);   // result is not shown, so no account is revealed
        }
    }

    if ($notice !== '') goto render;   // email codes are off: show the officer instructions

    // Same answer whether or not the account exists.
    $_SESSION['reset_identifier'] = $identifier;
    set_flash('success', 'If an account matches, a 6-digit code was sent to its email. The code expires in ' . otp_mins(OTP_RESET_LIFETIME_MIN) . '.');
    header('Location: ' . BASE_URL . '/verify_code.php');
    exit;
}

render:
$auth_layout = true;
require_once __DIR__ . '/includes/header.php';
?>
<main class="hub-page" id="main" tabindex="-1">
    <div class="hub-card">
        <div class="hub-brand">
            <img src="<?php echo asset_v('/assets/images/logo.png'); ?>" alt="" width="52" height="52" onerror="this.style.display='none'">
            <div class="hub-brand-text">
                <h1 class="hub-name">CSO-NORSU</h1>
                <p class="hub-org">Organization Portal</p>
            </div>
        </div>

        <p class="hub-kicker">Password Reset</p>
        <p class="hub-lead">Enter your Student ID or email. If an account matches, we will send a one-time code to its email.</p>

        <?php if ($notice): ?>
            <div class="alert alert-warning"><?php echo e($notice); ?></div>
        <?php endif; ?>

        <form method="POST" class="hub-form" autocomplete="off">
            <?php echo csrf_field(); ?>

            <div class="hub-field">
                <label class="hub-sr" for="identifier">Student ID or email</label>
                <input type="text" id="identifier" name="identifier" placeholder="Student ID / Email" required maxlength="120" autocomplete="username" autofocus>
                <span class="hub-ico"><svg class="icon" aria-hidden="true"><use href="#i-user"/></svg></span>
            </div>

            <button type="submit" name="action" value="send" class="hub-btn">Send code</button>
            <button type="submit" name="action" value="have_code" class="hub-btn hub-btn-outline">I already have a code</button>
            <p class="hub-help">Type your Student ID or email above, then tap &ldquo;I already have a code&rdquo; to enter it.</p>
        </form>
    </div>

    <div class="hub-extra">
        <a class="hub-extra-link hub-extra-plain" href="<?php echo BASE_URL; ?>/login.php">Back to log in</a>
    </div>

    <button type="button" class="theme-toggle hub-theme" id="themeToggle" aria-label="Switch between light and dark mode" title="Switch theme">
        <svg class="icon i-sun" aria-hidden="true"><use href="#i-sun"/></svg>
        <svg class="icon i-moon" aria-hidden="true"><use href="#i-moon"/></svg>
    </button>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
