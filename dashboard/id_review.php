<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/otp.php';
require_once __DIR__ . '/../includes/features.php';
require_role('admin');

$admin_id = (int) $_SESSION['user_id'];

// ---------------------------------------------------------
// Approve or reject a submitted NORSU ID
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $tid    = (int) ($_POST['user_id'] ?? 0);

    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, suffix, email, id_status, id_front, id_back FROM users WHERE user_id = ? AND role = 'member' AND user_type = 'student'");
    $stmt->bind_param('i', $tid);
    $stmt->execute();
    $who = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$who || empty($who['id_front']) || empty($who['id_back'])) {
        set_flash('error', 'That student has not sent an ID.');
    } elseif ($action === 'approve') {
        $up = $conn->prepare("UPDATE users SET id_status = 'approved', id_reviewed_at = NOW(), id_reviewed_by = ?, id_reject_reason = NULL, status = 'active', approval_pending = 0 WHERE user_id = ?");
        $up->bind_param('ii', $admin_id, $tid);
        $up->execute();
        $up->close();
        add_notice($conn, $tid, 'Your NORSU ID was verified. Your account is now fully active.');
        cso_send_mail($who['email'], full_name($who), 'Your NORSU ID was verified',
            "Hello " . full_name($who) . ",\n\nA CSO admin verified your NORSU ID. You can now use the whole CSO system.\n\nCSO-NORSU");
        set_flash('success', full_name($who) . ' is verified.');
    } elseif ($action === 'reject') {
        $reason = mb_substr(clean($_POST['reason'] ?? ''), 0, 250);
        if ($reason === '') $reason = 'Please send clear photos of both the front and back of your NORSU ID.';
        $up = $conn->prepare("UPDATE users SET id_status = 'rejected', id_reviewed_at = NOW(), id_reviewed_by = ?, id_reject_reason = ? WHERE user_id = ?");
        $up->bind_param('isi', $admin_id, $reason, $tid);
        $up->execute();
        $up->close();
        add_notice($conn, $tid, 'Your NORSU ID was not approved: ' . $reason . ' Send new photos from your profile.');
        cso_send_mail($who['email'], full_name($who), 'Your NORSU ID needs a new photo',
            "Hello " . full_name($who) . ",\n\nA CSO admin could not approve your NORSU ID.\nReason: $reason\n\nPlease log in, open your profile, and send new photos of the front and back of your ID.\n\nCSO-NORSU");
        set_flash('success', 'Sent back to ' . full_name($who) . ' with your reason.');
    }
    header('Location: ' . BASE_URL . '/dashboard/id_review.php?show=' . urlencode($_POST['show'] ?? 'pending'));
    exit;
}

$show = $_GET['show'] ?? 'pending';
if (!in_array($show, ['pending', 'rejected', 'approved', 'none'], true)) $show = 'pending';

$counts = ['pending' => 0, 'rejected' => 0, 'approved' => 0, 'none' => 0];
$cr = $conn->query("SELECT id_status, COUNT(*) c FROM users WHERE role = 'member' AND user_type = 'student' GROUP BY id_status");
while ($cr && ($c = $cr->fetch_assoc())) $counts[$c['id_status']] = (int) $c['c'];

$list = $conn->prepare("SELECT user_id, student_id, first_name, middle_name, last_name, suffix, email, course, year_level, id_status, id_front, id_back, id_submitted_at, id_reviewed_at, id_reject_reason
                        FROM users WHERE role = 'member' AND user_type = 'student' AND id_status = ?
                        ORDER BY id_submitted_at DESC, last_name LIMIT 100");
$list->bind_param('s', $show);
$list->execute();
$rows = $list->get_result();

$page_title = 'ID Verification';
require_once __DIR__ . '/../includes/header.php';
$labels = ['pending' => 'Waiting', 'rejected' => 'Sent back', 'approved' => 'Verified', 'none' => 'Not sent'];
?>
<h1 class="sr-only">ID Verification</h1>
<div class="panel">
    <div class="panel-head"><h2>NORSU ID verification</h2></div>
    <p class="muted">Compare the name and ID number on the photos with the account details, then approve or send it back with a reason.</p>
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin:12px 0;">
        <?php foreach ($labels as $key => $label): ?>
            <a class="btn <?php echo $show === $key ? 'btn-primary' : 'btn-outline'; ?> btn-sm" href="?show=<?php echo $key; ?>"><?php echo $label; ?> (<?php echo $counts[$key]; ?>)</a>
        <?php endforeach; ?>
    </div>

    <?php if ($rows && $rows->num_rows > 0): while ($r = $rows->fetch_assoc()): $img = BASE_URL . '/dashboard/id_image.php?u=' . (int) $r['user_id'] . '&amp;side='; ?>
        <div style="border-top:1px solid var(--line); padding:16px 0;">
            <p style="margin:0 0 6px;"><strong><?php echo e(full_name($r)); ?></strong> &middot; Student ID <strong><?php echo e($r['student_id']); ?></strong></p>
            <p class="muted" style="margin:0 0 10px; font-size:0.85rem;">
                <?php echo e($r['course']); ?> &middot; <?php echo e($r['year_level']); ?> &middot; <?php echo e($r['email']); ?>
                <?php if (!empty($r['id_submitted_at'])): ?> &middot; sent <?php echo e(date('M j, Y g:i A', strtotime($r['id_submitted_at']))); ?><?php endif; ?>
            </p>
            <?php if (!empty($r['id_front']) && !empty($r['id_back'])): ?>
            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
                <a href="<?php echo $img; ?>front" target="_blank" rel="noopener"><img src="<?php echo $img; ?>front" alt="Front of ID" style="max-width:300px; width:100%; border:1px solid var(--line); border-radius:8px;"></a>
                <a href="<?php echo $img; ?>back" target="_blank" rel="noopener"><img src="<?php echo $img; ?>back" alt="Back of ID" style="max-width:300px; width:100%; border:1px solid var(--line); border-radius:8px;"></a>
            </div>
            <?php if ($r['id_status'] === 'rejected' && !empty($r['id_reject_reason'])): ?>
                <p class="muted" style="font-size:0.85rem;">Sent back: <?php echo e($r['id_reject_reason']); ?></p>
            <?php endif; ?>
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <?php if ($r['id_status'] !== 'approved'): ?>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="user_id" value="<?php echo (int) $r['user_id']; ?>">
                    <input type="hidden" name="show" value="<?php echo e($show); ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="user_id" value="<?php echo (int) $r['user_id']; ?>">
                    <input type="hidden" name="show" value="<?php echo e($show); ?>">
                    <input type="text" name="reason" maxlength="250" placeholder="Reason (e.g. back side is blurry)" style="min-width:240px;">
                    <button type="submit" class="btn btn-outline btn-sm"><?php echo $r['id_status'] === 'approved' ? 'Ask for a new ID' : 'Send back'; ?></button>
                </form>
            </div>
            <?php else: ?>
                <p class="muted" style="font-size:0.85rem;">No ID sent yet.</p>
            <?php endif; ?>
        </div>
    <?php endwhile; else: ?>
        <div class="empty-state"><h3>Nothing here</h3></div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
