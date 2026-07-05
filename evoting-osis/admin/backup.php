<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$pdo = get_pdo();
$tables = ['pemilih', 'kandidat', 'pengaturan'];
$filename = 'backup-evoting-osis-' . date('Ymd-His') . '.sql';

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "-- Backup privacy-safe E-Voting Ketua OSIS\n";
echo "-- Tabel votes dan admin tidak diekspor.\n";
echo "-- Backup ini menjaga kerahasiaan pilihan pemilih.\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $table) {
    $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
    echo "DROP TABLE IF EXISTS `{$table}`;\n";
    echo $create['Create Table'] . ";\n\n";

    $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll();
    foreach ($rows as $row) {
        $columns = array_map(fn ($column) => '`' . str_replace('`', '``', (string) $column) . '`', array_keys($row));
        $values = array_map(
            fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
            array_values($row)
        );
        echo 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
    }

    echo "\n";
}
