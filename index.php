<?php
/**
 * Entry point of the app.
 * Everyone lands on the public Home page — logged in or not. It carries the
 * events, officers and announcements previews, plus Log in / Join CSO in the
 * header for people who want to sign in.
 */
require_once __DIR__ . '/includes/functions.php';

redirect_home();
