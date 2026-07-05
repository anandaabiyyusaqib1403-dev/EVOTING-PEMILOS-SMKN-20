<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'import') {
        try {
            if (empty($_FILES['file']['tmp_name'])) {
                throw new RuntimeException('Pilih file Excel terlebih dahulu.');
            }

            $rows = read_voter_import_rows($_FILES['file']['tmp_name'], $_FILES['file']['name']);
            $stmt = get_pdo()->prepare(
                'INSERT INTO pemilih (jenis, nomor_induk, nama, kelas, created_at)
                 VALUES (?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE jenis = VALUES(jenis), nama = VALUES(nama), kelas = VALUES(kelas)'
            );

            $imported = 0;
            foreach ($rows as $row) {
                [$jenis, $nomorInduk, $nama, $kelas] = $row;
                $jenis = ucfirst(strtolower($jenis));

                if (!in_array($jenis, ['Siswa', 'Guru'], true) || $nomorInduk === '' || $nama === '') {
                    continue;
                }

                $stmt->execute([$jenis, $nomorInduk, $nama, $kelas ?: null]);
                $imported++;
            }

            flash('success', $imported . ' data pemilih berhasil diimpor.');
        } catch (Throwable $e) {
            $message = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Import gagal diproses. Periksa format file pemilih.';
            flash('error', $message);
        }

        redirect('admin/voters.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = get_pdo()->prepare('DELETE FROM pemilih WHERE id = ? AND sudah_memilih = 0');
        $stmt->execute([$id]);
        flash('success', 'Data pemilih dihapus jika belum menggunakan suara.');
        redirect('admin/voters.php');
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? 'all');
$status = in_array($status, ['all', 'voted', 'not_voted'], true) ? $status : 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;
$pdo = get_pdo();

$summary = [
    'siswa' => (int) $pdo->query("SELECT COUNT(*) FROM pemilih WHERE jenis = 'Siswa'")->fetchColumn(),
    'guru' => (int) $pdo->query("SELECT COUNT(*) FROM pemilih WHERE jenis = 'Guru'")->fetchColumn(),
];

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(nomor_induk LIKE ? OR nama LIKE ? OR kelas LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($status === 'voted') {
    $where[] = 'sudah_memilih = 1';
}
if ($status === 'not_voted') {
    $where[] = 'sudah_memilih = 0';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pemilih {$whereSql}");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM pemilih {$whereSql} ORDER BY nama ASC LIMIT ? OFFSET ?");
foreach ($params as $index => $param) {
    $stmt->bindValue($index + 1, $param);
}
$stmt->bindValue(count($params) + 1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$voters = $stmt->fetchAll();

$activeMenu = 'pemilih';
$pageTitle = 'Pemilih';
$pageHeading = 'Pemilih';
$pageSubheading = 'Import dan pantau daftar siswa serta guru yang memiliki hak suara.';
require_once __DIR__ . '/../includes/admin-header.php';
?>
<section class="stat-grid compact-stat-grid mb-4">
    <div class="admin-card stat-card">
        <div class="stat-icon blue"><i class="bi bi-mortarboard"></i></div>
        <span>Total Siswa</span>
        <strong><?= h((string) $summary['siswa']) ?></strong>
    </div>
    <div class="admin-card stat-card">
        <div class="stat-icon gold"><i class="bi bi-person-workspace"></i></div>
        <span>Total Guru</span>
        <strong><?= h((string) $summary['guru']) ?></strong>
    </div>
</section>

<section class="admin-card mb-4">
    <form method="post" enctype="multipart/form-data" class="row g-3 align-items-end">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="import">
        <div class="col-lg-7">
            <label class="form-label fw-bold" for="file">Import Data Pemilih</label>
            <input class="form-control" id="file" name="file" type="file" accept=".xlsx,.xls,.csv" required>
            <div class="form-text">Format: Jenis | NIS/NIP | Nama | Kelas</div>
        </div>
        <div class="col-lg-3">
            <button class="btn btn-primary w-100" type="submit">
                <i class="bi bi-upload"></i> Import
            </button>
        </div>
        <div class="col-lg-2">
            <a class="btn btn-outline-secondary w-100" href="<?= h(app_url('database/template-pemilih.csv')) ?>">
                <i class="bi bi-download"></i> Template
            </a>
        </div>
    </form>
</section>

<section class="admin-card">
    <div class="toolbar">
        <h2 class="h5 section-heading mb-0">Daftar Pemilih</h2>
        <form class="voter-filter-form" method="get">
            <select class="form-select" name="status" aria-label="Filter status pemilih">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua</option>
                <option value="voted" <?= $status === 'voted' ? 'selected' : '' ?>>Sudah Memilih</option>
                <option value="not_voted" <?= $status === 'not_voted' ? 'selected' : '' ?>>Belum Memilih</option>
            </select>
            <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Cari pemilih">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Nomor Induk</th>
                    <th>Jenis</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($voters as $voter): ?>
                    <tr>
                        <td class="table-name"><?= h($voter['nama']) ?></td>
                        <td><?= h($voter['nomor_induk']) ?></td>
                        <td><?= h($voter['jenis']) ?></td>
                        <td><?= h($voter['kelas']) ?></td>
                        <td>
                            <?php if ((int) $voter['sudah_memilih'] === 1): ?>
                                <span class="badge text-bg-success">Sudah Memilih</span>
                            <?php else: ?>
                                <span class="badge text-bg-secondary">Belum Memilih</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form method="post" data-confirm-title="Hapus pemilih?" data-confirm="Tindakan ini tidak dapat dibatalkan. Pemilih yang sudah memilih tidak akan dihapus." class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= h((string) $voter['id']) ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit" <?= (int) $voter['sudah_memilih'] === 1 ? 'disabled' : '' ?>>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$voters): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-4">Data pemilih belum ada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <nav class="pagination-wrap" aria-label="Navigasi halaman pemilih">
            <span class="small text-secondary">Menampilkan <?= h((string) count($voters)) ?> dari <?= h((string) $totalRows) ?> data</span>
            <div class="btn-group">
                <a class="btn btn-outline-primary btn-sm <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= h(app_url('admin/voters.php?status=' . urlencode($status) . '&q=' . urlencode($q) . '&page=' . max(1, $page - 1))) ?>">Sebelumnya</a>
                <a class="btn btn-outline-primary btn-sm <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= h(app_url('admin/voters.php?status=' . urlencode($status) . '&q=' . urlencode($q) . '&page=' . min($totalPages, $page + 1))) ?>">Berikutnya</a>
            </div>
        </nav>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
