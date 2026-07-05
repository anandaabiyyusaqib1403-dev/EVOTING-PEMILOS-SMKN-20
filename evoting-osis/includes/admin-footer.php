        <footer class="admin-footer">
            <span><?= h(APP_SHORT_NAME) ?></span>
            <span><?= h(SCHOOL_NAME) ?> &middot; <?= h(ACADEMIC_YEAR) ?></span>
        </footer>
    </main>
</div>
<div class="modal fade" id="actionConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="actionConfirmTitle">Apakah Anda yakin?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="modal-question-icon danger mx-auto mb-3"><i class="bi bi-exclamation-triangle"></i></div>
                <p class="text-center mb-0" id="actionConfirmText">Tindakan ini tidak dapat dibatalkan.</p>
                <div class="confirm-phrase-wrap mt-3 d-none" id="confirmPhraseWrap">
                    <label class="form-label" for="confirmPhraseInput">Ketik <strong id="confirmPhraseLabel">RESET</strong> untuk melanjutkan</label>
                    <input class="form-control" id="confirmPhraseInput" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="actionConfirmContinue">Lanjutkan</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= h(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
