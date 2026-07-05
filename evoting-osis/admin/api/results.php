<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

header('Content-Type: application/json');

$results = candidate_results();
$totalVotes = array_sum(array_map(fn ($row) => (int) $row['total_suara'], $results));

echo json_encode([
    'labels' => array_map(fn ($row) => [
        str_pad((string) $row['nomor_urut'], 2, '0', STR_PAD_LEFT),
        $row['nama_ketua'] . ' & ' . $row['nama_wakil'],
    ], $results),
    'values' => array_map(fn ($row) => (int) $row['total_suara'], $results),
    'meta' => array_map(fn ($row) => [
        'votes' => (int) $row['total_suara'],
        'percentage' => percentage((int) $row['total_suara'], $totalVotes),
    ], $results),
], JSON_THROW_ON_ERROR);
