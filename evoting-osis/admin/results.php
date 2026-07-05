<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$results = candidate_results();
$totalVotes = array_sum(array_map(fn ($row) => (int) $row['total_suara'], $results));
$stats = dashboard_stats();
$rankedResults = $results;
usort($rankedResults, fn ($a, $b) => (int) $b['total_suara'] <=> (int) $a['total_suara']);

$activeMenu = 'hasil';
$pageTitle = 'Hasil Voting';
$pageHeading = 'Pusat Rekapitulasi Suara';
$pageSubheading = 'Pantau hasil pemilihan secara realtime tanpa membuka kerahasiaan pilihan pemilih.';
require_once __DIR__ . '/../includes/admin-header.php';
?>
<section class="stat-grid results-stat-grid mb-4">
    <div class="admin-card stat-card">
        <div class="stat-icon blue"><i class="bi bi-inboxes"></i></div>
        <span>Total Suara Masuk</span>
        <strong><?= h((string) $totalVotes) ?></strong>
    </div>
    <div class="admin-card stat-card">
        <div class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></div>
        <span>Partisipasi</span>
        <strong><?= h((string) $stats['participation']) ?>%</strong>
        <small><?= h((string) $stats['voted']) ?> dari <?= h((string) $stats['total']) ?> pemilih</small>
    </div>
    <div class="admin-card stat-card">
        <div class="stat-icon gold"><i class="bi bi-person-badge"></i></div>
        <span>Jumlah Kandidat</span>
        <strong><?= h((string) count($results)) ?></strong>
    </div>
</section>

<section class="admin-card mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
        <div>
            <h2 class="h5 section-heading mb-1">Grafik Realtime</h2>
            <p class="muted-text mb-0">Total suara masuk: <?= h((string) $totalVotes) ?></p>
        </div>
        <a class="btn btn-success" href="<?= h(app_url('admin/export.php')) ?>">
            <i class="bi bi-download"></i> Download Rekap Excel
        </a>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="chart-box">
                <canvas id="resultsChart" data-endpoint="<?= h(app_url('admin/api/results.php')) ?>"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-box">
                <canvas id="resultsPieChart" data-endpoint="<?= h(app_url('admin/api/results.php')) ?>"></canvas>
            </div>
        </div>
    </div>
</section>

<section class="admin-card">
    <h2 class="h5 section-heading mb-3">Ranking Hasil</h2>
    <div class="ranking-list">
        <?php foreach ($rankedResults as $index => $row): ?>
            <div class="ranking-item">
                <span class="ranking-number"><?= h((string) ($index + 1)) ?></span>
                <img class="photo-thumb" src="<?= h(candidate_photo_url($row['foto'])) ?>" alt="">
                <div class="ranking-main">
                    <div class="table-name"><?= h($row['nama_ketua']) ?> - <?= h($row['nama_wakil']) ?></div>
                    <div class="small text-secondary">Nomor urut <?= h((string) $row['nomor_urut']) ?></div>
                </div>
                <div class="ranking-score">
                    <strong><?= h((string) $row['total_suara']) ?> suara</strong>
                    <span><?= h(percentage((int) $row['total_suara'], $totalVotes)) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$rankedResults): ?>
            <p class="text-center text-secondary py-4 mb-0">Belum ada kandidat.</p>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
