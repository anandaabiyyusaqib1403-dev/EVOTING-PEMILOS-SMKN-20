<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

$success = false;
$error = null;
$adminCreated = false;
$adminUser = app_env('EVOTING_ADMIN_USERNAME', 'admin');
$adminPass = app_env('EVOTING_ADMIN_PASSWORD', 'admin123');
$adminName = app_env('EVOTING_ADMIN_NAME', 'Administrator');

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function run_privacy_migrations(PDO $pdo): void
{
    if (!column_exists($pdo, 'pemilih', 'sudah_memilih')) {
        $pdo->exec('ALTER TABLE pemilih ADD COLUMN sudah_memilih TINYINT(1) NOT NULL DEFAULT 0 AFTER kelas');
    }
    if (!column_exists($pdo, 'pemilih', 'waktu_memilih')) {
        $pdo->exec('ALTER TABLE pemilih ADD COLUMN waktu_memilih DATETIME NULL AFTER sudah_memilih');
    }
    if (!column_exists($pdo, 'pemilih', 'updated_at')) {
        $pdo->exec('ALTER TABLE pemilih ADD COLUMN updated_at DATETIME NULL AFTER created_at');
    }
    if (column_exists($pdo, 'pemilih', 'has_voted')) {
        $pdo->exec('UPDATE pemilih SET sudah_memilih = has_voted WHERE sudah_memilih = 0');
    }
    if (column_exists($pdo, 'pemilih', 'voted_at')) {
        $pdo->exec('UPDATE pemilih SET waktu_memilih = voted_at WHERE waktu_memilih IS NULL');
    }
    if (column_exists($pdo, 'pemilih', 'has_voted')) {
        $pdo->exec('ALTER TABLE pemilih DROP COLUMN has_voted');
    }
    if (column_exists($pdo, 'pemilih', 'voted_at')) {
        $pdo->exec('ALTER TABLE pemilih DROP COLUMN voted_at');
    }

    if (!column_exists($pdo, 'kandidat', 'status')) {
        $pdo->exec("ALTER TABLE kandidat ADD COLUMN status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif' AFTER misi");
    }
    if (column_exists($pdo, 'kandidat', 'is_active')) {
        $pdo->exec("UPDATE kandidat SET status = IF(is_active = 1, 'aktif', 'nonaktif')");
        $pdo->exec('ALTER TABLE kandidat DROP COLUMN is_active');
    }

    if (!table_exists($pdo, 'votes')) {
        $pdo->exec(
            "CREATE TABLE votes (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                kandidat_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_votes_kandidat FOREIGN KEY (kandidat_id) REFERENCES kandidat(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                INDEX idx_votes_kandidat (kandidat_id),
                INDEX idx_votes_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    if (table_exists($pdo, 'voting')) {
        $copied = (int) $pdo->query('SELECT COUNT(*) FROM votes')->fetchColumn();
        if ($copied === 0) {
            $pdo->exec('INSERT INTO votes (kandidat_id, created_at) SELECT kandidat_id, created_at FROM voting');
        }
        $pdo->exec('DROP TABLE voting');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $serverDsn = sprintf('mysql:host=%s;charset=%s', DB_HOST, DB_CHARSET);
        $pdo = new PDO($serverDsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '``', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . str_replace('`', '``', DB_NAME) . '`');

        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('File schema.sql tidak ditemukan.');
        }
        foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
            $pdo->exec($statement);
        }
        run_privacy_migrations($pdo);

        $count = (int) $pdo->query('SELECT COUNT(*) FROM admin')->fetchColumn();
        if ($count === 0) {
            $stmt = $pdo->prepare('INSERT INTO admin (username, password, nama, created_at) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$adminUser, password_hash($adminPass, PASSWORD_DEFAULT), $adminName]);
            $adminCreated = true;
        }

        $success = true;
    } catch (Throwable $e) {
        $error = 'Setup gagal. Periksa konfigurasi MySQL di config/config.php.';
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="public-body">
<main class="public-wrap">
    <section class="auth-panel">
        <h1 class="auth-title mb-3">Setup E-Voting</h1>
        <p class="muted-text">Klik tombol di bawah untuk membuat database, tabel, pengaturan awal, dan akun admin default.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                Setup berhasil.
                <?php if ($adminCreated): ?>
                    Login admin pertama: <strong><?= htmlspecialchars($adminUser, ENT_QUOTES, 'UTF-8') ?></strong> /
                    <strong><?= htmlspecialchars($adminPass, ENT_QUOTES, 'UTF-8') ?></strong>.
                <?php else: ?>
                    Akun admin lama tetap digunakan.
                <?php endif; ?>
            </div>
            <?php if ($adminCreated && $adminPass === 'admin123'): ?>
                <div class="alert alert-warning">
                    Password admin masih default. Ganti sebelum digunakan untuk pemilihan sungguhan.
                </div>
            <?php endif; ?>
            <a class="btn btn-primary w-100" href="admin/login.php">Login Admin</a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post">
                <button class="btn btn-primary w-100" type="submit">Jalankan Setup</button>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
