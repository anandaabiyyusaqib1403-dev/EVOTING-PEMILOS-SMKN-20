<?php
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['verified_voter_id'])) {
    redirect('candidates.php');
}

if (!empty($_SESSION['pending_voter_id'])) {
    redirect('identity-confirmation.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nomorInduk = trim((string) ($_POST['nomor_induk'] ?? ''));

    if ($nomorInduk === '') {
        flash('error', 'Nomor Induk wajib diisi.');
        redirect('login.php');
    }

    if (!voting_is_open()) {
        flash('error', 'Pemungutan suara sedang ditutup');
        redirect('login.php');
    }

    $stmt = get_pdo()->prepare('SELECT * FROM pemilih WHERE nomor_induk = ? LIMIT 1');
    $stmt->execute([$nomorInduk]);
    $pemilih = $stmt->fetch();

    if (!$pemilih) {
        flash('error', 'Nomor induk tidak ditemukan');
        redirect('login.php');
    }

    if ((int) $pemilih['sudah_memilih'] === 1) {
        flash('error', 'Anda sudah menggunakan hak suara');
        redirect('login.php');
    }

    session_regenerate_id(true);
    unset($_SESSION['verified_voter_id'], $_SESSION['pemilih_id']);
    $_SESSION['pending_voter_id'] = (int) $pemilih['id'];
    redirect('identity-confirmation.php');
}

$pageTitle = 'Login Pemilih - ' . APP_NAME;
require_once __DIR__ . '/includes/public-header.php';
?>
<main class="public-wrap">
    <section class="auth-panel">
        <div class="text-center mb-4">
            <img src="<?= h(app_url('assets/img/logo-smkn20.png')) ?>" alt="Logo SMKN 20 Jakarta" class="auth-logo mb-3">
            <span class="auth-badge">Sistem Pemilihan Digital</span>
            <h1 class="auth-title mb-2">Masuk Sebagai Pemilih</h1>
            <p class="muted-text mb-0">Pemilihan Ketua dan Wakil Ketua OSIS SMKN 20 Jakarta</p>
        </div>

        <?php if ($message = flash('error')): ?>
            <div class="alert alert-danger"><?= h($message) ?></div>
        <?php endif; ?>
        <?php if ($message = flash('info')): ?>
            <div class="alert alert-info"><?= h($message) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="mb-3">
                <label for="nomor_induk" class="form-label">Nomor Induk</label>
                <input type="text" class="form-control form-control-lg" id="nomor_induk" name="nomor_induk" placeholder="Masukkan NIS atau NIP" required autofocus>
            </div>
            <button class="btn btn-primary btn-lg w-100" type="submit">
                <i class="bi bi-box-arrow-in-right"></i> Masuk
            </button>
        </form>

        <div class="login-info mt-3">
            <div>NIS digunakan siswa</div>
            <div>NIP digunakan guru</div>
        </div>

        <div class="text-center mt-4">
            <a href="<?= h(app_url('admin/login.php')) ?>" class="small text-secondary">Login Admin</a>
        </div>
        <p class="auth-footer mb-0 mt-4">&copy; OSIS MPK SMKN 20 Jakarta</p>
    </section>
</main>
<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
