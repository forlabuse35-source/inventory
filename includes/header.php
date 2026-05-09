<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
requireLogin();
$currentUser = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? 'Dashboard') ?> — <?= sanitize(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-boxes-stacked"></i>
                <span>InvenTrack</span>
            </div>
            <button class="sidebar-close" id="sidebarClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
            <a href="products.php" class="nav-link <?= $currentPage === 'products' ? 'active' : '' ?>">
                <i class="fas fa-cube"></i>
                <span>Products</span>
            </a>
            <a href="stock.php" class="nav-link <?= $currentPage === 'stock' ? 'active' : '' ?>">
                <i class="fas fa-warehouse"></i>
                <span>Stock Management</span>
            </a>
            <a href="sales.php" class="nav-link <?= $currentPage === 'sales' ? 'active' : '' ?>">
                <i class="fas fa-cash-register"></i>
                <span>Sales / Billing</span>
            </a>
            <?php if (isAdmin()): ?>
            <a href="users.php" class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                <i class="fas fa-users-gear"></i>
                <span>Users</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info-mini">
                <div class="user-avatar-small"><?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?></div>
                <div>
                    <div class="user-name-small"><?= sanitize($currentUser['full_name']) ?></div>
                    <div class="user-role-small"><?= ucfirst($currentUser['role']) ?></div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title"><?= sanitize($pageTitle ?? 'Dashboard') ?></h1>
            </div>
            <div class="header-right">
                <div class="header-user" id="headerUserBtn">
                    <div class="user-avatar"><?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?></div>
                    <span class="user-name"><?= sanitize($currentUser['full_name']) ?></span>
                    <i class="fas fa-chevron-down"></i>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="dropdown-header">
                            <strong><?= sanitize($currentUser['full_name']) ?></strong>
                            <small><?= ucfirst($currentUser['role']) ?></small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="content">
            <?php if ($flash = getFlash('success')): ?>
                <div class="alert alert-success"><?= sanitize($flash) ?></div>
            <?php endif; ?>
            <?php if ($flash = getFlash('error')): ?>
                <div class="alert alert-danger"><?= sanitize($flash) ?></div>
            <?php endif; ?>
