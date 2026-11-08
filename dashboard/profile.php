<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$uid = $_SESSION['user_id'];
$errors = [];

// ---- Quick actions from the Facebook-style header: cover, avatar, status note ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['profile_action'])) {
    csrf_check();
    $action = $_POST['profile_action'];

    if ($action === 'cover' || $action === 'avatar') {
        $field  = $action === 'cover' ? 'cover_photo' : 'profile_photo';
        $subdir = $action === 'cover' ? 'covers' : 'profiles';
        $upload = handle_image_upload($field, $subdir, $action === 'cover' ? 5 * 1024 * 1024 : 3 * 1024 * 1024);
        if (!$upload['ok']) {
            set_flash('error', $upload['error']);
        } elseif ($upload['path']) {
            $old = $conn->prepare("SELECT $field FROM users WHERE user_id = ?");
            $old->bind_param('i', $uid);
            $old->execute();
            $old_path = $old->get_result()->fetch_assoc()[$field] ?? null;
            if ($old_path) delete_uploaded_image($old_path);

            $up = $conn->prepare("UPDATE users SET $field = ? WHERE user_id = ?");
            $up->bind_param('si', $upload['path'], $uid);
            $up->execute();
            if ($action === 'avatar') $_SESSION['profile_photo'] = $upload['path'];
            set_flash('success', $action === 'cover' ? 'Cover photo updated.' : 'Profile photo updated.');
        }
    } elseif ($action === 'id_submit') {
        // NORSU ID check: both sides of the school ID, then an admin approves.
        $cur = $conn->prepare("SELECT role, user_type, id_status, id_front, id_back FROM users WHERE user_id = ?");
        $cur->bind_param('i', $uid);
        $cur->execute();
        $row = $cur->get_result()->fetch_assoc();
        $cur->close();
        if (!$row || $row['role'] !== 'member' || ($row['user_type'] ?? 'student') !== 'student') {
            set_flash('error', 'ID verification is for student accounts.');
        } elseif ($row['id_status'] === 'approved') {
            set_flash('success', 'Your NORSU ID is already verified.');
        } else {
            $front = handle_private_id_upload($_FILES['id_front'] ?? []);
            $back  = $front['ok'] ? handle_private_id_upload($_FILES['id_back'] ?? []) : ['ok' => false, 'error' => ''];
            if (!$front['ok'] || !$back['ok']) {
                if ($front['ok']) delete_private_id($front['file']);   // keep it all-or-nothing
                set_flash('error', $front['ok'] ? $back['error'] : $front['error']);
            } else {
                delete_private_id($row['id_front']);
                delete_private_id($row['id_back']);
                $up = $conn->prepare("UPDATE users SET id_front = ?, id_back = ?, id_status = 'pending', id_submitted_at = NOW(), id_reviewed_at = NULL, id_reviewed_by = NULL, id_reject_reason = NULL WHERE user_id = ?");
                $up->bind_param('ssi', $front['file'], $back['file'], $uid);
                $up->execute();
                $up->close();
                set_flash('success', 'Thank you! Your NORSU ID was sent. An admin will review it, and you will get a notice here once it is approved.');
            }
        }
    } elseif ($action === 'note') {
        $note = isset($_POST['clear_note']) ? '' : mb_substr(trim((string) ($_POST['status_note'] ?? '')), 0, 60);
        $note = $note === '' ? null : $note;
        $up = $conn->prepare("UPDATE users SET status_note = ? WHERE user_id = ?");
        $up->bind_param('si', $note, $uid);
        $up->execute();
        set_flash('success', $note === null ? 'Note cleared.' : 'Note saved.');
    }
    header('Location: ' . BASE_URL . '/dashboard/profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $first_name  = clean($_POST['first_name']);
    $middle_name = clean($_POST['middle_name'] ?? '');
    $last_name   = clean($_POST['last_name']);
    $suffix      = clean($_POST['suffix'] ?? '');
    $email      = clean($_POST['email']);
    $year_level = clean($_POST['year_level']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($first_name) || empty($last_name) || empty($email) || empty($year_level)) {
        $errors[] = 'All profile fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }
        if ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirmation do not match.';
        }
    }

    $email_check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $email_check->bind_param('si', $email, $uid);
    $email_check->execute();
    if ($email_check->get_result()->num_rows > 0) {
        $errors[] = 'That email is already in use by another account.';
    }

    $photo_path = null;
    if (empty($errors)) {
        $upload = handle_image_upload('profile_photo', 'profiles');
        if (!$upload['ok']) {
            $errors[] = $upload['error'];
        } else {
            $photo_path = $upload['path'];
        }
    }

    if (empty($errors)) {
        if ($photo_path) {
            // Replacing an existing photo — remove the old file so uploads don't pile up
            $old = $conn->prepare("SELECT profile_photo FROM users WHERE user_id = ?");
            $old->bind_param('i', $uid);
            $old->execute();
            $old_photo = $old->get_result()->fetch_assoc()['profile_photo'] ?? null;
            if ($old_photo) delete_uploaded_image($old_photo);
        }

        if (!empty($new_password) && $photo_path) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, suffix=?, email=?, year_level=?, password=?, profile_photo=? WHERE user_id=?");
            $stmt->bind_param('ssssssssi', $first_name, $middle_name, $last_name, $suffix, $email, $year_level, $hashed, $photo_path, $uid);
        } elseif (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, suffix=?, email=?, year_level=?, password=? WHERE user_id=?");
            $stmt->bind_param('sssssssi', $first_name, $middle_name, $last_name, $suffix, $email, $year_level, $hashed, $uid);
        } elseif ($photo_path) {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, suffix=?, email=?, year_level=?, profile_photo=? WHERE user_id=?");
            $stmt->bind_param('sssssssi', $first_name, $middle_name, $last_name, $suffix, $email, $year_level, $photo_path, $uid);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, middle_name=?, last_name=?, suffix=?, email=?, year_level=? WHERE user_id=?");
            $stmt->bind_param('ssssssi', $first_name, $middle_name, $last_name, $suffix, $email, $year_level, $uid);
        }
        $stmt->execute();

        if (!empty($new_password)) {
            // New token version: every other browser or device is signed out; this one stays in.
            $conn->query("UPDATE users SET token_version = token_version + 1 WHERE user_id = " . (int) $uid);
            $tv = $conn->query("SELECT token_version FROM users WHERE user_id = " . (int) $uid)->fetch_assoc();
            $_SESSION['tv'] = (int) $tv['token_version'];
            session_regenerate_id(true);
            if (remember_selector() !== '') remember_issue($uid, $tv['token_version']);   // this device stays remembered
        }

        $_SESSION['first_name'] = $first_name;
        $_SESSION['middle_name'] = $middle_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['suffix'] = $suffix;
        if ($photo_path) $_SESSION['profile_photo'] = $photo_path;
        set_flash('success', 'Profile updated.');
        header('Location: ' . BASE_URL . '/dashboard/profile.php');
        exit;
    }
}

$s = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$s->bind_param('i', $uid);
$s->execute();
$me = $s->get_result()->fetch_assoc();

$qr_img = $me['student_id'];   // drawn in the browser (assets/js/qr.js)

$page_title = 'My Profile';
$attended_stmt = $conn->prepare("SELECT COUNT(*) c FROM attendance WHERE user_id = ? AND status = 'present'");
$attended_stmt->bind_param('i', $uid);
$attended_stmt->execute();
$attended_count = (int) $attended_stmt->get_result()->fetch_assoc()['c'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/profile_header.php';

$is_faculty = (($me['user_type'] ?? 'student') === 'faculty');
// Position line (committee role, or faculty position), then course - year.
// Plain student members with no position show "Member".
$position  = $me['committee_role'] ?: ($is_faculty ? ($me['faculty_position'] ?? '') : (($me['role'] ?? '') === 'member' ? 'Member' : ''));
$meta_line = e(implode(' - ', array_filter([$me['course'], $is_faculty ? '' : $me['year_level']])));

ob_start();
?>
<button type="button" class="btn btn-primary" onclick="openQrModal(event)"><svg class="icon" aria-hidden="true"><use href="#i-qr"/></svg>My QR Code</button>
<a href="#edit-profile" class="btn fb-btn-soft"><svg class="icon" aria-hidden="true"><use href="#i-pencil"/></svg>Edit profile</a>
<div class="fb-menu-wrap">
    <button type="button" class="btn fb-btn-soft fb-btn-icon" data-menu-toggle aria-haspopup="true" aria-expanded="false" aria-label="More options"><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></button>
    <div class="fb-menu" hidden>
        <label for="avatarInput" class="fb-menu-item"><svg class="icon" aria-hidden="true"><use href="#i-user"/></svg>Change profile photo</label>
        <label for="coverInput" class="fb-menu-item"><svg class="icon" aria-hidden="true"><use href="#i-camera"/></svg>Edit cover photo</label>
        <a href="#password-section" class="fb-menu-item"><svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>Change password</a>
        <a href="<?php echo BASE_URL; ?>/logout.php" class="fb-menu-item fb-menu-danger"><svg class="icon" aria-hidden="true"><use href="#i-logout"/></svg>Log out</a>
    </div>
</div>
<?php
$profile_actions = ob_get_clean();
render_profile_header($me, ['editable' => true, 'position' => $position, 'meta' => $meta_line, 'actions' => $profile_actions]);
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?php echo htmlspecialchars($err); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php
/* NORSU ID verification (students only). Shown until an admin approves the ID. */
$show_id_panel = !$is_faculty && ($me['role'] ?? '') === 'member';
$id_status = $me['id_status'] ?? 'approved';
$id_img = BASE_URL . '/dashboard/id_image.php?u=' . (int) $uid . '&amp;side=';
if ($show_id_panel && $id_status !== 'approved'):
?>
<div class="panel" id="id-verify">
    <div class="panel-head"><div class="ab-head"><h2>Verify your NORSU ID</h2>
        <span class="badge badge-outline"><?php echo $id_status === 'pending' ? 'Waiting for admin' : ($id_status === 'rejected' ? 'Needs a new photo' : 'Not sent yet'); ?></span>
    </div></div>

    <?php if ($id_status === 'pending'): ?>
        <p>Your ID was sent<?php echo !empty($me['id_submitted_at']) ? ' on ' . e(date('F j, Y g:i A', strtotime($me['id_submitted_at']))) : ''; ?>. An admin is checking it. You can open the rest of the system as soon as it is approved.</p>
        <p class="muted" style="font-size:0.85rem;">Sent the wrong photo? Send both sides again below to replace them.</p>
    <?php elseif ($id_status === 'rejected'): ?>
        <div class="alert alert-error"><strong>Your ID was not approved.</strong>
            <?php echo !empty($me['id_reject_reason']) ? e($me['id_reject_reason']) : 'Please send clearer photos of both sides.'; ?></div>
    <?php else: ?>
        <p>Before you can use the system, send a photo of the <strong>front</strong> and the <strong>back</strong> of your NORSU school ID. An admin will check that you are a NORSU student, and then approve your account.</p>
    <?php endif; ?>

    <?php if (!empty($me['id_front']) && !empty($me['id_back'])): ?>
    <div style="display:flex; gap:12px; flex-wrap:wrap; margin:12px 0;">
        <a href="<?php echo $id_img; ?>front" target="_blank" rel="noopener"><img src="<?php echo $id_img; ?>front" alt="Front of your ID" style="max-width:220px; border:1px solid var(--line); border-radius:8px;"></a>
        <a href="<?php echo $id_img; ?>back" target="_blank" rel="noopener"><img src="<?php echo $id_img; ?>back" alt="Back of your ID" style="max-width:220px; border:1px solid var(--line); border-radius:8px;"></a>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="idForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="profile_action" value="id_submit">
        <div class="form-row form-row-split">
            <div><label for="id_front">Front of ID</label><input type="file" id="id_front" name="id_front" accept=".jpg,.jpeg,.png,.webp" required></div>
            <div><label for="id_back">Back of ID</label><input type="file" id="id_back" name="id_back" accept=".jpg,.jpeg,.png,.webp" required></div>
        </div>
        <p class="muted" style="font-size:0.8rem; margin:4px 0 14px;">JPG, PNG or WEBP, up to 5MB each. Make sure your name and ID number are readable. Only you and the CSO admins can see these photos.</p>
        <button type="submit" class="btn btn-primary"><?php echo $id_status === 'pending' ? 'Replace photos' : 'Send my ID'; ?></button>
    </form>
    <script>
    // Phone photos are often 4-10 MB. Shrink each ID photo in the browser first (max 1600 px, JPEG)
    // so the upload is a few hundred KB instead of several MB, then show progress on the button.
    (function () {
        var form = document.getElementById('idForm');
        if (!form || !window.DataTransfer || !window.HTMLCanvasElement || !window.File) return;
        var MAX = 1600, busy = 0, btn = form.querySelector('button[type="submit"]'), label = btn.textContent;
        function compress(input) {
            var f = input.files && input.files[0];
            if (!f || !/^image\/(jpeg|png|webp)$/.test(f.type)) return;
            busy++; btn.disabled = true; btn.textContent = 'Preparing photo\u2026';
            var url = URL.createObjectURL(f), img = new Image();
            function done() { URL.revokeObjectURL(url); if (--busy === 0) { btn.disabled = false; btn.textContent = label; } }
            img.onload = function () {
                var w = img.naturalWidth, h = img.naturalHeight, k = Math.min(1, MAX / Math.max(w, h));
                if (k === 1 && f.size < 1.5 * 1024 * 1024) { done(); return; }   // already small enough
                var c = document.createElement('canvas');
                c.width = Math.round(w * k); c.height = Math.round(h * k);
                var g = c.getContext('2d');
                g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height);
                g.drawImage(img, 0, 0, c.width, c.height);
                c.toBlob(function (blob) {
                    if (blob && blob.size < f.size) {   // keep the original if shrinking did not help
                        var dt = new DataTransfer();
                        dt.items.add(new File([blob], f.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }));
                        input.files = dt.files;
                    }
                    done();
                }, 'image/jpeg', 0.82);
            };
            img.onerror = done;
            img.src = url;
        }
        form.querySelectorAll('input[type="file"]').forEach(function (i) { i.addEventListener('change', function () { compress(i); }); });
        form.addEventListener('submit', function () { btn.disabled = true; btn.textContent = 'Uploading\u2026 please wait'; });
    })();
    </script>
</div>
<?php elseif ($show_id_panel): ?>
<p class="muted" style="font-size:0.85rem; margin:0 0 12px;"><svg class="icon" aria-hidden="true"><use href="#i-check-circle"/></svg> NORSU ID verified</p>
<?php endif; ?>

<div class="panel" id="edit-profile">
    <div class="panel-head">
        <div class="ab-head">
            <h2>About me</h2>
            <span class="badge<?php echo $me['role'] === 'member' ? ' badge-outline' : ''; ?>"><?php echo ucfirst($me['role']); ?></span>
            <?php if (!empty($me['committee_role'])): ?>
                <span class="badge badge-outline"><?php echo htmlspecialchars($me['committee_role']); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="about-me ab-layout" aria-label="About me">
        <div class="ab-main">
            <section class="ab-group" aria-labelledby="ab-personal">
                <h3 class="ab-group-title" id="ab-personal">Personal</h3>
                <dl class="ab-list">
                    <div class="ab-row"><dt>Full name</dt><dd><?php echo e(full_name($me)); ?></dd></div>
                    <div class="ab-row"><dt>Email</dt><dd><?php echo e($me['email']); ?></dd></div>
                </dl>
            </section>

            <section class="ab-group" aria-labelledby="ab-org">
                <h3 class="ab-group-title" id="ab-org"><?php echo $is_faculty ? 'Work' : 'Academic'; ?></h3>
                <dl class="ab-list">
                    <div class="ab-row"><dt><?php echo $is_faculty ? 'Employee ID' : 'Student ID'; ?></dt><dd><?php echo e($me['student_id']); ?></dd></div>
                    <?php if ($is_faculty): ?>
                    <div class="ab-row"><dt>Department</dt><dd><?php echo e($me['course']); ?></dd></div>
                    <div class="ab-row"><dt>Position</dt><dd><?php echo e($me['faculty_position'] ?: '—'); ?></dd></div>
                    <?php else: ?>
                    <div class="ab-row"><dt>Program</dt><dd><?php echo e($me['course']); ?></dd></div>
                    <div class="ab-row"><dt>Year level</dt><dd><?php echo e($me['year_level']); ?></dd></div>
                    <?php endif; ?>
                </dl>
            </section>

            <section class="ab-group" aria-labelledby="ab-cso">
                <h3 class="ab-group-title" id="ab-cso">CSO membership</h3>
                <dl class="ab-list">
                    <div class="ab-row"><dt>Role</dt><dd><?php echo e(ucfirst($me['role'])); ?></dd></div>
                    <?php if (!empty($me['committee_role'])): ?>
                    <div class="ab-row"><dt>Committee</dt><dd><?php echo e($me['committee_role']); ?></dd></div>
                    <?php endif; ?>
                    <div class="ab-row"><dt>Status</dt><dd><span class="status status-<?php echo e($me['status'] ?? 'active'); ?>"><?php echo e(ucfirst($me['status'] ?? 'active')); ?></span></dd></div>
                    <div class="ab-row"><dt><?php echo $is_faculty ? 'Joined' : 'Member since'; ?></dt><dd><?php echo !empty($me['created_at']) ? e(date('F j, Y', strtotime($me['created_at']))) : '—'; ?></dd></div>
                    <div class="ab-row"><dt>Events attended</dt><dd><?php echo (int) $attended_count; ?></dd></div>
                </dl>
            </section>
        </div>

        <aside class="ab-card" aria-label="Check-in QR code">
            <button type="button" class="ab-qr" onclick="openQrModal(event)" aria-label="Show my QR code" title="Click to enlarge">
                <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-qr="<?php echo htmlspecialchars($qr_img); ?>" alt="QR code for ID <?php echo htmlspecialchars($me['student_id']); ?>">
            </button>
            <p class="ab-card-title">Check-in QR code</p>
            <p class="ab-card-sub">Show this to an officer at events to record your attendance.</p>
            <button type="button" class="btn btn-outline btn-sm" onclick="openQrModal(event)">Enlarge</button>
        </aside>
    </div>

    <form method="POST" id="editProfileForm"<?php echo empty($errors) ? ' hidden' : ''; ?>>
        <?php echo csrf_field(); ?>

        <div class="form-row form-row-split">
            <div><label for="fld-1">First Name</label><input id="fld-1" type="text" name="first_name" value="<?php echo htmlspecialchars($me['first_name']); ?>" required></div>
            <div><label for="fld-2">Middle Name <span class="muted" style="font-weight:400;">(optional)</span></label><input id="fld-2" type="text" name="middle_name" value="<?php echo htmlspecialchars($me['middle_name'] ?? ''); ?>"></div>
            <div><label for="fld-3">Last Name</label><input id="fld-3" type="text" name="last_name" value="<?php echo htmlspecialchars($me['last_name']); ?>" required></div>
            <div><label for="fld-4">Suffix <span class="muted" style="font-weight:400;">(optional)</span></label><input id="fld-4" type="text" name="suffix" value="<?php echo htmlspecialchars($me['suffix'] ?? ''); ?>"></div>
        </div>
        <div class="form-row"><label for="fld-5">Email</label><input id="fld-5" type="email" name="email" value="<?php echo htmlspecialchars($me['email']); ?>" required></div>
        <?php if ($is_faculty): ?>
        <input type="hidden" name="year_level" value="Faculty">
        <div class="form-row form-row-split">
            <div><label for="fld-6">Department / College</label><input id="fld-6" type="text" value="<?php echo htmlspecialchars($me['course']); ?>" disabled></div>
            <div><label for="fld-7">Position</label><input id="fld-7" type="text" value="<?php echo htmlspecialchars($me['faculty_position'] ?? ''); ?>" disabled><p class="muted" style="font-size:0.78rem; margin-top:4px;">Managed by CSO staff &mdash; contact an officer or admin to update this.</p></div>
        </div>
        <?php else: ?>
        <div class="form-row form-row-split">
            <div><label for="fld-8">Program</label><input id="fld-8" type="text" value="<?php echo htmlspecialchars($me['course']); ?>" disabled><p class="muted" style="font-size:0.78rem; margin-top:4px;">Managed by CSO staff — contact an officer or admin to update this.</p></div>
            <div>
                <label for="fld-9">Year Level</label>
                <select id="fld-9" name="year_level" required>
                    <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yr): ?>
                        <option value="<?php echo $yr; ?>" <?php echo $me['year_level'] === $yr ? 'selected' : ''; ?>><?php echo $yr; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
        <hr style="border:none; border-top:1px solid var(--line); margin:20px 0;">
        <p class="muted" id="password-section" style="font-size:0.85rem; margin-bottom:14px;">Leave the password fields blank to keep your current password.</p>
        <div class="form-row form-row-split">
            <div><label for="pf_new_password">New Password</label><div class="pw-wrap"><input type="password" id="pf_new_password" name="new_password" minlength="6" autocomplete="new-password" data-pw-new data-pw-confirm="pf_confirm_password" data-pw-rules="pw-rules-pf" aria-describedby="pw-rules-pf"><button type="button" class="pw-toggle" aria-label="Show password"><svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg></button></div></div>
            <div><label for="pf_confirm_password">Confirm New Password</label><div class="pw-wrap"><input type="password" id="pf_confirm_password" name="confirm_password" minlength="6" autocomplete="new-password"><button type="button" class="pw-toggle" aria-label="Show password"><svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg></button></div></div>
            <ul class="pw-rules" id="pw-rules-pf" aria-live="polite">
            <li data-rule="len">At least 6 characters</li>
            <li data-rule="match">Both passwords match</li>
                        </ul>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <button type="button" class="btn btn-outline" id="editProfileCancel">Cancel</button>
    </form>
</div>

<script>
/* The edit form stays hidden until "Edit profile" (or "Change password") is used. */
(function () {
    var form = document.getElementById('editProfileForm');
    if (!form) return;
    function openForm(targetId) {
        form.hidden = false;
        var t = document.getElementById(targetId) || form;
        t.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    document.querySelectorAll('a[href="#edit-profile"], a[href="#password-section"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            openForm(a.getAttribute('href').slice(1));
        });
    });
    var cancel = document.getElementById('editProfileCancel');
    if (cancel) cancel.addEventListener('click', function () {
        form.reset();
        form.hidden = true;
        document.getElementById('edit-profile').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    if (location.hash === '#edit-profile' || location.hash === '#password-section') openForm(location.hash.slice(1));
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
