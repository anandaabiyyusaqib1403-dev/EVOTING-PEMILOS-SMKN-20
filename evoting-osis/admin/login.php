<?php
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('admin/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = get_pdo()->prepare('SELECT * FROM admin WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_name'] = $admin['nama'];
        redirect('admin/index.php');
    }

    flash('error', 'Username atau password salah.');
    redirect('admin/login.php');
}

$pageTitle = 'Login Admin - ' . APP_NAME;
require_once __DIR__ . '/../includes/public-header.php';
?>
<main class="public-wrap">
    <section class="auth-panel">
        <div class="text-center mb-4">
            <img src="<?= h(app_url('assets/img/logo-smkn20.png')) ?>" alt="Logo SMKN 20 Jakarta" class="auth-logo mb-3">
            <span class="auth-badge">Area Panitia Terverifikasi</span>
            <h1 class="auth-title mb-2">Login Admin</h1>
            <p class="muted-text mb-0">Masuk sebagai panitia pemilihan. Aktivitas admin dilindungi session.</p>
        </div>

        <?php if ($message = flash('error')): ?>
            <div class="alert alert-danger"><?= h($message) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input class="form-control" id="username" name="username" type="text" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" id="password" name="password" type="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">
                <i class="bi bi-box-arrow-in-right"></i> Masuk
            </button>
        </form>

        <div class="text-center mt-4">
            <a href="<?= h(app_url('login.php')) ?>" class="small text-secondary">Kembali ke Login Pemilih</a>
        </div>
        <p class="auth-footer mb-0 mt-4">&copy; OSIS MPK SMKN 20 Jakarta</p>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/public-footer.php'; ?>
