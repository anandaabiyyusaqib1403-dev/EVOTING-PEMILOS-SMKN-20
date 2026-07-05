<?php
require_once __DIR__ . '/functions.php';
require_admin();

$activeMenu = $activeMenu ?? 'dashboard';
$menus = [
    'dashboard' => ['Dashboard', 'bi-speedometer2', 'admin/index.php'],
    'pemilih' => ['Pemilih', 'bi-people', 'admin/voters.php'],
    'kandidat' => ['Kandidat', 'bi-person-badge', 'admin/candidates.php'],
    'hasil' => ['Hasil Voting', 'bi-bar-chart', 'admin/results.php'],
    'pengaturan' => ['Pengaturan', 'bi-gear', 'admin/settings.php'],
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle ?? 'Admin - ' . APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= h(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a href="<?= h(app_url('admin/index.php')) ?>" class="admin-brand">
            <img src="<?= h(app_url('assets/img/logo-smkn20.png')) ?>" alt="Logo SMKN 20 Jakarta" class="admin-brand-logo">
            <span>
                <strong>E-Voting OSIS</strong>
                <small><?= h(SCHOOL_NAME) ?></small>
            </span>
        </a>
        <button class="admin-menu-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavigation" aria-expanded="false" aria-controls="adminNavigation">
            <i class="bi bi-list"></i>
            <span>Menu</span>
        </button>
        <nav class="admin-nav collapse" id="adminNavigation">
            <?php foreach ($menus as $key => $menu): ?>
                <a class="<?= $activeMenu === $key ? 'active' : '' ?>" href="<?= h(app_url($menu[2])) ?>">
                    <i class="bi <?= h($menu[1]) ?>"></i>
                    <span><?= h($menu[0]) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1><?= h($pageHeading ?? $pageTitle ?? 'Dashboard') ?></h1>
                <p><?= h($pageSubheading ?? 'Kelola pemilihan Ketua OSIS dengan cepat dan aman.') ?></p>
            </div>
            <div class="topbar-actions">
                <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= h(date('d M Y')) ?></span>
                <span class="admin-user"><i class="bi bi-person-circle"></i> <?= h($_SESSION['admin_name'] ?? 'Admin') ?></span>
                <span class="status-pill <?= voting_is_open() ? 'open' : 'closed' ?>">
                    <?= voting_is_open() ? 'Voting Dibuka' : 'Voting Ditutup' ?>
                </span>
                <a href="<?= h(app_url('admin/logout.php')) ?>" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
            </div>
        </header>
        <?php if ($message = flash('success')): ?>
            <div class="alert alert-success"><?= h($message) ?></div>
        <?php endif; ?>
        <?php if ($message = flash('error')): ?>
            <div class="alert alert-danger"><?= h($message) ?></div>
        <?php endif; ?>
