<?php
$host = "mif.myhost.id";
$user = "mifmyho2_E2";
$pass = "@MIF2025";
$db   = "mifmyho2_E2"; // SESUAI DATABASE BARU KAMU

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// timezone (opsional tapi bagus)
date_default_timezone_set("Asia/Jakarta");