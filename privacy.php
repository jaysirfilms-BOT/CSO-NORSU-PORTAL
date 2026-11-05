<?php
require_once __DIR__ . '/includes/functions.php';
if (file_exists(__DIR__ . '/config/site.php')) require_once __DIR__ . '/config/site.php';
if (!defined('CSO_CONTACT_EMAIL')) define('CSO_CONTACT_EMAIL', '');
$page_title = 'Privacy Policy';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container narrow">
        <h2>Privacy Policy</h2>
        <p style="color:var(--muted); font-size:0.85rem;">Last updated: <?php echo date('F Y'); ?></p>

        <div class="terms-body">
<h3>1. About This Policy</h3>
<p>This Privacy Policy explains what personal information the CSO-NORSU Portal (Computer Science Organization &mdash; Management and Information System) collects, why it is collected, who can see it, and the choices you have. It applies to students, faculty, officers, moderators, and administrators who create an account or use the portal.</p>

<h3>2. Information We Collect</h3>
<p><strong>Account details:</strong> your student ID (or employee ID for faculty), first, middle and last name, suffix, email address, program or department, year level, and position (for faculty).</p>
<p><strong>Password:</strong> stored only as a one-way encrypted hash. Nobody, including administrators, can read your password.</p>
<p><strong>Content you add:</strong> an optional profile photo, event registrations, feedback you submit, and problem reports you send about the campus map.</p>
<p><strong>Attendance records:</strong> the events you attend and your check-in and check-out times, recorded by QR scan or staff-assisted quick check-in.</p>
<p><strong>Technical information:</strong> a session cookie that keeps you logged in, and your IP address, which may be used to limit repeated verification-code or login requests.</p>

<h3>3. How We Use Your Information</h3>
<ul>
    <li>To create and manage your membership account, and to approve faculty accounts.</li>
    <li>To run events, announcements, registrations, and attendance tracking.</li>
    <li>To verify your email address and to reset your password.</li>
    <li>To respond to feedback and to fix problems reported on the campus map.</li>
    <li>To keep the portal secure and prevent misuse.</li>
</ul>

<h3>4. Emails</h3>
<p>Verification codes, password-reset codes, and account or event notices are sent to your email address through a third-party email delivery service. Only the information needed to deliver the message (your email address and the message itself) is passed to that service.</p>

<h3>5. Who Can See Your Information</h3>
<p>Officers, moderators, and administrators can see member information they need to do their duties, according to their role. Some details, such as your name, role, photo, and (for faculty) position, may be shown to other users, for example on the officers, members, and faculty listings. Please do not upload photos of other people without their consent.</p>

<h3>6. Sharing</h3>
<p>Your personal information is not sold and is not shared with anyone outside the organization, except for the email delivery service described above and where disclosure is required by law or by the university.</p>

<h3>7. Cookies and Stored Settings</h3>
<p>The portal uses a session cookie that is required for logging in. Your browser may also store display preferences, such as theme and sidebar state, and may save pages such as the campus map and curriculum on your device so they can be viewed offline. None of this is used for advertising or tracking.</p>

<h3>8. Security</h3>
<p>We protect your data with password hashing, protection against forged form submissions, and role-based access so people only reach what their role allows. No online system is completely secure, so please keep your password private and notify an officer immediately if you suspect unauthorized access to your account.</p>

<h3>9. How Long We Keep Your Information</h3>
<p>Account and attendance records are kept for as long as they are needed for CSO membership and event records. You may ask an officer or administrator to remove your account when you no longer wish to be a member, subject to records the organization is required to keep.</p>

<h3>10. Your Rights</h3>
<p>In line with the Data Privacy Act of 2012 (Republic Act No. 10173), you may ask to see the personal information we hold about you, ask for it to be corrected, object to how it is used, or ask for it to be deleted. You can update most details yourself from your profile page.</p>

<h3>11. Changes to This Policy</h3>
<p>This policy may be updated as the system changes. Continued use of the CSO-NORSU Portal after an update means you accept the revised policy.</p>

<h3>12. Contact</h3>
<p>Questions or requests about your personal information can be directed to a CSO officer, moderator, or administrator<?php if (CSO_CONTACT_EMAIL !== ''): ?>, or sent to <a href="mailto:<?php echo e(CSO_CONTACT_EMAIL); ?>"><?php echo e(CSO_CONTACT_EMAIL); ?></a><?php endif; ?>.</p>
        </div>

        <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline" style="margin-top:20px;">Back to Login</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
