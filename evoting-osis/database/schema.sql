CREATE TABLE IF NOT EXISTS admin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pemilih (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nomor_induk VARCHAR(40) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    jenis ENUM('Siswa', 'Guru') NOT NULL,
    kelas VARCHAR(40) NULL,
    sudah_memilih TINYINT(1) NOT NULL DEFAULT 0,
    waktu_memilih DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    INDEX idx_pemilih_status (sudah_memilih),
    INDEX idx_pemilih_jenis (jenis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kandidat (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nomor_urut INT UNSIGNED NOT NULL UNIQUE,
    foto VARCHAR(255) NULL,
    nama_ketua VARCHAR(150) NOT NULL,
    nama_wakil VARCHAR(150) NOT NULL,
    visi TEXT NOT NULL,
    misi TEXT NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    INDEX idx_kandidat_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS votes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kandidat_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_votes_kandidat FOREIGN KEY (kandidat_id) REFERENCES kandidat(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_votes_kandidat (kandidat_id),
    INDEX idx_votes_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pengaturan (
    kunci VARCHAR(80) PRIMARY KEY,
    nilai TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pengaturan (kunci, nilai, updated_at)
VALUES
    ('voting_status', 'closed', NOW()),
    ('school_name', 'SMKN 20 Jakarta', NOW())
ON DUPLICATE KEY UPDATE kunci = kunci;
