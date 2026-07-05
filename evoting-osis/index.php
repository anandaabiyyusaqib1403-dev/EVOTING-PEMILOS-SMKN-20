<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = APP_NAME;
require_once __DIR__ . '/includes/public-header.php';
?>
<main class="public-wrap">
    <section class="landing-hero">
        <div class="landing-card text-center">
            <div class="landing-logo-panel">
                <div class="landing-logos" aria-label="Logo SMKN 20 Jakarta, OSIS, dan MPK">
                    <span><img src="<?= h(app_url('assets/img/logo-smkn20.png')) ?>" alt="Logo SMKN 20 Jakarta"></span>
                    <span><img src="<?= h(app_url('assets/img/logo-osis-smkn20.png')) ?>" alt="Logo OSIS SMKN 20 Jakarta"></span>
                    <span><img src="<?= h(app_url('assets/img/logo-mpk-smkn20.png')) ?>" alt="Logo MPK SMKN 20 Jakarta"></span>
                </div>
                <p class="landing-school mb-0">SMK Negeri 20 Jakarta</p>
            </div>
            <h1 class="landing-title mb-3">E-Voting Ketua OSIS</h1>
            <p class="landing-subtitle mb-4">
                Pemilihan Ketua dan Wakil Ketua OSIS<br>SMK Negeri 20 Jakarta
            </p>
            <a href="<?= h(app_url('login.php')) ?>" class="btn btn-primary btn-lg landing-button">
                <i class="bi bi-box-arrow-in-right"></i> Masuk
            </a>
            <p class="academic-year mb-0"><?= h(ACADEMIC_YEAR) ?></p>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
