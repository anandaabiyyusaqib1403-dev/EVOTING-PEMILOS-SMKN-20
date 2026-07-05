<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $posted = $_POST['csrf_token'] ?? '';
    if (!is_string($posted) || !hash_equals($_SESSION['csrf_token'] ?? '', $posted)) {
        http_response_code(419);
        exit('Sesi tidak valid. Silakan muat ulang halaman.');
    }
}

function get_setting(string $key, string $default = ''): string
{
    $stmt = get_pdo()->prepare('SELECT nilai FROM pengaturan WHERE kunci = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();

    return $row['nilai'] ?? $default;
}

function set_setting(string $key, string $value): void
{
    $stmt = get_pdo()->prepare(
        'INSERT INTO pengaturan (kunci, nilai, updated_at) VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), updated_at = NOW()'
    );
    $stmt->execute([$key, $value]);
}

function voting_is_open(): bool
{
    return get_setting('voting_status', 'closed') === 'open';
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect('admin/login.php');
    }
}

function require_voter(): void
{
    if (empty($_SESSION['verified_voter_id'])) {
        redirect('login.php');
    }
}

function current_voter(): ?array
{
    if (empty($_SESSION['verified_voter_id'])) {
        return null;
    }

    $stmt = get_pdo()->prepare('SELECT * FROM pemilih WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['verified_voter_id']]);

    return $stmt->fetch() ?: null;
}

function pending_voter(): ?array
{
    if (empty($_SESSION['pending_voter_id'])) {
        return null;
    }

    $stmt = get_pdo()->prepare('SELECT id, nomor_induk, nama, kelas, jenis, sudah_memilih FROM pemilih WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['pending_voter_id']]);

    return $stmt->fetch() ?: null;
}

function candidate_photo_url(?string $filename): string
{
    if ($filename) {
        return UPLOAD_URL . '/' . rawurlencode($filename);
    }

    return app_url('assets/img/candidate-placeholder.svg');
}

function text_preview(?string $value, int $limit = 150): string
{
    $text = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $value)));
    if ($text === '') {
        return '-';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text, 'UTF-8') > $limit
            ? rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . '...'
            : $text;
    }

    return strlen($text) > $limit ? rtrim(substr($text, 0, $limit)) . '...' : $text;
}

function mission_items(?string $value): array
{
    $text = trim((string) $value);
    if ($text === '') {
        return [];
    }

    $items = preg_split('/\r?\n|;|(?=\s*\d+[.)]\s+)/', $text) ?: [];
    return array_values(array_filter(array_map(
        fn ($item) => trim((string) preg_replace('/^\s*(?:[-*]|\d+[.)])\s*/', '', (string) $item)),
        $items
    )));
}

function mission_count(?string $value): int
{
    $items = mission_items($value);
    return count($items);
}

function display_time(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }

    $timestamp = strtotime($datetime);
    return $timestamp ? date('H:i', $timestamp) : $datetime;
}

function save_candidate_photo(string $fieldName, ?string $current = null): ?string
{
    if (empty($_FILES[$fieldName]['name'])) {
        return $current;
    }

    $file = $_FILES[$fieldName];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Foto gagal diunggah.');
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    }

    $mime = mime_content_type($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Format foto harus JPG, PNG, atau WEBP.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = 'candidate-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extensions[$mime];
    $destination = UPLOAD_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Foto tidak dapat disimpan.');
    }

    if ($current && is_file(UPLOAD_DIR . '/' . $current)) {
        @unlink(UPLOAD_DIR . '/' . $current);
    }

    return $filename;
}

function dashboard_stats(): array
{
    $pdo = get_pdo();

    $total = (int) $pdo->query('SELECT COUNT(*) FROM pemilih')->fetchColumn();
    $voted = (int) $pdo->query('SELECT COUNT(*) FROM pemilih WHERE sudah_memilih = 1')->fetchColumn();
    $notVoted = max(0, $total - $voted);
    $participation = $total > 0 ? round(($voted / $total) * 100, 1) : 0;

    return [
        'status' => voting_is_open() ? 'Dibuka' : 'Ditutup',
        'total' => $total,
        'voted' => $voted,
        'not_voted' => $notVoted,
        'participation' => $participation,
    ];
}

function candidate_results(): array
{
    $sql = 'SELECT k.id, k.nomor_urut, k.foto, k.nama_ketua, k.nama_wakil, k.visi, k.misi, k.status, k.created_at, k.updated_at,
                   COUNT(v.id) AS total_suara
            FROM kandidat k
            LEFT JOIN votes v ON v.kandidat_id = k.id
            GROUP BY k.id, k.nomor_urut, k.foto, k.nama_ketua, k.nama_wakil, k.visi, k.misi, k.status, k.created_at, k.updated_at
            ORDER BY k.nomor_urut ASC';

    return get_pdo()->query($sql)->fetchAll();
}

function recent_votes(int $limit = 8): array
{
    $stmt = get_pdo()->prepare(
        'SELECT nama, jenis, kelas, waktu_memilih AS created_at
         FROM pemilih
         WHERE sudah_memilih = 1 AND waktu_memilih IS NOT NULL
         ORDER BY waktu_memilih DESC
         LIMIT ?'
    );
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function percentage(int $part, int $total): string
{
    if ($total <= 0) {
        return '0%';
    }

    return number_format(($part / $total) * 100, 1, ',', '.') . '%';
}

function read_voter_import_rows(string $path, string $originalName): array
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }

    if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->toArray(null, true, true, true) as $index => $row) {
            if ($index === 1) {
                continue;
            }

            $rows[] = [
                trim((string) ($row['A'] ?? '')),
                trim((string) ($row['B'] ?? '')),
                trim((string) ($row['C'] ?? '')),
                trim((string) ($row['D'] ?? '')),
            ];
        }

        return $rows;
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === 'csv') {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            throw new RuntimeException('File tidak dapat dibaca.');
        }

        $rows = [];
        $line = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $line++;
            if ($line === 1) {
                continue;
            }

            $rows[] = [
                trim((string) ($data[0] ?? '')),
                trim((string) ($data[1] ?? '')),
                trim((string) ($data[2] ?? '')),
                trim((string) ($data[3] ?? '')),
            ];
        }
        fclose($handle);

        return $rows;
    }

    if ($extension !== 'xlsx' || !class_exists(ZipArchive::class)) {
        throw new RuntimeException('Install PhpSpreadsheet atau unggah file CSV/XLSX sederhana.');
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('File Excel tidak valid.');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $shared = simplexml_load_string($sharedXml);
        foreach ($shared->si ?? [] as $si) {
            $sharedStrings[] = (string) ($si->t ?? '');
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($sheetXml === false) {
        throw new RuntimeException('Sheet pertama tidak ditemukan.');
    }

    $sheet = simplexml_load_string($sheetXml);
    $rows = [];
    foreach ($sheet->sheetData->row ?? [] as $rowIndex => $row) {
        if ((int) $row['r'] === 1 || $rowIndex === 0) {
            continue;
        }

        $cells = ['A' => '', 'B' => '', 'C' => '', 'D' => ''];
        foreach ($row->c as $cell) {
            $ref = preg_replace('/\d+/', '', (string) $cell['r']);
            if (!array_key_exists($ref, $cells)) {
                continue;
            }

            $value = (string) ($cell->v ?? '');
            if ((string) $cell['t'] === 's') {
                $value = $sharedStrings[(int) $value] ?? '';
            }
            $cells[$ref] = trim($value);
        }

        $rows[] = [$cells['A'], $cells['B'], $cells['C'], $cells['D']];
    }

    return $rows;
}
