<?php
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['verified_voter_id'])) {
    redirect('candidates.php');
}

if (empty($_SESSION['pending_voter_id'])) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'reject') {
        unset($_SESSION['pending_voter_id'], $_SESSION['verified_voter_id'], $_SESSION['pemilih_id']);
        flash('info', 'Silakan masukkan kembali NIS/NIP Anda');
        redirect('login.php');
    }

    if ($action === 'confirm') {
        $stmt = get_pdo()->prepare('SELECT id, sudah_memilih FROM pemilih WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['pending_voter_id']]);
        $pemilih = $stmt->fetch();

        if (!$pemilih) {
            unset($_SESSION['pending_voter_id']);
            flash('error', 'Data tidak ditemukan');
            redirect('login.php');
        }

        if (!voting_is_open()) {
            unset($_SESSION['pending_voter_id']);
            flash('error', 'Pemungutan suara sedang ditutup');
            redirect('login.php');
        }

        if ((int) $pemilih['sudah_memilih'] === 1) {
            unset($_SESSION['pending_voter_id']);
            flash('error', 'Anda sudah menggunakan hak suara');
            redirect('login.php');
        }

        $voterId = (int) $pemilih['id'];
        session_regenerate_id(true);
        unset($_SESSION['pending_voter_id'], $_SESSION['pemilih_id']);
        $_SESSION['verified_voter_id'] = $voterId;
        redirect('candidates.php');
    }

    flash('error', 'Terjadi kesalahan, silakan coba lagi');
    redirect('identity-confirmation.php');
}

$pemilih = pending_voter();
$pageTitle = 'Verifikasi Identitas - ' . APP_NAME;
require_once __DIR__ . '/includes/public-header.php';
?>
<main class="public-wrap">
    <section class="auth-panel identity-panel">
        <div class="text-center mb-4">
            <img src="<?= h(app_url('assets/img/logo-smkn20.png')) ?>" alt="Logo SMKN 20 Jakarta" class="auth-logo mb-3">
            <span class="auth-badge">Konfirmasi Pemilih</span>
            <h1 class="auth-title mb-2">Verifikasi Identitas</h1>
            <p class="muted-text mb-0">Pastikan data berikut sudah sesuai sebelum menggunakan hak suara Anda.</p>
        </div>

        <?php if ($message = flash('error')): ?>
            <div class="alert alert-danger"><?= h($message) ?></div>
        <?php endif; ?>

        <?php if (!$pemilih): ?>
            <?php unset($_SESSION['pending_voter_id']); ?>
            <div class="identity-alert">
                <i class="bi bi-exclamation-triangle"></i>
                <div>
                    <strong>Data tidak ditemukan</strong>
                    <p>Silakan kembali ke halaman login dan masukkan ulang NIS/NIP Anda.</p>
                </div>
            </div>
            <a href="<?= h(app_url('login.php')) ?>" class="btn btn-primary w-100 mt-3">Kembali ke Login</a>
        <?php else: ?>
            <div class="identity-card">
                <div class="identity-user-icon">
                    <i class="bi bi-person-check"></i>
                </div>
                <div class="identity-name-block">
                    <span>Nama Pemilih</span>
                    <strong><?= h($pemilih['nama']) ?></strong>
                </div>
                <dl class="identity-data mb-0">
                    <div>
                        <dt>Nomor Induk</dt>
                        <dd><?= h($pemilih['nomor_induk']) ?></dd>
                    </div>
                    <?php if (trim((string) ($pemilih['kelas'] ?? '')) !== ''): ?>
                        <div>
                            <dt>Kelas</dt>
                            <dd><?= h($pemilih['kelas']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <div>
                        <dt>Jenis</dt>
                        <dd><?= h($pemilih['jenis']) ?></dd>
                    </div>
                </dl>
            </div>

            <div class="identity-question">
                <h2>Apakah data di atas benar?</h2>
                <p>Pastikan identitas sesuai sebelum melanjutkan proses pemilihan.</p>
            </div>

            <div class="identity-actions">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="action" value="reject">
                    <button class="btn btn-outline-secondary w-100" type="submit">
                        <i class="bi bi-arrow-left-circle"></i> Bukan Saya
                    </button>
                </form>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="action" value="confirm">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-check-circle"></i> Ya, Lanjut Memilih
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <p class="auth-footer mb-0 mt-4">&copy; OSIS MPK SMKN 20 Jakarta</p>
    </section>
</main>
<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
