<?php
/**
 * Shows one side of a NORSU ID photo. The photos live in private/ids (not directly
 * reachable from a browser), so this is the only way to see them: the owner of the
 * ID and admins only.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_login();

$target = (int) ($_GET['u'] ?? 0);
$side   = ($_GET['side'] ?? '') === 'back' ? 'id_back' : 'id_front';
$me     = (int) $_SESSION['user_id'];

if ($target <= 0 || ($target !== $me && ($_SESSION['role'] ?? '') !== 'admin')) {
    http_response_code(403);
    exit('Not allowed.');
}

$stmt = $conn->prepare("SELECT $side AS f FROM users WHERE user_id = ?");
$stmt->bind_param('i', $target);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$file = basename((string) ($row['f'] ?? ''));
$path = private_id_dir() . $file;
if ($file === '' || !is_file($path)) {
    http_response_code(404);
    exit('No photo.');
}

$types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
