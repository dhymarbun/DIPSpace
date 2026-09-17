CREATE DATABASE IF NOT EXISTS ppk_demo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ppk_demo;

DROP TABLE IF EXISTS reservations;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS facilities;

CREATE TABLE facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    tipe VARCHAR(50) NOT NULL,
    lokasi VARCHAR(100) NOT NULL,
    kapasitas INT NOT NULL,
    status ENUM('aktif', 'dalam_perbaikan') NOT NULL DEFAULT 'aktif'
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('pengguna', 'petugas', 'admin') NOT NULL DEFAULT 'pengguna'
);

CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    facility_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    tujuan VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservations_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_reservations_facility FOREIGN KEY (facility_id) REFERENCES facilities(id)
);

INSERT INTO facilities (nama, tipe, lokasi, kapasitas, status) VALUES
    ('Laboratorium Komputer A', 'Laboratorium', 'Gedung Teknik Lantai 2', 36, 'aktif'),
    ('Aula Serbaguna Cendekia', 'Aula', 'Gedung Rektorat Lantai 1', 300, 'aktif'),
    ('Ruang Kelas B-203', 'Ruang Kelas', 'Gedung B Lantai 2', 42, 'aktif'),
    ('Ruang Seminar Pascasarjana', 'Ruang Seminar', 'Gedung Pascasarjana Lantai 3', 80, 'aktif'),
    ('Lapangan Basket Kampus', 'Lapangan Olahraga', 'Area Olahraga Barat', 150, 'dalam_perbaikan');

INSERT INTO users (nama, email, password, role) VALUES
    ('Budi Santoso', 'budi@dipspace.test', '$2y$12$IuDb2./mY14Oepepi.rHO.ZSTOVOKZCpJEx2uMWG8bDwUQ5981WQ6', 'pengguna'),
    ('Admin DIPSpace', 'admin@dipspace.test', '$2y$12$IuDb2./mY14Oepepi.rHO.ZSTOVOKZCpJEx2uMWG8bDwUQ5981WQ6', 'admin');
