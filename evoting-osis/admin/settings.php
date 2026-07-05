<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'open') {
        set_setting('voting_status', 'open');
        flash('success', 'Voting berhasil dibuka.');
    }

    if ($action === 'close') {
        set_setting('voting_status', 'closed');
        flash('success', 'Voting berhasil ditutup.');
    }

    if ($action === 'reset') {
        if (trim((string) ($_POST['reset_phrase'] ?? '')) !== 'RESET') {
            flash('error', 'Reset voting dibatalkan. Ketik RESET untuk melanjutkan.');
            redirect('admin/settings.php');
        }

        $pdo = get_pdo();
        $pdo->beginTransaction();
        try {
            $pdo->exec('DELETE FROM votes');
            $pdo->exec('UPDATE pemilih SET sudah_memilih = 0, waktu_memilih = NULL, updated_at = NOW()');
            set_setting('voting_status', 'closed');
            $pdo->commit();
            flash('success', 'Reset voting berhasil.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Reset voting gagal.');
        }
    }

    redirect('admin/settings.php');
}

$stats = dashboard_stats();

$activeMenu = 'pengaturan';
$pageTitle = 'Pengaturan';
$pageHeading = 'Pengaturan';
$pageSubheading = 'Kelola data, kontrol pemungutan suara, dan tindakan sistem dengan hati-hati.';
require_once __DIR__ . '/../includes/admin-header.php';
?>
<section class="settings-section">
    <h2 class="section-heading">Data Management</h2>
    <div class="settings-grid">
        <a class="action-card" href="<?= h(app_url('admin/voters.php')) ?>">
            <span><i class="bi bi-file-earmark-spreadsheet"></i></span>
            <strong>Import Pemilih</strong>
            <small>Unggah data pemilih siswa dan guru.</small>
        </a>
        <a class="action-card success" href="<?= h(app_url('admin/export.php')) ?>">
            <span><i class="bi bi-download"></i></span>
            <strong>Export Hasil</strong>
            <small>Unduh rekap hasil tanpa data pilihan pemilih.</small>
        </a>
    </div>
</section>

<section class="settings-section">
    <h2 class="section-heading">Voting Control</h2>
    <div class="settings-grid">
        <form method="post" class="action-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="open">
            <button type="submit">
                <span><i class="bi bi-play-circle"></i></span>
                <strong>Mulai Voting</strong>
                <small>Status saat ini: <?= h($stats['status']) ?></small>
            </button>
        </form>
        <form method="post" class="action-form danger" data-confirm-title="Tutup voting?" data-confirm="Akses pemilih akan dikunci sampai voting dibuka kembali.">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="close">
            <button type="submit">
                <span><i class="bi bi-stop-circle"></i></span>
                <strong>Tutup Voting</strong>
                <small>Kunci akses pemilih setelah selesai.</small>
            </button>
        </form>
    </div>
</section>

<section class="settings-section">
    <h2 class="section-heading">System</h2>
    <div class="settings-grid">
        <form method="post" class="action-form danger" data-confirm-title="Reset Voting?" data-confirm="Tindakan ini akan menghapus seluruh suara masuk dan membuka kembali hak pilih seluruh pemilih." data-confirm-phrase="RESET">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="reset">
            <button type="submit">
                <span><i class="bi bi-arrow-counterclockwise"></i></span>
                <strong>Reset Voting</strong>
                <small>Hapus suara dan buka ulang hak pilih.</small>
            </button>
        </form>
        <a class="action-card" href="<?= h(app_url('admin/backup.php')) ?>">
            <span><i class="bi bi-database-down"></i></span>
            <strong>Backup Database</strong>
            <small>Unduh backup data sistem dalam format SQL.</small>
        </a>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
