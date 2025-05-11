<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$isAdmin = $isLoggedIn && isAdmin();

// Handle specific page requests
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowedPages = ['home', 'login', 'register', 'dashboard', 'upload', 'admin', 'logout', 'print-options', 'payment', 'status'];

// Ensure page is in allowed list
if (!in_array($page, $allowedPages)) {
    $page = 'home';
}

// Check access permissions for pages
if (($page == 'dashboard' || $page == 'upload' || $page == 'print-options' || $page == 'payment' || $page == 'status') && !$isLoggedIn) {
    header("Location: index.php?page=login");
    exit;
}

if ($page == 'admin' && !$isAdmin) {
    header("Location: index.php?page=dashboard");
    exit;
}

if ($page == 'logout') {
    logout();
    header("Location: index.php?page=login");
    exit;
}

// Include page header
include 'includes/header.php';

// Include the appropriate page content
include 'pages/' . $page . '.php';

// Include page footer
include 'includes/footer.php';
?>