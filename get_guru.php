<?php
// Matikan error reporting agar tidak merusak format JSON
error_reporting(0);
include 'config/koneksi.php'; 

header('Content-Type: application/json');
// Pastikan koneksi menggunakan UTF-8
mysqli_set_charset($conn, 'utf8');

// Ambil parameter pencarian dan halaman dari Select2
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 10; // Jumlah item per halaman
$offset = ($page - 1) * $limit;

$where = '';
if (!empty($search)) {
    // Escape string untuk mencegah SQL Injection
    $search = mysqli_real_escape_string($conn, $search);
    $where = "WHERE nama_guru LIKE '%$search%'";
}

// Query untuk mendapatkan total jumlah guru (untuk pagination Select2)
$total_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM guru $where");
$total_count = 0;
if ($total_query) {
    $total_row = mysqli_fetch_assoc($total_query);
    $total_count = (int)$total_row['total'];
}

// Query untuk mendapatkan data guru
$query = mysqli_query($conn, "SELECT id, nama_guru FROM guru $where ORDER BY nama_guru ASC LIMIT $limit OFFSET $offset");

$results = [];
if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $results[] = [
            'id' => (string)$row['id'],
            'text' => $row['nama_guru']
        ];
    }
}

// Kembalikan data dalam format JSON yang diharapkan oleh Select2
if (empty($results) && !empty($search)) {
    $results = []; // Kembalikan array kosong jika tidak ditemukan
}

echo json_encode([
    'items' => $results,
    'total_count' => $total_count
]);

mysqli_close($conn);
?>