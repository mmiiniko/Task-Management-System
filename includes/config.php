<?php
session_start();
require_once 'db.php';
require_once 'functions.php';
require_once 'helpers.php';

// Define constants
define('BASE_URL', '');
define('ADMIN_URL', BASE_URL . 'admin');
define('USER_URL', BASE_URL . 'user');