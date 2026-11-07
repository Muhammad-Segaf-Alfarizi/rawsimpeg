<?php
error_reporting(0); ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');
session_start(); include 'koneksi.php'; include 'include/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepala') {
    header("Location: login.php"); exit;
}
$today = date('Y-m-d');
$total_pegawai = mysqli_fetch_row(mysqli_query($koneksi,"SELECT COUNT(*) FROM pegawai"))[0] ?? 0;
$total_dl      = mysqli_fetch_row(mysqli_query($koneksi,"SELECT COUNT(DISTINCT nip) FROM riwayat_status WHERE status='DL' AND '$today' BETWEEN tanggal_mulai AND tanggal_selesai"))[0] ?? 0;
$total_cuti    = mysqli_fetch_row(mysqli_query($koneksi,"SELECT COUNT(DISTINCT nip) FROM riwayat_status WHERE status='Cuti' AND '$today' BETWEEN tanggal_mulai AND tanggal_selesai"))[0] ?? 0;
?>
<div id="wrapper">
    <?php include 'include/sidebar.php'; ?>
    <div id="content-wrapper">
        <div id="topbar">
            <div class="topbar-left">
                <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <span class="page-title"><i class="fas fa-th-large" style="color:var(--accent);margin-right:8px;"></i>Dashboard Pimpinan</span>
            </div>
            <div class="topbar-right">
                <a href="logout.php" class="btn-logout confirm-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div id="main-content">
            <div class="grid grid-3" style="margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon accent"><i class="fas fa-users"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= $total_pegawai ?></div>
                        <div class="stat-label">Total Pegawai</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fas fa-plane-departure"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= $total_dl ?></div>
                        <div class="stat-label">Dinas Luar Hari Ini</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fas fa-umbrella-beach"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= $total_cuti ?></div>
                        <div class="stat-label">Cuti Hari Ini</div>
                    </div>
                </div>
            </div>

            <div class="card-dark">
                <div class="card-header-dark">
                    <h5><i class="fas fa-table header-icon"></i> Rekap Kehadiran Pegawai Hari Ini</h5>
                    <span style="font-size:12px;color:var(--text-muted);"><?= date('d F Y') ?></span>
                </div>
                <div class="card-body-dark" style="padding:0;">
                    <div style="overflow-x:auto;">
                        <table class="table-dark-custom auto-datatable" id="tblKepala">
                            <thead>
                                <tr>
                                    <th>Nama Pegawai</th>
                                    <th>NIP</th>
                                    <th>Golongan</th>
                                    <th>Jabatan</th>
                                    <th>Status Hari Ini</th>
                                    <th>Keterangan</th>
                                    <th>Tgl Mulai</th>
                                    <th>Tgl Selesai</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $res_peg = mysqli_query($koneksi, "SELECT nip, nama, golongan, jabatan FROM pegawai ORDER BY nama ASC");
                            $stmt_rw = mysqli_prepare($koneksi,
                                "SELECT status, keterangan, tanggal_mulai, tanggal_selesai, no_spt, file
                                 FROM riwayat_status WHERE nip=? AND ? BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1"
                            );

                            while ($row = mysqli_fetch_assoc($res_peg)):
                                $st_badge = '<span class="badge-custom badge-success"><i class="fas fa-building"></i> Hadir</span>';
                                $ket = '-'; $tgl_m = '-'; $tgl_s = '-';

                                mysqli_stmt_bind_param($stmt_rw, 'ss', $row['nip'], $today);
                                mysqli_stmt_execute($stmt_rw);
                                $rw_res = mysqli_stmt_get_result($stmt_rw);
                                if ($rw = mysqli_fetch_assoc($rw_res)) {
                                    $ket   = htmlspecialchars($rw['keterangan'] ?? '-');
                                    $tgl_m = !empty($rw['tanggal_mulai']) ? date('d M Y', strtotime($rw['tanggal_mulai'])) : '-';
                                    $tgl_s = !empty($rw['tanggal_selesai']) ? date('d M Y', strtotime($rw['tanggal_selesai'])) : '-';
                                    $st_badge = match($rw['status']) {
                                        'DL'   => '<span class="badge-custom badge-info"><i class="fas fa-plane-departure"></i> Dinas Luar</span>',
                                        'Cuti' => '<span class="badge-custom badge-warning"><i class="fas fa-umbrella-beach"></i> Cuti</span>',
                                        'DK'   => '<span class="badge-custom badge-success"><i class="fas fa-building"></i> Di Kantor</span>',
                                        default => '<span class="badge-custom badge-secondary">'.htmlspecialchars($rw['status']).'</span>',
                                    };
                                }
                            ?>
                            <tr>
                                <td><a href="index.php?nip=<?= urlencode($row['nip']) ?>" class="employee-link">
                                    <i class="fas fa-user-circle" style="color:var(--accent);margin-right:6px;"></i>
                                    <?= htmlspecialchars($row['nama']) ?>
                                </a></td>
                                <td style="color:var(--text-muted);font-family:monospace;"><?= htmlspecialchars($row['nip']) ?></td>
                                <td><?= htmlspecialchars($row['golongan'] ?? '-') ?></td>
                                <td style="font-size:12px;"><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
                                <td><?= $st_badge ?></td>
                                <td style="font-size:12px;"><?= $ket ?></td>
                                <td style="font-size:12px;white-space:nowrap;"><?= $tgl_m ?></td>
                                <td style="font-size:12px;white-space:nowrap;"><?= $tgl_s ?></td>
                            </tr>
                            <?php endwhile; mysqli_stmt_close($stmt_rw); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'include/footer.php'; ?>