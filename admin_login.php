<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (is_logged_in()) {
    redirect_home();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require_once __DIR__ . '/includes/auth.php';

    $identifier = clean($_POST['identifier']); // student_id or email
    $password   = $_POST['password'] ?? '';
    $result = attempt_login($conn, $identifier, $password, 'admin');
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
        redirect_after_login();   // always Home (never the dashboard)
    }
    $error = $result['error'];
}

$page_title = 'Admin Login';
$auth_layout  = true;
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

        <p class="hub-kicker">Administrator</p>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo BASE_URL; ?>/admin_login.php" class="hub-form">
            <?php echo csrf_field(); ?>

            <div class="hub-field">
                <label class="hub-sr" for="identifier">Admin ID or email</label>
                <input type="text" id="identifier" name="identifier" placeholder="Admin ID / Email" autocomplete="username" required autofocus>
                <span class="hub-ico"><svg class="icon" aria-hidden="true"><use href="#i-user"/></svg></span>
            </div>

            <div class="hub-field pw-wrap">
                <label class="hub-sr" for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" autocomplete="current-password" required>
                <button type="button" class="pw-toggle" aria-label="Show password"><svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg></button>
            </div>

            <div class="hub-row">
                <span></span>
                <a class="hub-link" href="<?php echo BASE_URL; ?>/forgot_password.php">Forgot password?</a>
            </div>

            <button type="submit" class="hub-btn">Login as admin</button>
        </form>
    </div>

    <div class="hub-extra">
        <a class="hub-extra-link" href="<?php echo BASE_URL; ?>/login.php">Member Login</a>
        <a class="hub-extra-link hub-extra-plain" href="<?php echo BASE_URL; ?>/home.php">Continue without logging in</a>
    </div>

    <button type="button" class="theme-toggle hub-theme" id="themeToggle" aria-label="Switch between light and dark mode" title="Switch theme">
        <svg class="icon i-sun" aria-hidden="true"><use href="#i-sun"/></svg>
        <svg class="icon i-moon" aria-hidden="true"><use href="#i-moon"/></svg>
    </button>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
