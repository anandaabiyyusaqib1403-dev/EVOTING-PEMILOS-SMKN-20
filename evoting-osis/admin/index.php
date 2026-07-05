<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$activeMenu = 'dashboard';
$pageTitle = 'Dashboard';
$pageHeading = 'Dashboard';
$pageSubheading = 'Pantau status dan partisipasi voting secara realtime.';
$stats = dashboard_stats();
$recentVotes = recent_votes();

require_once __DIR__ . '/../includes/admin-header.php';
?>
<section class="stat-grid mb-4">
    <div class="admin-card stat-card">
        <div class="stat-icon <?= voting_is_open() ? 'green' : 'red' ?>"><i class="bi bi-broadcast"></i></div>
        <span>Status Voting</span>
        <strong class="<?= voting_is_open() ? 'text-success' : 'text-danger' ?>"><?= voting_is_open() ? 'Berlangsung' : 'Ditutup' ?></strong>
        <small><?= voting_is_open() ? 'Pemilih dapat masuk dan memilih' : 'Akses pemilih sedang dikunci' ?></small>
    </div>
    <div class="admin-card stat-card">
        <div class="stat-icon blue"><i class="bi bi-people"></i></div>
        <span>Total Pemilih</span>
        <strong><?= h((string) $stats['total']) ?></strong>
        <small>Akun terdaftar</small>
    </div>
    <div class="admin-card stat-card">
        <div class="stat-icon green"><i class="bi bi-check2-circle"></i></div>
        <span>Sudah Memilih</span>
        <strong><?= h((string) $stats['voted']) ?></strong>
        <small>Suara berhasil masuk</small>
    </div>
    <div class="admin-card stat-card">
        <div class="stat-icon gray"><i class="bi bi-hourglass-split"></i></div>
        <span>Belum Memilih</span>
        <strong><?= h((string) $stats['not_voted']) ?></strong>
        <small>Menunggu menggunakan hak suara</small>
    </div>
    <div class="admin-card stat-card">
        <div class="stat-icon blue"><i class="bi bi-graph-up-arrow"></i></div>
        <span>Partisipasi</span>
        <strong><?= h((string) $stats['participation']) ?>%</strong>
        <small><?= h((string) $stats['voted']) ?> dari <?= h((string) $stats['total']) ?> pemilih</small>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-8">
        <section class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 section-heading mb-0">Grafik Perolehan Suara Realtime</h2>
                <span class="small text-secondary">Refresh otomatis</span>
            </div>
            <div style="height: 360px;">
                <canvas id="resultsChart" data-endpoint="<?= h(app_url('admin/api/results.php')) ?>"></canvas>
            </div>
        </section>
    </div>
    <div class="col-lg-4">
        <section class="admin-card">
            <h2 class="h5 section-heading mb-3">Aktivitas Terbaru</h2>
            <?php if (!$recentVotes): ?>
                <p class="muted-text mb-0">Belum ada suara masuk.</p>
            <?php endif; ?>
            <div class="activity-timeline">
                <?php foreach ($recentVotes as $vote): ?>
                    <div class="activity-item">
                        <span class="activity-check"><i class="bi bi-check2"></i></span>
                        <div>
                            <div class="activity-name"><?= h($vote['nama']) ?></div>
                            <div class="small text-secondary">Telah menggunakan hak suara</div>
                        </div>
                        <time><?= h(display_time($vote['created_at'])) ?></time>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
