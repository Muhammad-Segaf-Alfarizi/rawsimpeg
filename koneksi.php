<?php
// Konfigurasi database
$host     = "localhost";      // Server database, biasanya localhost
$user     = "root";           // Username MySQL (default XAMPP: root)
$password = "";               // Password MySQL (default XAMPP: kosong)
$database = "pegawaibgtk";   // Nama database

// Membuat koneksi
$koneksi = mysqli_connect($host, $user, $password, $database);

?>
