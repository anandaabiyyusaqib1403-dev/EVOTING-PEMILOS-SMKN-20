<?php
require_once __DIR__ . '/includes/functions.php';
require_voter();

$pemilih = current_voter();
if (!$pemilih || (int) $pemilih['sudah_memilih'] === 1) {
    unset($_SESSION['verified_voter_id'], $_SESSION['pending_voter_id'], $_SESSION['pemilih_id']);
    flash('error', 'Anda sudah menggunakan hak suara');
    redirect('login.php');
}

if (!voting_is_open()) {
    unset($_SESSION['verified_voter_id'], $_SESSION['pending_voter_id'], $_SESSION['pemilih_id']);
    flash('error', 'Pemungutan suara sedang ditutup');
    redirect('login.php');
}

$stmt = get_pdo()->query("SELECT * FROM kandidat WHERE status = 'aktif' ORDER BY nomor_urut ASC");
$candidates = $stmt->fetchAll();

$pageTitle = 'Pilih Kandidat - ' . APP_NAME;
require_once __DIR__ . '/includes/public-header.php';
?>
<nav class="voting-navbar">
    <div class="container d-flex align-items-center justify-content-between gap-3">
        <a class="voting-brand" href="<?= h(app_url('login.php')) ?>">
            <img src="<?= h(app_url('assets/img/logo-smkn20.png')) ?>" alt="Logo SMKN 20 Jakarta">
            <span><?= h(APP_SHORT_NAME) ?></span>
        </a>
        <span class="status-pill open">Voting Dibuka</span>
    </div>
</nav>
<main class="container py-4 py-lg-5">
    <div class="content-panel p-4 p-lg-5 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <h1 class="page-title mb-2">Pilih Kandidat Anda</h1>
                <p class="muted-text mb-0">
                    Selamat datang, <?= h($pemilih['nama']) ?>. Pilih satu pasangan calon dengan yakin.
                </p>
            </div>
            <div class="text-lg-end">
                <div class="small text-secondary mt-2"><?= h($pemilih['jenis']) ?><?= $pemilih['kelas'] ? ' - ' . h($pemilih['kelas']) : '' ?></div>
            </div>
        </div>
    </div>

    <?php if ($message = flash('error')): ?>
        <div class="alert alert-danger"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if (!$candidates): ?>
        <div class="alert alert-warning">Belum ada kandidat aktif.</div>
    <?php endif; ?>

    <div class="candidate-grid">
        <?php foreach ($candidates as $candidate): ?>
            <article class="content-panel candidate-card">
                <div class="candidate-media">
                    <img class="candidate-photo" src="<?= h(candidate_photo_url($candidate['foto'])) ?>" alt="Foto pasangan calon nomor <?= h((string) $candidate['nomor_urut']) ?>">
                    <span class="candidate-number-overlay"><?= h((string) $candidate['nomor_urut']) ?></span>
                </div>
                <div class="candidate-body p-4">
                    <div class="candidate-main">
                        <p class="candidate-role mb-1">Calon Ketua</p>
                        <h2 class="candidate-name mb-2"><?= h($candidate['nama_ketua']) ?></h2>
                        <p class="mb-3 muted-text">Wakil Ketua: <?= h($candidate['nama_wakil']) ?></p>
                        <div class="vision-preview">
                            <span>Visi Singkat</span>
                            <p><?= h(text_preview($candidate['visi'], 155)) ?></p>
                        </div>
                    </div>
                    <div class="candidate-actions">
                        <button
                            type="button"
                            class="btn btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#candidateDetailModal"
                            data-candidate-id="<?= h((string) $candidate['id']) ?>"
                            data-candidate-photo="<?= h(candidate_photo_url($candidate['foto'])) ?>"
                            data-candidate-number="<?= h((string) $candidate['nomor_urut']) ?>"
                            data-candidate-chairman="<?= h($candidate['nama_ketua']) ?>"
                            data-candidate-vice="<?= h($candidate['nama_wakil']) ?>"
                            data-candidate-vision="<?= h($candidate['visi']) ?>"
                            data-candidate-mission="<?= h($candidate['misi']) ?>">
                            <i class="bi bi-eye"></i> Lihat Visi & Misi
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#confirmVoteModal"
                            data-candidate-id="<?= h((string) $candidate['id']) ?>"
                            data-candidate-name="Nomor <?= h((string) $candidate['nomor_urut']) ?> - <?= h($candidate['nama_ketua']) ?> & <?= h($candidate['nama_wakil']) ?>">
                            <i class="bi bi-check-circle"></i> Pilih Kandidat
                        </button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</main>

<div class="modal fade" id="candidateDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5">Visi & Misi Kandidat</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="detail-candidate-header">
                    <img id="detailCandidatePhoto" class="detail-candidate-photo" src="<?= h(app_url('assets/img/candidate-placeholder.svg')) ?>" alt="Foto pasangan calon">
                    <div>
                        <span id="detailCandidateNumber" class="number-badge mb-3">1</span>
                        <p class="candidate-role mb-1">Calon Ketua</p>
                        <h3 id="detailCandidateChairman" class="h4 section-heading mb-2"></h3>
                        <p id="detailCandidateVice" class="muted-text mb-0"></p>
                    </div>
                </div>
                <ul class="nav nav-pills detail-tabs" id="candidateDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="vision-tab" data-bs-toggle="pill" data-bs-target="#vision-pane" type="button" role="tab" aria-controls="vision-pane" aria-selected="true">VISI</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mission-tab" data-bs-toggle="pill" data-bs-target="#mission-pane" type="button" role="tab" aria-controls="mission-pane" aria-selected="false">MISI</button>
                    </li>
                </ul>
                <div class="tab-content detail-tab-content">
                    <section class="tab-pane fade show active detail-section" id="vision-pane" role="tabpanel" aria-labelledby="vision-tab" tabindex="0">
                        <p id="detailCandidateVision" class="mb-0"></p>
                    </section>
                    <section class="tab-pane fade detail-section" id="mission-pane" role="tabpanel" aria-labelledby="mission-tab" tabindex="0">
                        <ol id="detailCandidateMission" class="detail-mission-list mb-0"></ol>
                    </section>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="detailVoteButton" data-bs-toggle="modal" data-bs-target="#confirmVoteModal">
                    <i class="bi bi-check-circle"></i> Pilih Kandidat
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmVoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5 fw-bold">Konfirmasi Pilihan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center">
                <div class="modal-question-icon mx-auto mb-3"><i class="bi bi-question-circle"></i></div>
                <p class="mb-1">Apakah Anda yakin memilih pasangan calon ini?</p>
                <strong id="confirmCandidateName"></strong>
                <p class="warning-copy mt-3 mb-0">Pilihan tidak dapat diubah setelah dikirim.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="voteForm" method="post" action="<?= h(app_url('vote.php')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="candidate_id" value="">
                    <button class="btn btn-primary" type="submit">Ya, Pilih</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/public-footer.php'; ?>
