<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $nomorUrut = (int) ($_POST['nomor_urut'] ?? 0);
            $namaKetua = trim((string) ($_POST['nama_ketua'] ?? ''));
            $namaWakil = trim((string) ($_POST['nama_wakil'] ?? ''));
            $visi = trim((string) ($_POST['visi'] ?? ''));
            $misi = trim((string) ($_POST['misi'] ?? ''));
            $status = isset($_POST['status']) && $_POST['status'] === 'aktif' ? 'aktif' : 'nonaktif';

            if ($nomorUrut <= 0 || $namaKetua === '' || $namaWakil === '' || $visi === '') {
                throw new RuntimeException('Nomor urut, nama ketua, nama wakil, dan visi wajib diisi.');
            }

            $currentPhoto = null;
            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT foto FROM kandidat WHERE id = ?');
                $stmt->execute([$id]);
                $currentPhoto = $stmt->fetchColumn() ?: null;
            }

            $photo = save_candidate_photo('foto', $currentPhoto);

            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE kandidat
                     SET nomor_urut = ?, foto = ?, nama_ketua = ?, nama_wakil = ?, visi = ?, misi = ?, status = ?, updated_at = NOW()
                     WHERE id = ?'
                );
                $stmt->execute([$nomorUrut, $photo, $namaKetua, $namaWakil, $visi, $misi, $status, $id]);
                flash('success', 'Kandidat berhasil diperbarui.');
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO kandidat (nomor_urut, foto, nama_ketua, nama_wakil, visi, misi, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
                );
                $stmt->execute([$nomorUrut, $photo, $namaKetua, $namaWakil, $visi, $misi, $status]);
                flash('success', 'Kandidat berhasil ditambahkan.');
            }
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT foto FROM kandidat WHERE id = ?');
            $stmt->execute([$id]);
            $photo = $stmt->fetchColumn();

            $stmt = $pdo->prepare('DELETE FROM kandidat WHERE id = ?');
            $stmt->execute([$id]);

            if ($photo && is_file(UPLOAD_DIR . '/' . $photo)) {
                @unlink(UPLOAD_DIR . '/' . $photo);
            }

            flash('success', 'Kandidat berhasil dihapus.');
        }
    } catch (Throwable $e) {
        $message = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Aksi kandidat gagal diproses. Pastikan nomor urut tidak duplikat dan kandidat belum memiliki suara.';
        flash('error', $message);
    }

    redirect('admin/candidates.php');
}

$editId = (int) ($_GET['edit'] ?? 0);
$editCandidate = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM kandidat WHERE id = ?');
    $stmt->execute([$editId]);
    $editCandidate = $stmt->fetch() ?: null;
}

$candidates = $pdo->query('SELECT * FROM kandidat ORDER BY nomor_urut ASC')->fetchAll();

$activeMenu = 'kandidat';
$pageTitle = 'Kandidat';
$pageHeading = 'Kandidat';
$pageSubheading = 'Tambah, edit, dan aktifkan pasangan calon Ketua OSIS.';
require_once __DIR__ . '/../includes/admin-header.php';
?>
<section class="admin-card mb-4">
    <div class="toolbar">
        <div>
            <h2 class="h5 section-heading mb-1">Daftar Kandidat</h2>
            <p class="muted-text mb-0">Kelola pasangan calon tanpa menampilkan form panjang di halaman utama.</p>
        </div>
        <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#candidateFormModal">
            <i class="bi bi-plus-circle"></i> Tambah Kandidat
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nomor</th>
                    <th>Pasangan Calon</th>
                    <th>Visi Singkat</th>
                    <th>Jumlah Misi</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidates as $candidate): ?>
                    <tr>
                        <td><img class="photo-thumb" src="<?= h(candidate_photo_url($candidate['foto'])) ?>" alt=""></td>
                        <td><span class="number-badge"><?= h((string) $candidate['nomor_urut']) ?></span></td>
                        <td>
                            <div class="table-name"><?= h($candidate['nama_ketua']) ?></div>
                            <div class="small text-secondary">Wakil: <?= h($candidate['nama_wakil']) ?></div>
                        </td>
                        <td class="table-preview"><?= h(text_preview($candidate['visi'], 110)) ?></td>
                        <td><?= h((string) mission_count($candidate['misi'])) ?> misi</td>
                        <td><?= $candidate['status'] === 'aktif' ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= h(app_url('admin/candidates.php?edit=' . $candidate['id'])) ?>">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" data-confirm-title="Hapus kandidat?" data-confirm="Tindakan ini tidak dapat dibatalkan. Kandidat yang sudah memiliki suara mungkin tidak dapat dihapus." class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= h((string) $candidate['id']) ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$candidates): ?>
                    <tr><td colspan="7" class="text-center text-secondary py-4">Belum ada kandidat.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="modal fade" id="candidateFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= h((string) ($editCandidate['id'] ?? 0)) ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5"><?= $editCandidate ? 'Edit Kandidat' : 'Tambah Kandidat' ?></h2>
                    <a class="btn-close" href="<?= h(app_url('admin/candidates.php')) ?>" aria-label="Tutup"></a>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="nomor_urut">Nomor Urut</label>
                            <input class="form-control" id="nomor_urut" name="nomor_urut" type="number" min="1" value="<?= h((string) ($editCandidate['nomor_urut'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label" for="foto">Foto</label>
                            <input class="form-control" id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="nama_ketua">Nama Ketua</label>
                            <input class="form-control" id="nama_ketua" name="nama_ketua" value="<?= h($editCandidate['nama_ketua'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="nama_wakil">Nama Wakil</label>
                            <input class="form-control" id="nama_wakil" name="nama_wakil" value="<?= h($editCandidate['nama_wakil'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="visi">Visi</label>
                            <textarea class="form-control" id="visi" name="visi" rows="4" required><?= h($editCandidate['visi'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="misi">Misi</label>
                            <textarea class="form-control" id="misi" name="misi" rows="4"><?= h($editCandidate['misi'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" id="status" name="status" value="aktif" type="checkbox" <?= ($editCandidate['status'] ?? 'aktif') === 'aktif' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="status">Status Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-secondary" href="<?= h(app_url('admin/candidates.php')) ?>">Batal</a>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editCandidate): ?>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('candidateFormModal')).show();
        });
    </script>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
