<?php
require_once 'includes/config.php';

// Set a session variable to indicate successful logout
$_SESSION['logout_success'] = true;

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page
redirect(BASE_URL . 'login.php');
