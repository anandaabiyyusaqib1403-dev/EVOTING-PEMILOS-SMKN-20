<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

$results = candidate_results();
$totalVotes = array_sum(array_map(fn ($row) => (int) $row['total_suara'], $results));
$stats = dashboard_stats();
$filename = 'hasil-voting-osis-' . date('Ymd-His');

if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Rekap Hasil');
    $sheet->fromArray(['Nama Pemilihan', APP_NAME], null, 'A1');
    $sheet->fromArray(['Tanggal Export', date('d/m/Y H:i')], null, 'A2');
    $sheet->fromArray(['Total Pemilih', $stats['total']], null, 'A3');
    $sheet->fromArray(['Total Suara Masuk', $totalVotes], null, 'A4');
    $sheet->fromArray(['Persentase Partisipasi', $stats['participation'] . '%'], null, 'A5');
    $sheet->fromArray(['Nomor', 'Ketua', 'Wakil', 'Jumlah Suara', 'Persentase'], null, 'A7');

    $rowIndex = 8;
    foreach ($results as $row) {
        $sheet->fromArray([
            $row['nomor_urut'],
            $row['nama_ketua'],
            $row['nama_wakil'],
            (int) $row['total_suara'],
            percentage((int) $row['total_suara'], $totalVotes),
        ], null, 'A' . $rowIndex);
        $rowIndex++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
?>
<table border="1">
    <tbody>
        <tr><th>Nama Pemilihan</th><td><?= h(APP_NAME) ?></td></tr>
        <tr><th>Tanggal Export</th><td><?= h(date('d/m/Y H:i')) ?></td></tr>
        <tr><th>Total Pemilih</th><td><?= h((string) $stats['total']) ?></td></tr>
        <tr><th>Total Suara Masuk</th><td><?= h((string) $totalVotes) ?></td></tr>
        <tr><th>Persentase Partisipasi</th><td><?= h((string) $stats['participation']) ?>%</td></tr>
    </tbody>
</table>
<br>
<table border="1">
    <thead>
        <tr>
            <th>Nomor</th>
            <th>Ketua</th>
            <th>Wakil</th>
            <th>Jumlah Suara</th>
            <th>Persentase</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?= h((string) $row['nomor_urut']) ?></td>
                <td><?= h($row['nama_ketua']) ?></td>
                <td><?= h($row['nama_wakil']) ?></td>
                <td><?= h((string) $row['total_suara']) ?></td>
                <td><?= h(percentage((int) $row['total_suara'], $totalVotes)) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
