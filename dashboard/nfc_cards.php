<?php
/**
 * NFC card links. Each person gets a random link that an officer writes to their card
 * with the NFC Tools app. Tapping the card with an officer's phone opens card.php.
 * Making a new link switches the old one off, which is how a lost card is cancelled.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_role(['admin', 'officer', 'moderator']);

$q = trim((string) ($_GET['q'] ?? ''));
$here = BASE_URL . '/dashboard/nfc_cards.php' . ($q !== '' ? '?q=' . urlencode($q) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $set = $conn->prepare("UPDATE users SET card_token = ? WHERE user_id = ?");

    if ($action === 'generate') {
        $user_id = (int) ($_POST['user_id'] ?? 0);
        $new = bin2hex(random_bytes(16));
        $set->bind_param('si', $new, $user_id);
        $set->execute();
        set_flash('success', 'New link made. Write it to the card with NFC Tools.');
    } elseif ($action === 'generate_missing') {
        $made = 0;
        $missing = $conn->query("SELECT user_id FROM users WHERE status = 'active' AND card_token IS NULL");
        while ($missing && ($row = $missing->fetch_assoc())) {
            $new = bin2hex(random_bytes(16));
            $id  = (int) $row['user_id'];
            $set->bind_param('si', $new, $id);
            $set->execute();
            $made++;
        }
        set_flash('success', "Made $made new link" . ($made === 1 ? '' : 's') . '.');
    }
    header('Location: ' . $here);
    exit;
}

// Links use the address this page was opened on, so they are only safe to write from the real site.
$origin    = cso_origin();
$link_base = $origin . BASE_URL . '/card.php?t=';
$host      = (string) parse_url($origin, PHP_URL_HOST);
$temporary = $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP) !== false;

$sql = "SELECT user_id, student_id, first_name, middle_name, last_name, suffix, card_token FROM users WHERE status = 'active'";
if ($q !== '') {
    $sql .= " AND (student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
}
$sql .= " ORDER BY last_name ASC, first_name ASC";
$st = $conn->prepare($sql);
if ($q !== '') {
    $like = '%' . $q . '%';
    $st->bind_param('sss', $like, $like, $like);
}
$st->execute();
$people = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = 'NFC Cards';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section">
    <div class="container">
        <h2>NFC Cards</h2>
        <p>In NFC Tools choose <strong>Write &rarr; Add a record &rarr; URL / URI</strong>, paste the person's whole link, then write it to their card. To scan, an officer taps the card with their phone: the first scan times the person in, and the next scan times them out.</p>

        <?php if ($temporary): ?>
            <div class="alert alert-warning">
                These links use <strong><?php echo e($origin); ?></strong>, a test address. Cards written with it stop working if that address changes, and they will not work on the live site, which keeps its own links. Open this page on the live site before writing real cards.
            </div>
        <?php endif; ?>

        <form method="GET" class="nfc-tools">
            <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Search name or ID" aria-label="Search name or ID">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
        </form>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="generate_missing">
            <button type="submit" class="btn btn-primary btn-sm nfc-make-all">Make links for everyone without one</button>
        </form>

        <?php if ($people): ?>
            <ul class="nfc-list">
                <?php foreach ($people as $p): ?>
                    <?php $name = full_name($p); ?>
                    <li class="nfc-person">
                        <div class="nfc-person-name"><?php echo e($name); ?></div>
                        <div class="nfc-person-id"><?php echo e($p['student_id']); ?></div>
                        <?php if ($p['card_token']): ?>
                            <input type="text" readonly class="nfc-person-link" aria-label="Card link for <?php echo e($name); ?>" value="<?php echo e($link_base . $p['card_token']); ?>" onclick="this.select();">
                        <?php endif; ?>
                        <div class="nfc-person-actions">
                            <?php if ($p['card_token']): ?>
                                <button type="button" class="btn btn-primary btn-sm" onclick="copyCardLink(this);">Copy</button>
                            <?php endif; ?>
                            <form method="POST" <?php echo $p['card_token'] ? confirm_attr('Make a new link for ' . $name . '? Their current card will stop working until it is rewritten.') : ''; ?>>
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="generate">
                                <input type="hidden" name="user_id" value="<?php echo (int) $p['user_id']; ?>">
                                <button type="submit" class="btn btn-outline btn-sm"><?php echo $p['card_token'] ? 'New link' : 'Make link'; ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Nobody found.</p>
        <?php endif; ?>
    </div>
</section>

<script>
// navigator.clipboard needs HTTPS, so plain http pages use the older copy command.
function copyCardLink(btn) {
    var input = btn.closest('.nfc-person').querySelector('.nfc-person-link');
    var done = function () { btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = 'Copy'; }, 1500); };
    input.focus();
    input.select();
    input.setSelectionRange(0, input.value.length);
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value).then(done);
    } else if (document.execCommand('copy')) {
        done();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
