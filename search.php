<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/cards.php';

$q = trim($_GET['q'] ?? '');
$q = mb_substr($q, 0, 80);
require_once __DIR__ . '/includes/search_lib.php';

$events = $anns = $people = $pages = [];
if (mb_strlen($q) >= 1) {
    $res    = search_run($conn, $q, 20);
    $pages  = $res['pages'];
    $events = $res['events'];
    $anns   = $res['announcements'];
    $people = $res['members'];
}
$total = count($pages) + count($events) + count($anns) + count($people);

$page_title = 'Search';
require_once __DIR__ . '/includes/header.php';
?>
<section class="section" style="padding-top:12px;">
    <div class="container">
        <h1 class="page-title">Search</h1>
        <form method="GET" class="inline-form" role="search" style="margin-bottom:18px;">
            <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search pages, events, announcements<?php echo is_logged_in() ? ', members' : ''; ?>" aria-label="Search the site" autofocus>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <?php if ($q !== '' && $total === 0): ?>
            <div class="empty-state"><svg class="icon empty-icon" aria-hidden="true"><use href="#i-search"/></svg><h3>No results for &ldquo;<?php echo e($q); ?>&rdquo;</h3><p>Try a different word.</p></div>
        <?php elseif ($q !== ''): ?>
            <p class="muted" style="margin-bottom:6px;"><?php echo (int) $total; ?> result<?php echo $total === 1 ? '' : 's'; ?> for &ldquo;<?php echo e($q); ?>&rdquo;</p>
            <?php
            $groups = ['Menu pages' => $pages, 'Events' => $events, 'Announcements' => $anns, 'Members' => $people];
            foreach ($groups as $label => $rows):
                if (!$rows) continue; ?>
                <h2 class="cal-agenda-title"><?php echo e($label); ?></h2>
                <ul class="cal-agenda">
                <?php foreach ($rows as $r): ?>
                    <li><a href="<?php echo e($r['url']); ?>"><strong><?php echo e($r['title']); ?></strong>
                        <?php if ($r['sub'] !== ''): ?><span class="muted"><?php echo e($r['sub']); ?></span><?php endif; ?></a></li>
                <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="muted">Search events, announcements<?php echo is_logged_in() ? ' and members' : ''; ?>. <?php echo is_logged_in() ? '' : 'Log in to search members too.'; ?></p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
