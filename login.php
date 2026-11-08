<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (is_logged_in()) {
    redirect_home();
}

$error = '';
$show_admin_link = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    {
        require_once __DIR__ . '/includes/auth.php';
        require_once __DIR__ . '/includes/otp.php';   // otp_email_available() for the unverified-account branch
        $identifier = clean($_POST['identifier']); // student_id or email
        $password   = $_POST['password'] ?? '';
        $result = attempt_login($conn, $identifier, $password, 'member');
        if ($result['ok']) {
            $user = $result['user'];
            $_SESSION['user_id']       = $user['user_id'];
        $_SESSION['tv']            = (int) $user['token_version'];
            $_SESSION['first_name']    = $user['first_name'];
            $_SESSION['middle_name']   = $user['middle_name'] ?? '';
            $_SESSION['last_name']     = $user['last_name'];
            $_SESSION['suffix']        = $user['suffix'] ?? '';
            $_SESSION['role']          = $user['role'];
            $_SESSION['profile_photo'] = $user['profile_photo'];
            if (!empty($_POST['remember'])) remember_issue($user['user_id'], $user['token_version']);
            redirect_after_login();   // always Home (never the dashboard)
        }
        if (!empty($result['unverified_user_id']) && otp_email_available()) {
            // The password was right, so it is safe to send them back to finish verifying.
            $_SESSION['verify_user'] = (int) $result['unverified_user_id'];
            set_flash('error', $result['error']);
            header('Location: ' . BASE_URL . '/verify_email.php');
            exit;
        }
        $error = $result['error'];
        $show_admin_link = !empty($result['admin_hint']);
    }
}

$page_title = 'Login';
$auth_layout  = true;
require_once __DIR__ . '/includes/header.php';
?>

<main class="hub-page" id="main" tabindex="-1">
    <div class="hub-card">
        <div class="hub-brand">
            <img src="<?php echo asset_v('/assets/images/logo.png'); ?>" alt="" width="46" height="46" onerror="this.style.display='none'">
            <div class="hub-brand-text">
                <h1 class="hub-name">CSO-NORSU</h1>
                <p class="hub-org">Organization Portal</p>
            </div>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['reset'])): ?>
            <div class="alert alert-success" role="status">Your password was changed. Log in with your new password.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo BASE_URL; ?>/login.php" class="hub-form">
            <?php echo csrf_field(); ?>

            <div class="hub-field">
                <label class="hub-sr" for="identifier">Student ID or Email</label>
                <input type="text" id="identifier" name="identifier" placeholder="Student ID / Email" autocomplete="username" required autofocus>
                <span class="hub-ico"><svg class="icon" aria-hidden="true"><use href="#i-user"/></svg></span>
            </div>

            <div class="hub-field pw-wrap">
                <label class="hub-sr" for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" autocomplete="current-password" required>
                <button type="button" class="pw-toggle" aria-label="Show password"><svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg></button>
            </div>

            <div class="hub-row">
                <label class="hub-remember">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>
                <a class="hub-link" href="<?php echo BASE_URL; ?>/forgot_password.php">Forgot password?</a>
            </div>

            <button type="submit" class="hub-btn">Login</button>
        </form>

        <p class="hub-foot">Don't have an account? <a class="hub-link" href="<?php echo BASE_URL; ?>/register.php">Register</a></p>
    </div>

    <div class="hub-extra">
        <a class="hub-extra-link" href="<?php echo BASE_URL; ?>/faculty_login.php">Faculty &amp; Staff Login</a>
        <a class="hub-extra-link hub-extra-plain" href="<?php echo BASE_URL; ?>/home.php">Continue without logging in</a>
    </div>

    <button type="button" class="theme-toggle hub-theme" id="themeToggle" aria-label="Switch between light and dark mode" title="Switch theme">
        <svg class="icon i-sun" aria-hidden="true"><use href="#i-sun"/></svg>
        <svg class="icon i-moon" aria-hidden="true"><use href="#i-moon"/></svg>
    </button>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
