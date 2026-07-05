<?php
require_once __DIR__ . '/includes/functions.php';
require_voter();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('candidates.php');
}

verify_csrf();

$pemilihId = (int) $_SESSION['verified_voter_id'];
$candidateId = (int) ($_POST['candidate_id'] ?? 0);

if ($candidateId <= 0) {
    flash('error', 'Kandidat tidak ditemukan.');
    redirect('candidates.php');
}

if (!voting_is_open()) {
    unset($_SESSION['verified_voter_id'], $_SESSION['pending_voter_id'], $_SESSION['pemilih_id']);
    flash('error', 'Pemungutan suara sedang ditutup');
    redirect('login.php');
}

$pdo = get_pdo();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT id, sudah_memilih FROM pemilih WHERE id = ? FOR UPDATE');
    $stmt->execute([$pemilihId]);
    $pemilih = $stmt->fetch();

    if (!$pemilih || (int) $pemilih['sudah_memilih'] === 1) {
        $pdo->rollBack();
        unset($_SESSION['verified_voter_id'], $_SESSION['pending_voter_id'], $_SESSION['pemilih_id']);
        flash('error', 'Anda sudah menggunakan hak suara');
        redirect('login.php');
    }

    $stmt = $pdo->prepare("SELECT id FROM kandidat WHERE id = ? AND status = 'aktif' LIMIT 1");
    $stmt->execute([$candidateId]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        flash('error', 'Kandidat tidak ditemukan.');
        redirect('candidates.php');
    }

    $stmt = $pdo->prepare('INSERT INTO votes (kandidat_id, created_at) VALUES (?, NOW())');
    $stmt->execute([$candidateId]);

    $stmt = $pdo->prepare('UPDATE pemilih SET sudah_memilih = 1, waktu_memilih = NOW(), updated_at = NOW() WHERE id = ? AND sudah_memilih = 0');
    $stmt->execute([$pemilihId]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Status pemilih gagal diperbarui.');
    }

    $pdo->commit();
    unset($_SESSION['verified_voter_id'], $_SESSION['pending_voter_id'], $_SESSION['pemilih_id']);
    redirect('thank-you.php');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    unset($_SESSION['verified_voter_id'], $_SESSION['pending_voter_id'], $_SESSION['pemilih_id']);
    flash('error', 'Terjadi kesalahan, silakan coba kembali.');
    redirect('login.php');
}
