<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', '0');

function app_env(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

define('APP_NAME', app_env('EVOTING_APP_NAME', 'E-Voting Ketua OSIS SMKN 20 Jakarta'));
define('APP_SHORT_NAME', app_env('EVOTING_APP_SHORT_NAME', 'E-Voting Ketua OSIS'));
define('SCHOOL_NAME', app_env('EVOTING_SCHOOL_NAME', 'SMKN 20 Jakarta'));
define('ACADEMIC_YEAR', app_env('EVOTING_ACADEMIC_YEAR', 'Tahun Ajaran 2026/2027'));
define('DB_HOST', app_env('EVOTING_DB_HOST', '127.0.0.1'));
define('DB_NAME', app_env('EVOTING_DB_NAME', 'evoting_osis'));
define('DB_USER', app_env('EVOTING_DB_USER', 'root'));
define('DB_PASS', app_env('EVOTING_DB_PASS', ''));
define('DB_CHARSET', app_env('EVOTING_DB_CHARSET', 'utf8mb4'));

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = preg_replace('#/admin(?:/.*)?$#', '', $scriptDir);
$baseDir = $baseDir === '/' ? '' : rtrim($baseDir, '/');
define('BASE_URL', $baseDir);

define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/candidates');
define('UPLOAD_URL', BASE_URL . '/uploads/candidates');
