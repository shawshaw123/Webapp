<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <?php if (isset($_GET['page']) && $_GET['page'] === 'admin'): ?>
    <link rel="stylesheet" href="assets/css/admin.css">
    <?php endif; ?>

</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <i class="fas fa-print"></i>
                    <span><?php echo APP_NAME; ?></span>
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                        <li><a href="index.php?page=admin">Admin Dashboard</a></li>
                        <?php else: ?>
                        <li><a href="index.php?page=dashboard">Dashboard</a></li>
                        <li><a href="index.php?page=upload">New Print Job</a></li>
                        <li><a href="index.php?page=status">Job Status</a></li>
                        <?php endif; ?>
                        <li class="user-menu">
                            <a href="#">
                                <i class="fas fa-user-circle"></i>
                                <?php echo getCurrentUserName(); ?>
                            </a>
                            <ul class="dropdown">
                                <?php if (!isAdmin()): ?>
                                <li>
                                    <span class="credits">
                                        <i class="fas fa-coins"></i>
                                        Credits: <?php 
                                            $user = getUserById(getCurrentUserId());
                                            echo formatPrice($user['credits']);
                                        ?>
                                    </span>
                                </li>
                                <?php endif; ?>
                                <li><a href="index.php?page=logout">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="index.php?page=login">Login</a></li>
                        <li><a href="index.php?page=register">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>
    
    <main>
        <div class="container">
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
            <?php endif; ?>