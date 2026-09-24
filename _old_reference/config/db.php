<?php

$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'ppk_demo';

$conn = mysqli_connect($dbHost, $dbUser, $dbPassword, $dbName);

if (!$conn) {
    die('Database connection failed. Please ensure MySQL is running and import database.sql.');
}

mysqli_set_charset($conn, 'utf8mb4');