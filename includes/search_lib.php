<?php
/**
 * Shared search logic, used by the live suggestions (search_suggest.php)
 * and by the full results page (search.php) so both always agree.
 *
 * Searches: sidebar pages, events, announcements and (logged-in only) members.
 */
if (!function_exists('is_logged_in')) {
    require_once __DIR__ . '/functions.php';
}

/**
 * Every page that appears in the sidebar menu for the current visitor, so the
 * menu contents can be found with search too. Mirrors includes/header.php.
 * Each item: [label, url, icon, extra search words]
 */
function search_nav_items() {
    $b    = BASE_URL;
    $role = $_SESSION['role'] ?? null;
    $in   = is_logged_in();
    $items = [];
    $add = function ($label, $path, $icon, $words = '') use (&$items, $b) {
        $items[] = ['label' => $label, 'url' => $b . $path, 'icon' => $icon, 'words' => $words];
    };

    $add('Home', '/home.php', 'home', 'main start');
    if ($in) {
        $add('Dashboard', '/dashboard/' . $role . '.php', 'grid', 'overview my account');
    }
    $add('Events', '/events.php', 'compass', 'activities upcoming happenings');
    $add('Announcements', '/announcements.php', 'megaphone', 'news updates notices');
    $add('Calendar', '/calendar.php', 'calendar', 'schedule dates agenda');
    if ($in) {
        $add('Map', '/map.php', 'map', 'campus rooms buildings directions route');
        $add('Faculty & Staff', '/directory.php', 'briefcase', 'directory teachers professors instructors');
        $add('Officers', '/officers.php', 'award', 'cso officers leaders president');
        $add('Members', '/members.php', 'user', 'students directory people');
        $add('Curriculum', '/curriculum.php', 'book', 'subjects courses bscs units prospectus');
        $add('Notifications', '/dashboard/notifications.php', 'bell', 'alerts reminders');
        $add('Attendance Attended', '/dashboard/my_attendance.php', 'checkin', 'my attendance present history');
        $add('Feedback', '/dashboard/feedback.php', 'message', 'suggestion report comment');
        $add('Instructions', '/instructions.php', 'help', 'help guide how to tutorial');
        $add('My Profile', '/dashboard/profile.php', 'user', 'account photo password edit');

        if (in_array($role, ['admin', 'officer', 'moderator'], true)) {
            $add('Manage Members', '/dashboard/members.php', 'users', 'approve signups students manage');
            $add('Manage Events', '/dashboard/events.php', 'calendar', 'create edit event');
            $add('Manage Announcements', '/dashboard/announcements.php', 'megaphone', 'post create edit announcement');
            $add('Attendance', '/dashboard/attendance.php', 'clipboard', 'scan check in record');
            $add('NFC Cards', '/dashboard/nfc_cards.php', 'clipboard', 'nfc card tag link write');
            $add('Attendance Charts', '/dashboard/attendance_stats.php', 'chart', 'statistics graph analytics');
            $add('Student Reports', '/dashboard/student_report.php', 'user', 'report student');
            $add('Map Reports', '/dashboard/map_reports.php', 'message', 'map issues wrong');
            $add('Manage Map', '/dashboard/map.php', 'map', 'edit campus rooms paths');
            if (in_array($role, ['admin', 'officer'], true)) {
                $add('Student ID List', '/dashboard/roster.php', 'idcard', 'roster student number');
                $add('Reset Codes', '/dashboard/reset_codes.php', 'shield', 'password reset');
            }
            if ($role === 'admin') {
                $add('ID Verification', '/dashboard/id_review.php', 'idcard', 'approve student id');
                $add('About CSO Section', '/dashboard/info.php', 'info', 'about organization');
            }
        }
    } else {
        $add('Curriculum', '/curriculum.php', 'book', 'subjects courses bscs units prospectus');
        $add('Log in', '/login.php', 'lock', 'sign in');
        $add('Join CSO', '/register.php', 'plus', 'register sign up create account');
    }
    return $items;
}

/** Split a query into lower-cased words (max 6). */
function search_terms($q) {
    $q = mb_strtolower(trim($q));
    $parts = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY);
    return array_slice($parts ?: [], 0, 6);
}

/** Escape % and _ so they are searched literally. */
function search_like($term) {
    return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term) . '%';
}

/**
 * Run a search. Every word typed must appear somewhere in the item, so
 * "tech summit" finds "Tech Summit 2026" and typing more narrows the list.
 * Returns ['pages'=>[], 'events'=>[], 'announcements'=>[], 'members'=>[]];
 * each entry is ['title','sub','url','icon'].
 */
function search_run($conn, $q, $limit = 10) {
    $out = ['pages' => [], 'events' => [], 'announcements' => [], 'members' => []];
    $terms = search_terms($q);
    if (!$terms) return $out;
    $b = BASE_URL;

    // 1) Sidebar pages (every word must match the label or its keywords)
    $scored = [];
    foreach (search_nav_items() as $it) {
        $label = mb_strtolower($it['label']);
        $hay   = $label . ' ' . mb_strtolower($it['words']);
        $ok = true;
        foreach ($terms as $t) { if (mb_strpos($hay, $t) === false) { $ok = false; break; } }
        if (!$ok) continue;
        $rank = 2;
        if ($label === implode(' ', $terms)) $rank = 0;
        elseif (mb_strpos($label, $terms[0]) === 0) $rank = 1;
        $scored[] = [$rank, $it];
    }
    usort($scored, function ($a, $b2) { return $a[0] <=> $b2[0]; });
    foreach (array_slice($scored, 0, $limit) as $s) {
        $out['pages'][] = ['title' => $s[1]['label'], 'sub' => 'Menu page', 'url' => $s[1]['url'], 'icon' => $s[1]['icon']];
    }

    // The database is only searched from 2 characters on.
    if (mb_strlen(implode(' ', $terms)) < 2) return $out;

    $where = function (array $cols) use ($terms, &$types, &$params) {
        $parts = [];
        foreach ($terms as $t) {
            $or = [];
            foreach ($cols as $c) { $or[] = "$c LIKE ?"; $params[] = search_like($t); $types .= 's'; }
            $parts[] = '(' . implode(' OR ', $or) . ')';
        }
        return implode(' AND ', $parts);
    };
    $run = function ($sql, $types, $params) use ($conn) {
        $st = $conn->prepare($sql);
        if (!$st) return [];
        $st->bind_param($types, ...$params);
        $st->execute();
        return $st->get_result()->fetch_all(MYSQLI_ASSOC);
    };
    $first = $terms[0];

    // 2) Events
    $types = ''; $params = [];
    $w = $where(['title', 'venue', 'description']);
    $types .= 's'; $params[] = search_like($first);   // ORDER BY: title match first
    $rows = $run("SELECT event_id, title, event_date, event_end_date, venue FROM events WHERE $w
                  ORDER BY (title LIKE ?) DESC, event_date DESC LIMIT " . (int) $limit, $types, $params);
    foreach ($rows as $r) {
        $out['events'][] = ['title' => $r['title'], 'sub' => trim(event_range_text($r) . ' · ' . $r['venue'], ' ·'),
                            'url' => $b . '/event.php?id=' . (int) $r['event_id'], 'icon' => 'calendar'];
    }

    // 3) Announcements
    $types = ''; $params = [];
    $w = $where(['title', 'content']);
    $types .= 's'; $params[] = search_like($first);
    $rows = $run("SELECT announcement_id, title, created_at FROM announcements WHERE $w
                  ORDER BY (title LIKE ?) DESC, created_at DESC LIMIT " . (int) $limit, $types, $params);
    foreach ($rows as $r) {
        $out['announcements'][] = ['title' => $r['title'], 'sub' => date('F j, Y', strtotime($r['created_at'])),
                                   'url' => $b . '/announcement.php?id=' . (int) $r['announcement_id'], 'icon' => 'megaphone'];
    }

    // 4) Members: only for logged-in, ID-verified users
    if (is_logged_in() && !id_verification_required()) {
        $types = ''; $params = [];
        $w = $where(['first_name', 'last_name', "CONCAT(first_name, ' ', last_name)"]);
        $rows = $run("SELECT user_id, first_name, middle_name, last_name, suffix, course, year_level, committee_role
                      FROM users WHERE status = 'active' AND approval_pending = 0 AND role IN ('member','officer') AND $w
                      ORDER BY last_name LIMIT " . (int) $limit, $types, $params);
        foreach ($rows as $r) {
            $sub = trim($r['course'] . ' ' . $r['year_level']);
            if (!empty($r['committee_role'])) $sub .= ($sub !== '' ? ' · ' : '') . $r['committee_role'];
            $out['members'][] = ['title' => full_name($r), 'sub' => $sub,
                                 'url' => $b . '/members.php?q=' . urlencode($r['last_name']), 'icon' => 'user'];
        }
    }
    return $out;
}
