<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/otp.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect_home();
}

$errors = [];
$success = false;
$success_faculty = false;
$auto_approved = false;   // true when an admin has turned off member approval

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ip_now = cso_client_ip();
    // Bot trap: the hidden field below is empty for real people. Also limit how many sign-ups one address can start per hour.
    if (!empty($_POST['hp_field'])) {
        $errors[] = 'Registration failed. Please try again.';
    } elseif (register_too_many($conn, $ip_now)) {
        $errors[] = 'Too many sign-ups from this network just now. Please wait a while and try again, or ask a CSO officer for help.';
    }
    $is_faculty  = (($_POST['account_type'] ?? 'student') === 'faculty');
    $student_id = clean($_POST['student_id']);
    $first_name  = clean($_POST['first_name']);
    $middle_name = clean($_POST['middle_name'] ?? '');
    $last_name   = clean($_POST['last_name']);
    $suffix      = clean($_POST['suffix'] ?? '');
    $email      = strtolower(clean($_POST['email']));   // stored lower-case: addresses are not case-sensitive, and the mail API matches exactly
    $faculty_position = clean($_POST['faculty_position'] ?? '');
    if ($is_faculty) {
        // Faculty: the course column holds their department, year level is fixed.
        $course     = clean($_POST['department'] ?? '');
        $year_level = 'Faculty';
    } else {
        $course     = 'BSCS';   // BSCS (Computer Science) is the only course offered
        $year_level = clean($_POST['year_level'] ?? '');
    }
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    if (empty($student_id) || empty($first_name) || empty($last_name) || empty($email) || empty($course) || empty($year_level) || empty($password)) {
        $errors[] = 'All fields are required.';
    }
    if (empty($_POST['agree_terms'])) {
        $errors[] = 'Please read and agree to the Terms and Conditions to create an account.';
    }
    if ($is_faculty && $faculty_position === '') {
        $errors[] = 'Please enter your position (e.g. Adviser, Instructor, Professor).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 10) {
        $errors[] = 'Password must be at least 10 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Students must be on the official Student ID list (when the list has been uploaded).
    if (empty($errors) && !$is_faculty) {
        $rc = $conn->query("SELECT COUNT(*) AS c FROM student_roster");
        if ($rc && (int) $rc->fetch_assoc()['c'] > 0) {
            $rs = $conn->prepare("SELECT 1 FROM student_roster WHERE student_id = ?");
            $rs->bind_param('s', $student_id);
            $rs->execute();
            $rs->store_result();
            if ($rs->num_rows === 0) {
                $errors[] = 'This Student ID is not on the CSO member list. Check the number, or ask a CSO officer for help.';
            }
            $rs->close();
        }
    }

    if (empty($errors)) {
        $check = $conn->prepare("SELECT user_id FROM users WHERE student_id = ? OR email = ?");
        $check->bind_param('ss', $student_id, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = $is_faculty ? 'An account with this Employee ID or email already exists.' : 'An account with this Student ID or email already exists.';
        } else {
            register_log($conn, $ip_now);   // counts toward this address's hourly sign-up limit
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            // While email verification is switched on, every new account is held
            // inactive and unverified until the emailed code is entered
            // (verify_email.php). Only then do the usual approval rules apply.
            $verify_by_email = otp_email_available();

            if ($is_faculty) {
                // Faculty start as 'inactive' (pending). Staff approve them in Dashboard > Members,
                // and only approved faculty appear on the Home page.
                $new_verified = $verify_by_email ? 0 : 1;
                $stmt = $conn->prepare("INSERT INTO users (student_id, first_name, middle_name, last_name, suffix, email, password, course, year_level, role, user_type, faculty_position, status, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'member', 'faculty', ?, 'inactive', ?)");
                $stmt->bind_param('ssssssssssi', $student_id, $first_name, $middle_name, $last_name, $suffix, $email, $hashed, $course, $year_level, $faculty_position, $new_verified);
            } else {
                // Admin setting (Admin dashboard > Sign-up settings): when approval is off, new
                // student accounts are active straight away. Faculty always need admin approval.
                $needs_approval = member_approval_required($conn);
                if ($verify_by_email) {
                    $new_status    = 'inactive';
                    $new_pending   = 0;          // officers only see it once the email is verified
                    $new_verified  = 0;
                    $auto_approved = false;
                } else {
                    $new_status    = $needs_approval ? 'inactive' : 'active';
                    $new_pending   = $needs_approval ? 1 : 0;
                    $new_verified  = 1;
                    $auto_approved = !$needs_approval;
                }
                $stmt = $conn->prepare("INSERT INTO users (student_id, first_name, middle_name, last_name, suffix, email, password, course, year_level, role, status, approval_pending, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'member', ?, ?, ?)");
                $stmt->bind_param('ssssssssssii', $student_id, $first_name, $middle_name, $last_name, $suffix, $email, $hashed, $course, $year_level, $new_status, $new_pending, $new_verified);
            }

            if ($stmt->execute()) {
                $new_user_id = (int) $conn->insert_id;
                $stmt->close();

                if ($verify_by_email) {
                    // Send the 6-digit code and hand the visitor over to verify_email.php.
                    $ip   = cso_client_ip();
                    $code   = ev_issue($conn, $new_user_id, $ip);
                    $mailed = ev_send_email($email, trim($first_name . ' ' . $last_name), $code);
                    $_SESSION['verify_user'] = $new_user_id;
                    set_flash($mailed ? 'success' : 'error', $mailed
                        ? 'We sent a 6-digit code to ' . $email . '. It expires in ' . OTP_LIFETIME_MIN . ' minutes.'
                        : 'We could not send the code to ' . $email . ' just now. Try "Send a new code" below, or ask a CSO officer for help.');
                    header('Location: ' . BASE_URL . '/verify_email.php');
                    exit;
                }

                $success = true;
                $success_faculty = $is_faculty;
            } else {
                $errors[] = 'Registration failed. Please try again.';
                $stmt->close();
            }
        }
        $check->close();
    }
}

$page_title = 'Register';
$auth_layout = true;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth_shell.php';
?>

<?php auth_stage_start('register'); ?>
            <h1 class="signin-title">Create your account</h1>
            <p class="signin-sub">Join CSO-NORSU in about a minute.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php if ($success_faculty): ?>
                    Thank you! Your faculty request was sent. A CSO admin will review it &mdash; once approved you can <a href="<?php echo BASE_URL; ?>/login.php">Log In</a> and you will be listed on the Home page with your position.
                <?php elseif ($auto_approved): ?>
                    Welcome to CSO! Your account is ready &mdash; you can <a href="<?php echo BASE_URL; ?>/login.php">log in</a> now.
                <?php else: ?>
                    Thank you! Your sign-up was received. A CSO officer will check it, and you can <a href="<?php echo BASE_URL; ?>/login.php">log in</a> once it is approved.<?php echo otp_email_available() ? ' We will also email you when it is reviewed.' : ' Check back later, or ask a CSO officer.'; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/register.php" class="form">
 <?php echo csrf_field(); ?>
                <div style="position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden;" aria-hidden="true"><label>Leave this field empty<input type="text" name="hp_field" value="" tabindex="-1" autocomplete="off"></label></div>
                <?php $acct = (($_POST['account_type'] ?? 'student') === 'faculty') ? 'faculty' : 'student'; ?>
                <div class="form-row">
                    <label>I am joining as</label>
                    <div class="acct-switch" id="acctSeg" role="radiogroup" aria-label="Account type" data-active="<?php echo $acct; ?>">
                        <span class="acct-thumb" aria-hidden="true"></span>
                        <label class="acct-opt<?php echo $acct === 'student' ? ' is-on' : ''; ?>"><input type="radio" name="account_type" value="student" <?php echo $acct === 'student' ? 'checked' : ''; ?>><span>Student</span></label>
                        <label class="acct-opt<?php echo $acct === 'faculty' ? ' is-on' : ''; ?>"><input type="radio" name="account_type" value="faculty" <?php echo $acct === 'faculty' ? 'checked' : ''; ?>><span>Faculty / Instructor</span></label>
                    </div>
                </div>
                <div class="form-row">
                    <label for="student_id" id="idLabel"><?php echo $acct === 'faculty' ? 'Employee ID' : 'Student ID'; ?></label>
                    <input type="text" id="student_id" name="student_id" placeholder="<?php echo $acct === 'faculty' ? 'e.g. EMP1024' : 'e.g. 2026001'; ?>" value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" required>
                </div>
                <div class="form-row form-row-split">
                    <div>
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" placeholder="e.g. Juan" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                    </div>
                    <div>
                        <label for="middle_name">Middle Name <span class="muted" style="font-weight:400;">(optional)</span></label>
                        <input type="text" id="middle_name" name="middle_name" placeholder="e.g. Santos" value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                    </div>
                    <div>
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" placeholder="e.g. Dela Cruz" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                    </div>
                    <div>
                        <label for="suffix">Suffix <span class="muted" style="font-weight:400;">(optional, e.g. Jr., Sr., II, III)</span></label>
                        <input type="text" id="suffix" name="suffix" placeholder="e.g. Jr." value="<?php echo isset($_POST['suffix']) ? htmlspecialchars($_POST['suffix']) : ''; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="e.g. juandelacruz@gmail.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div class="form-row form-row-split" data-for="student"<?php echo $acct === 'faculty' ? ' hidden' : ''; ?>>
                    <div>
                        <label for="course">Program</label>
                        <input type="text" id="course" value="BSCS — Computer Science" disabled>
                    </div>
                    <div>
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level" <?php echo $acct === 'faculty' ? '' : 'required'; ?>>
                            <option value="">Select</option>
                            <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yr): ?>
                                <option value="<?php echo $yr; ?>" <?php echo (isset($_POST['year_level']) && $_POST['year_level'] === $yr) ? 'selected' : ''; ?>><?php echo $yr; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row form-row-split" data-for="faculty"<?php echo $acct === 'faculty' ? '' : ' hidden'; ?>>
                    <div>
                        <label for="faculty_position">Position</label>
                        <input type="text" id="faculty_position" name="faculty_position" list="positionList" placeholder="e.g. Adviser, Instructor" value="<?php echo isset($_POST['faculty_position']) ? htmlspecialchars($_POST['faculty_position']) : ''; ?>" <?php echo $acct === 'faculty' ? 'required' : ''; ?>>
                        <datalist id="positionList">
                            <option value="Adviser"><option value="Co-Adviser"><option value="Instructor"><option value="Professor"><option value="Department Head"><option value="Dean">
                        </datalist>
                    </div>
                    <div>
                        <label for="department">Department / College</label>
                        <input type="text" id="department" name="department" placeholder="e.g. College of Computer Science" value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>" <?php echo $acct === 'faculty' ? 'required' : ''; ?>>
                    </div>
                </div>
                <p class="muted" data-for="faculty" style="font-size:0.82rem; margin:-4px 0 14px;"<?php echo $acct === 'faculty' ? '' : ' hidden'; ?>>Faculty accounts are reviewed by a CSO admin before they go live. Once approved, you will be shown on the Home page with your position.</p>
                <div class="form-row form-row-split">
                    <div>
                        <label for="password">Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="password" name="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" minlength="10" required aria-describedby="pw-checks-1">
                            <button type="button" class="pw-toggle" aria-label="Show password"><svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg></button>
                        </div>
                        <ul class="pw-checks" id="pw-checks-1" aria-live="polite">
                            <li data-rule="len"><span class="pw-box" aria-hidden="true"></span><span>At least 10 characters</span><span class="sr-only pw-sr"></span></li>
                        </ul>
                    </div>
                    <div>
                        <label for="confirm_password">Confirm Password</label>
                        <div class="pw-wrap">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" minlength="10" required aria-describedby="pw-checks-2">
                            <button type="button" class="pw-toggle" aria-label="Show password"><svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg></button>
                        </div>
                        <ul class="pw-checks" id="pw-checks-2" aria-live="polite">
                            <li data-rule="match"><span class="pw-box" aria-hidden="true"></span><span>Both passwords match</span><span class="sr-only pw-sr"></span></li>
                        </ul>

                    </div>
                </div>
                <label class="signin-check">
                    <input type="checkbox" name="agree_terms" id="agree_terms" value="1" required <?php echo !empty($_POST['agree_terms']) ? 'checked' : ''; ?>>
                    <span>I have read and agree to the <a href="<?php echo BASE_URL; ?>/terms.php" onclick="openTermsModal(event)">Terms and Conditions</a> and<span class="agree-privacy"><a href="<?php echo BASE_URL; ?>/privacy.php" onclick="openPrivacyModal(event)">Privacy Policy</a>.</span></span>
                </label>
                <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
                <p class="form-footnote">Already have an account? <a href="<?php echo BASE_URL; ?>/login.php" data-switch="login">Log In</a></p>
            </form>
        <?php endif; ?>
<?php auth_stage_end(); ?>

<style>
/* Terms line: "Privacy Policy" sits centered on its own line under the sentence */
.signin-check .agree-privacy { display: block; text-align: center; margin-top: 2px; }
.signin-check a { text-decoration: underline; text-underline-offset: 2px; font-weight: 600; }
html[data-theme="light"] .signin-check a { color: #1F6B3B; }
html[data-theme="dark"] .signin-check a { color: #8FE3B2; }

/* Password checklist: a small rounded box that ticks green when the rule is met */
.pw-checks { list-style: none; margin: 10px 0 0; padding: 0; display: grid; gap: 6px; }
.pw-checks li { display: flex; align-items: center; gap: 8px; font-size: .85rem; color: var(--muted, #5b655f); transition: color .2s ease; }
.pw-box {
    position: relative; flex: none; width: 16px; height: 16px; border-radius: 5px;
    border: 1.5px solid #9aa5a0; background: transparent;
    transition: background-color .2s ease, border-color .2s ease;
}
.pw-box::after {
    content: ""; position: absolute; left: 4.5px; top: 1.5px; width: 4px; height: 8px;
    border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg) scale(0); transform-origin: center;
    transition: transform .2s ease;
}
.pw-checks li.is-ok { color: #1F6B3B; font-weight: 600; }
.pw-checks li.is-ok .pw-box { background: #2F7D4F; border-color: #2F7D4F; }
.pw-checks li.is-ok .pw-box::after { transform: rotate(45deg) scale(1); }
.pw-checks li.is-bad { color: #9B1C1C; }
.pw-checks li.is-bad .pw-box { border-color: #C23B3B; }
html[data-theme="dark"] .pw-checks li.is-ok { color: #6FD99C; }
html[data-theme="dark"] .pw-checks li.is-ok .pw-box { background: #3E9A66; border-color: #3E9A66; }
html[data-theme="dark"] .pw-checks li.is-bad { color: #FCA5A5; }
@media (prefers-reduced-motion: reduce) { .pw-box, .pw-box::after, .pw-checks li { transition: none; } }
</style>
<script>
/* Password checklist: both lists (under Password and Confirm Password) tick as the person types. */
(function () {
    var pw = document.getElementById('password');
    var conf = document.getElementById('confirm_password');
    var lists = document.querySelectorAll('.pw-checks');
    if (!pw || !conf || !lists.length) return;
    function set(rule, state) {
        Array.prototype.forEach.call(lists, function (list) {
            var li = list.querySelector('[data-rule="' + rule + '"]');
            if (!li) return;
            li.className = state === null ? '' : (state ? 'is-ok' : 'is-bad');
            li.querySelector('.pw-sr').textContent = state === null ? '' : (state ? ' (done)' : ' (not yet)');
        });
    }
    function check() {
        set('len', pw.value.length > 0 ? pw.value.length >= 10 : null);
        if (conf.value.length > 0) {
            var same = pw.value === conf.value;
            set('match', same);
            conf.setCustomValidity(same ? '' : 'The two passwords do not match.');
        } else {
            set('match', null);
            conf.setCustomValidity('');
        }
    }
    pw.addEventListener('input', check);
    conf.addEventListener('input', check);
    check();
})();
</script>
<style>
/* Student / Faculty switch: soft, borderless segmented control with a sliding selection */
.acct-switch {
    --acct-pad: 4px;
    position: relative; display: grid; grid-template-columns: 1fr 1fr;
    padding: var(--acct-pad); border-radius: 999px; border: 0;
    background: rgba(55, 85, 52, .10);
    box-shadow: none;
}
.acct-switch:focus-within { outline: none; box-shadow: 0 0 0 3px rgba(55, 85, 52, .22); }
.acct-thumb {
    position: absolute; top: var(--acct-pad); bottom: var(--acct-pad); left: var(--acct-pad);
    width: calc((100% - var(--acct-pad) * 2) / 2);
    border-radius: 999px; z-index: 0;
    background: #375534;                                   /* Student */
    transition: transform .3s cubic-bezier(.4, 0, .2, 1), background-color .3s cubic-bezier(.4, 0, .2, 1);
}
.acct-switch[data-active="faculty"] .acct-thumb {
    transform: translateX(100%);
    background: #1F3A5F;                                   /* Faculty */
}
html[data-theme] .acct-switch .acct-opt {
    position: relative; z-index: 1; display: flex; align-items: center; justify-content: center;
    margin: 0; padding: 10px 12px; border: 0; border-radius: 999px; background: transparent !important;
    box-shadow: none; cursor: pointer; user-select: none;
    font-size: .9rem; font-weight: 600; letter-spacing: .01em; color: #4b5a4f;
    transition: color .3s cubic-bezier(.4, 0, .2, 1);
}
html[data-theme] .acct-switch .acct-opt:not(.is-on):hover { color: #1a1f1c; }
/* text on the dark selection is always white */
html[data-theme] .acct-switch .acct-opt.is-on,
html[data-theme] .acct-switch .acct-opt.is-on span { color: #fff !important; }
.acct-opt input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
html[data-theme="dark"] .acct-switch { background: rgba(255, 255, 255, .08); }
html[data-theme="dark"] .acct-thumb { background: #4C7A47; }
html[data-theme="dark"] .acct-switch[data-active="faculty"] .acct-thumb { background: #3B5F94; }
html[data-theme="dark"] .acct-switch .acct-opt:not(.is-on) { color: #b4c0b8; }

/* swapped fields appear with a quick fade, no movement */
@keyframes acctFade { from { opacity: 0; } to { opacity: 1; } }
.acct-animate [data-for]:not([hidden]) { animation: acctFade .2s ease-out both; }

@media (max-width: 420px) { html[data-theme] .acct-switch .acct-opt { font-size: .82rem; padding: 10px 6px; } }
@media (prefers-reduced-motion: reduce) {
    .acct-thumb, html[data-theme] .acct-switch .acct-opt { transition: none; }
    .acct-animate [data-for]:not([hidden]) { animation: none; }
}
</style>
<script>
/* Student / Faculty switch: shows the matching fields and relabels the ID box. */
(function () {
    var radios = document.querySelectorAll('input[name="account_type"]');
    if (!radios.length) return;
    function apply() {
        var type = document.querySelector('input[name="account_type"]:checked').value;
        var fac = type === 'faculty';
        document.querySelectorAll('[data-for]').forEach(function (el) { el.hidden = el.getAttribute('data-for') !== type; });
        document.getElementById('idLabel').textContent = fac ? 'Employee ID' : 'Student ID';
        document.getElementById('student_id').placeholder = fac ? 'e.g. EMP1024' : 'e.g. 2026001';
        document.getElementById('year_level').required = !fac;
        document.getElementById('faculty_position').required = fac;
        document.getElementById('department').required = fac;
        var sw = document.getElementById('acctSeg');
        sw.setAttribute('data-active', type);
        sw.querySelectorAll('.acct-opt').forEach(function (l) {
            l.classList.toggle('is-on', l.querySelector('input').checked);
        });
    }
    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            var f = r.closest('form'); if (f) f.classList.add('acct-animate');   // fade the swapped fields in after a click
            apply();
        });
    });
    apply();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
