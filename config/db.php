<?php
// veri tabanı bağlantısı 
$host = 'localhost';
$db   = 'siber_guvenlik'; 
$user = 'root'; 
$pass = ''; 
$charset = 'utf8mb4';

$connect = mysqli_connect($host, $user, $pass, $db);

// Bağlantıyı kontrol etme
if (!$connect) {
    die("Bağlantı hatası: " . mysqli_connect_error());
}

mysqli_set_charset($connect, $charset);
?>