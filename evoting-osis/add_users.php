<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

try {
    $pdo = get_pdo();

    $users = [
        ['Siswa', '14110', 'Ananda Abiyyu Saqib', 'XII Bisnis Digital'],
        ['Guru', '199105112023211030', 'Anggara Elsa Bakhtiar, S.P.d', null],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO pemilih (jenis, nomor_induk, nama, kelas, sudah_memilih, waktu_memilih, created_at)
         VALUES (?, ?, ?, ?, 0, NULL, NOW())
         ON DUPLICATE KEY UPDATE jenis = VALUES(jenis), nama = VALUES(nama), kelas = VALUES(kelas)'
    );

    foreach ($users as $user) {
        $stmt->execute($user);
        echo 'OK Pemilih berhasil disimpan: ' . $user[2] . ' (' . $user[1] . ')' . PHP_EOL;
    }

    echo PHP_EOL . 'OK Data test pemilih selesai diproses.' . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
