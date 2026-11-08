<?php
/**
 * Live search suggestions for the header search bar.
 * GET ?q=...  ->  JSON { q, groups: [ {label, items:[{title,sub,url,icon}]} ] }
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/search_lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$q = mb_substr(trim($_GET['q'] ?? ''), 0, 80);
$groups = [];
if ($q !== '') {
    try {
        $res = search_run($conn, $q, 4);
        $labels = ['pages' => 'Menu', 'events' => 'Events', 'announcements' => 'Announcements', 'members' => 'Members'];
        foreach ($labels as $key => $label) {
            if ($res[$key]) $groups[] = ['label' => $label, 'items' => $res[$key]];
        }
    } catch (Throwable $e) {
        $groups = [];
    }
}
echo json_encode(['q' => $q, 'groups' => $groups], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
