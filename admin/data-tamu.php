<?php
require_once '../config/app.php';
include '../config/koneksi.php';
/** @var mysqli $conn */

requireLogin('login.php');

$role = $_SESSION['role'];

// Ambil kategori dari URL, default ke 'umum'
$kategori = validGuestCategory($_GET['kategori'] ?? 'umum');

// Query ambil semua data (Pagination akan dihandle oleh DataTables)
$query = mysqli_query($conn, "
    SELECT tamu.*, tl.nama_tujuan
    FROM tamu LEFT JOIN tujuan_layanan tl ON tamu.tujuan_id = tl.id
    WHERE tamu.kategori_tamu = '$kategori'
    ORDER BY tamu.id DESC
");

include '../layouts/header.php';
include '../layouts/sidebar.php';
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<style>
    /* Hilangkan tanda panah (^) dropdown */
    .dataTables_length select {
        appearance: none !important;
        -webkit-appearance: none !important;
        -moz-appearance: none !important;
        background-image: none !important;
        padding-right: 0.5rem !important;
        text-align: center;
        cursor: pointer;
    }

    #tabelTamu td { vertical-align: middle; } /* Menyelaraskan konten vertikal di tengah */

    .truncate-text {
        max-width: 140px;
        overflow: hidden;
        white-space: nowrap; /* Pastikan tidak pecah baris */
        text-overflow: ellipsis;
    }

    @media(max-width:768px) {

        .truncate-text {
            max-width: 100px;
        }

    }
</style>

<div class="card p-4 shadow-lg border-radius-xl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Log Kunjungan Tamu: 
            <?php 
                if($kategori == 'instansi') echo "Instansi";
                elseif($kategori == 'siswa') echo "Alumni / Siswa";
                else echo "Umum";
            ?>
        </h5>
    </div>

    <div class="table-responsive">
        <table id="tabelTamu" class="table align-items-center mb-0 tamu-table">
            <thead>
                <tr>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">No</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nama</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"><?= $kategori == 'siswa' ? 'NISN / Univ' : 'Instansi' ?></th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">No HP</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2"><?= $kategori == 'siswa' ? 'Keperluan' : 'Menemui' ?></th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tanggal</th>
                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($t = mysqli_fetch_assoc($query)) : ?>
                    <tr>

                        <!-- NO -->
                        <td>
                            <p class="text-xs font-weight-bold mb-0 ps-3">
                                <?= $no++; ?>
                            </p>
                        </td>

                        <!-- NAMA -->
                        <td>
                            <p class="text-sm font-weight-bold mb-0 truncate-text text-nowrap">
                                <?= htmlspecialchars($t['nama']); ?>
                            </p>
                        </td>

                        <!-- INSTANSI -->
                        <td>
                            <p class="text-xs mb-0 truncate-text">
                                <?php
                                    if($kategori == 'siswa') {
                                        echo htmlspecialchars($t['nisn']) . ($t['universitas'] ? " / " . htmlspecialchars($t['universitas']) : "");
                                    } else {
                                        echo htmlspecialchars($t['instansi'] ?: '-');
                                    }
                                ?>
                            </p>
                        </td>

                        <!-- NO HP -->
                        <td>
                            <p class="text-xs mb-0 text-nowrap">
                                <?= htmlspecialchars($t['no_hp']); ?>
                            </p>
                        </td>

                        <!-- TUJUAN -->
                        <td>
                            <p class="text-xs font-weight-bold mb-0 truncate-text">
                                <?php // This column correctly displays "Keperluan" or "Sub Kategori" info
                                if ($t['kategori_tamu'] == 'siswa' && $t['sub_kategori'] == 'legalisir') {
                                    echo 'Legalisir';
                                } elseif ($t['kategori_tamu'] == 'siswa' && $t['sub_kategori'] == 'biasa') {
                                    echo htmlspecialchars($t['nama_tujuan'] ?? 'Tidak Ditentukan') . ' (Alumni)';
                                } else {
                                    echo htmlspecialchars($t['nama_tujuan'] ?? 'Tidak Ditentukan');
                                }
                                ?>
                            </p>
                        </td>

                        <!-- TANGGAL -->
                        <td>
                            <p class="text-xs mb-0 text-nowrap">
                                <?= $t['tanggal_kunjungan']; ?>
                            </p>
                        </td>

                        <!-- STATUS -->
                        <td>

                            <?= renderStatusBadge($t['status']); ?>

                        </td>

                        <!-- AKSI -->
                        <td class="text-center">

                            <?php if ($role == 'resepsionis'): ?>
                                <div class="action-group">
                                    <a href="#" class="guest-action detail" title="Lihat Detail">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                    <?php if ($t['status'] == 'pending'): ?>
                                        <a href="approve.php?id=<?= $t['id']; ?>&aksi=terima&asal=tamu&kategori=<?= $kategori ?>" class="guest-action approve" title="Setujui">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="approve.php?id=<?= $t['id']; ?>&aksi=tolak&asal=tamu&kategori=<?= $kategori ?>" class="guest-action reject" title="Tolak">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($kategori == 'siswa' && $t['sub_kategori'] == 'legalisir' && $t['status'] == 'progres'): ?>
                                        <a href="approve.php?id=<?= $t['id']; ?>&aksi=selesai&asal=tamu&kategori=<?= $kategori ?>" class="guest-action finish" title="Tandai Selesai">
                                            <i class="fas fa-flag-checkered"></i>
                                        </a> 
                                    <?php endif; ?>

                                    <?php if ($kategori == 'instansi'): ?>
                                        <a href="upload-pdf.php?id=<?= $t['id']; ?>" class="guest-action pdf <?= !empty($t['file_pdf']) ? 'uploaded' : ''; ?>" title="<?= !empty($t['file_pdf']) ? 'Lihat/Ganti PDF' : 'Upload PDF'; ?>">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-secondary text-xs">-</span>
                            <?php endif; ?>

                        </td>

                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#tabelTamu').DataTable({
            "info": false,
            "language": {
                "lengthMenu": "Tampilkan _MENU_ data",
                "search": "Cari:",
                "paginate": {
                    "next": ">",
                    "previous": "<"
                }
            },
            "pageLength": 10
        });
    });
</script>

<?php include '../layouts/footer.php'; ?>
