<?php
/**
 * NFC card tap. Each person's card holds a link with a random token (made in
 * dashboard/nfc_cards.php). When an officer's phone taps the card it opens this page,
 * which records Time In or Time Out, whichever the officer has selected. With Time In
 * selected, scanning someone who is already timed in times them out.
 *
 * Only a logged-in officer can record, so a card or a copied link is useless on its own:
 * a student cannot time themselves in from anywhere. The event is the one the officer
 * chose, because the phone cannot work it out from the card.
 *
 * Results are shown after a redirect, so reloading the page never records a second time.
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';
require_role(['admin', 'officer', 'moderator']);

// Phones can read a card twice in one tap. A repeat this soon after Time In is ignored,
// so nobody is timed in and straight back out.
const CARD_REPEAT_SECONDS = 10;

$uid   = (int) $_SESSION['user_id'];
$token = (string) ($_GET['t'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $token)) $token = '';
$self  = BASE_URL . '/card.php';

// The officer picks the event and Time In or Time Out for this session. The Attendance
// page sends its own Time In / Time Out buttons here too, so both stay the same.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['event_id'])) $_SESSION['card_event_id'] = (int) $_POST['event_id'];
    if (isset($_POST['mode'])) $_SESSION['card_mode'] = $_POST['mode'] === 'out' ? 'out' : 'in';
    unset($_SESSION['card_result']);
    header('Location: ' . $self . ($token !== '' ? '?t=' . $token : ''));
    exit;
}
$mode = ($_SESSION['card_mode'] ?? 'in') === 'out' ? 'out' : 'in';

$event = null;
if (!empty($_SESSION['card_event_id']) && !isset($_GET['change'])) {
    $es = $conn->prepare("SELECT event_id, title FROM events WHERE event_id = ?");
    $es->bind_param('i', $_SESSION['card_event_id']);
    $es->execute();
    $event = $es->get_result()->fetch_assoc();
}

if ($token !== '' && $event) {
    $event_id = (int) $event['event_id'];
    $u = $conn->prepare("SELECT user_id, student_id, first_name, middle_name, last_name, suffix, course, year_level, status FROM users WHERE card_token = ?");
    $u->bind_param('s', $token);
    $u->execute();
    $person = $u->get_result()->fetch_assoc();

    if (!$person) {
        $state = 'unknown';
    } elseif ($person['status'] !== 'active') {
        $state = 'inactive';
    } else {
        $user_id = (int) $person['user_id'];
        $chk = $conn->prepare("SELECT status, time_out FROM attendance WHERE event_id = ? AND user_id = ?");
        $chk->bind_param('ii', $event_id, $user_id);
        $chk->execute();
        $row = $chk->get_result()->fetch_assoc();

        $present = $row && $row['status'] === 'present';

        // Each write carries its own condition, so a phone reading a tag twice in one tap counts once.
        if ($mode === 'out') {
            if (!$present) {
                $state = 'not_in';
            } else {
                $q = $conn->prepare("UPDATE attendance SET time_out = NOW(), recorded_by = ? WHERE event_id = ? AND user_id = ? AND time_out IS NULL");
                $q->bind_param('iii', $uid, $event_id, $user_id);
                $q->execute();
                $state = $q->affected_rows === 1 ? 'out' : 'already_out';
            }
        } elseif ($present && $row['time_out']) {
            $state = 'already_out';
        } elseif ($present) {
            $wait = CARD_REPEAT_SECONDS;
            $q = $conn->prepare("UPDATE attendance SET time_out = NOW(), recorded_by = ? WHERE event_id = ? AND user_id = ? AND time_out IS NULL AND TIMESTAMPDIFF(SECOND, checked_in_at, NOW()) >= ?");
            $q->bind_param('iiii', $uid, $event_id, $user_id, $wait);
            $q->execute();
            $state = $q->affected_rows === 1 ? 'out' : 'already_in';
        } elseif (!$row) {
            $q = $conn->prepare("INSERT IGNORE INTO attendance (event_id, user_id, status, recorded_by, checked_in_at) VALUES (?, ?, 'present', ?, NOW())");
            $q->bind_param('iii', $event_id, $user_id, $uid);
            $q->execute();
            $state = $q->affected_rows === 1 ? 'in' : 'already_in';
        } else {
            $q = $conn->prepare("UPDATE attendance SET status = 'present', recorded_by = ?, checked_in_at = NOW(), time_out = NULL WHERE event_id = ? AND user_id = ? AND status <> 'present'");
            $q->bind_param('iii', $uid, $event_id, $user_id);
            $q->execute();
            $state = $q->affected_rows === 1 ? 'in' : 'already_in';
        }
    }

    $_SESSION['card_result'] = [
        'state'      => $state,
        'name'       => $person ? full_name($person) : '',
        'student_id' => $person['student_id'] ?? '',
        'program'    => $person ? $person['course'] . ' — ' . $person['year_level'] : '',
        'event'      => $event['title'],
        'time'       => date('g:i A'),
    ];
    header('Location: ' . $self);
    exit;
}

$events = [];
if (!$event) {
    $er = $conn->query("SELECT event_id, title, event_date FROM events ORDER BY event_date IS NULL, event_date DESC, event_id DESC LIMIT 30");
    if ($er) $events = $er->fetch_all(MYSQLI_ASSOC);
}

$result = $event ? ($_SESSION['card_result'] ?? null) : null;
$headlines = [
    'in'          => ['ok',   'Timed in'],
    'out'         => ['ok',   'Timed out'],
    'already_in'  => ['warn', 'Already timed in'],
    'already_out' => ['warn', 'Already timed out'],
    'not_in'      => ['bad',  'Not timed in yet'],
    'unknown'     => ['bad',  'This card is not valid'],
    'inactive'    => ['bad',  'This account is not active'],
];

// The Time In / Time Out switch shown on the scan page.
function card_mode_buttons($self, $mode) {
    ?>
    <form method="POST" action="<?php echo e($self); ?>" class="card-mode">
        <?php echo csrf_field(); ?>
        <button type="submit" name="mode" value="in" class="btn <?php echo $mode === 'in' ? 'btn-primary' : 'btn-outline'; ?>" aria-pressed="<?php echo $mode === 'in' ? 'true' : 'false'; ?>">Time In</button>
        <button type="submit" name="mode" value="out" class="btn <?php echo $mode === 'out' ? 'btn-primary' : 'btn-outline'; ?>" aria-pressed="<?php echo $mode === 'out' ? 'true' : 'false'; ?>">Time Out</button>
    </form>
    <?php
}

$page_title = 'NFC Card';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section auth-section card-page">
    <div class="container narrow" style="text-align:center;">
        <?php if (!$event): ?>
            <h2>Which event is this for?</h2>
            <p>Cards will record attendance for the event you choose until you change it.</p>
            <?php if ($events): ?>
                <form method="POST" action="<?php echo e($self . ($token !== '' ? '?t=' . $token : '')); ?>" class="form" style="text-align:left;">
                    <?php echo csrf_field(); ?>
                    <label for="card-event">Event</label>
                    <select id="card-event" name="event_id" required>
                        <?php foreach ($events as $ev): ?>
                            <option value="<?php echo (int) $ev['event_id']; ?>">
                                <?php echo e($ev['title']); ?><?php echo $ev['event_date'] ? ' — ' . e(date('M j, Y', strtotime($ev['event_date']))) : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="card-mode">
                        <button type="submit" name="mode" value="in" class="btn <?php echo $mode === 'in' ? 'btn-primary' : 'btn-outline'; ?>">Time In</button>
                        <button type="submit" name="mode" value="out" class="btn <?php echo $mode === 'out' ? 'btn-primary' : 'btn-outline'; ?>">Time Out</button>
                    </div>
                </form>
            <?php else: ?>
                <p>There are no events yet. Add one first.</p>
            <?php endif; ?>
        <?php elseif ($result): ?>
            <?php [$tone, $headline] = $headlines[$result['state']]; ?>
            <div class="checkin-badge <?php echo $tone === 'ok' ? 'checkin-success' : 'checkin-error'; ?>">
                <?php if ($tone === 'ok'): ?><svg class="icon" aria-hidden="true"><use href="#i-check"/></svg><?php else: ?>!<?php endif; ?>
            </div>
            <h2 class="card-headline card-headline-<?php echo e($tone); ?>"><?php echo e($headline); ?></h2>
            <?php if ($result['name'] !== ''): ?>
                <p class="card-person-name"><?php echo e($result['name']); ?></p>
            <?php endif; ?>
            <?php if ($result['state'] === 'not_in'): ?>
                <p>Nothing was recorded. Select Time In first.</p>
            <?php elseif ($result['state'] === 'unknown'): ?>
                <p>Nothing was recorded. This link may have been replaced. Make a new one in NFC Cards.</p>
            <?php endif; ?>

            <div class="checkin-card">
                <?php if ($result['name'] !== ''): ?>
                    <div class="checkin-row"><span class="checkin-label">Student ID</span><span class="checkin-value"><?php echo e($result['student_id']); ?></span></div>
                    <div class="checkin-row"><span class="checkin-label">Program / Year</span><span class="checkin-value"><?php echo e($result['program']); ?></span></div>
                    <hr>
                <?php endif; ?>
                <div class="checkin-row"><span class="checkin-label">Event</span><span class="checkin-value"><?php echo e($result['event']); ?></span></div>
                <div class="checkin-row"><span class="checkin-label">Time</span><span class="checkin-value"><?php echo e($result['time']); ?></span></div>
            </div>
        <?php else: ?>
            <h2>Ready to scan</h2>
            <p>Select Time In or Time Out, then hold the back of the phone against a card.</p>
            <p><strong><?php echo e($event['title']); ?></strong></p>
            <?php card_mode_buttons($self, $mode); ?>
            <a href="<?php echo e($self); ?>?change=1" class="btn btn-outline btn-sm">Change event</a>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
