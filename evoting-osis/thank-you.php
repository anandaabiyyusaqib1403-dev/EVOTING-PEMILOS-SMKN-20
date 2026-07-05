<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Terima Kasih - ' . APP_NAME;
require_once __DIR__ . '/includes/public-header.php';
?>
<main class="public-wrap">
    <section class="auth-panel text-center">
        <div class="thank-icon mx-auto mb-3">
            <i class="bi bi-check2"></i>
        </div>
        <h1 class="auth-title mb-2">Terima Kasih</h1>
        <p class="muted-text mb-4">Suara Anda berhasil direkam.</p>
        <p class="small text-secondary mb-0">
            Kembali ke login dalam <strong data-countdown="5" data-redirect="<?= h(app_url('login.php')) ?>">5</strong> detik.
        </p>
    </section>
</main>
<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
