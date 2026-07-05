<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', '0');

define('APP_NAME', 'E-Voting Ketua OSIS SMKN 20 Jakarta');
define('APP_SHORT_NAME', 'E-Voting Ketua OSIS');
define('SCHOOL_NAME', 'SMKN 20 Jakarta');
define('ACADEMIC_YEAR', 'Tahun Ajaran 2026/2027');
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'evoting_osis');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = preg_replace('#/admin(?:/.*)?$#', '', $scriptDir);
$baseDir = $baseDir === '/' ? '' : rtrim($baseDir, '/');
define('BASE_URL', $baseDir);

define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/candidates');
define('UPLOAD_URL', BASE_URL . '/uploads/candidates');
