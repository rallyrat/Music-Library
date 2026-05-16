<?php
/**
 * logout.php
 * 
 * This file is the logout page for the application.
 * It includes the header and footer files and displays the content of the page.
 * It also includes the auth.php file to check if the user is logged in.
 * If not logged in, it will redirect to the login page.
 */
require_once __DIR__ . '/includes/auth.php';
if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}
session_destroy();
header('Location: index.php');
exit;
?>