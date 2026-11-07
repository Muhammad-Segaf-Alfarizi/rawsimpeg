<?php
error_reporting(0); ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');
session_start(); include 'koneksi.php'; include 'include/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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
                <span class="page-title"><i class="fas fa-th-large" style="color:var(--accent);margin-right:8px;"></i>Dashboard Admin</span>
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
                    <h5><i class="fas fa-table header-icon"></i> Data Pegawai BGTK</h5>
                </div>
                <div class="card-body-dark" style="padding:0;">
                    <div style="overflow-x:auto;">
                        <table class="table-dark-custom auto-datatable" id="tblAdmin">
                            <thead>
                                <tr>
                                    <th>Nama Pegawai</th>
                                    <th>NIP</th>
                                    <th>Golongan</th>
                                    <th>Jabatan</th>
                                    <th>Status Hari Ini</th>
                                    <th>No SPT / File</th>
                                    <th>Keterangan</th>
                                    <th>Tgl Mulai</th>
                                    <th>Tgl Selesai</th>
                                    <th style="text-align:center;">Aksi</th>
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
                                $st_disp = '-'; $spt_disp = '-'; $ket_disp = '-';
                                $tgl_mulai = '-'; $tgl_selesai = '-';
                                $st_badge = '<span class="badge-custom badge-secondary">&mdash;</span>';

                                mysqli_stmt_bind_param($stmt_rw, 'ss', $row['nip'], $today);
                                mysqli_stmt_execute($stmt_rw);
                                $rw_res = mysqli_stmt_get_result($stmt_rw);
                                if ($rw = mysqli_fetch_assoc($rw_res)) {
                                    $st_disp = $rw['status'];
                                    $spt_disp = htmlspecialchars($rw['no_spt'] ?? '-');
                                    if (!empty($rw['file']))
                                        $spt_disp .= ' <a href="'.htmlspecialchars($rw['file']).'" target="_blank" style="font-size:11px;">[File]</a>';
                                    $ket_disp = htmlspecialchars($rw['keterangan'] ?? '-');
                                    $tgl_mulai = !empty($rw['tanggal_mulai']) ? date('d M Y', strtotime($rw['tanggal_mulai'])) : '-';
                                    $tgl_selesai = !empty($rw['tanggal_selesai']) ? date('d M Y', strtotime($rw['tanggal_selesai'])) : '-';
                                    $st_badge = match($rw['status']) {
                                        'DL'   => '<span class="badge-custom badge-info"><i class="fas fa-plane-departure"></i> DL</span>',
                                        'Cuti' => '<span class="badge-custom badge-warning"><i class="fas fa-umbrella-beach"></i> Cuti</span>',
                                        'DK'   => '<span class="badge-custom badge-secondary"><i class="fas fa-building"></i> DK</span>',
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
                                <td style="font-size:12px;"><?= $spt_disp ?></td>
                                <td style="font-size:12px;"><?= $ket_disp ?></td>
                                <td style="font-size:12px;white-space:nowrap;"><?= $tgl_mulai ?></td>
                                <td style="font-size:12px;white-space:nowrap;"><?= $tgl_selesai ?></td>
                                                                <td style="vertical-align: middle; width: 140px;">
                                    <!-- Container Utama: Flex Column (Bawah ke atas) -->
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
                            
                                        <!-- Baris Atas: Tombol Edit (Lebar penuh) -->
                                        <a href="index.php?nip=<?= htmlspecialchars($row['nip']) ?>" class="btn btn-warning-dark btn-sm-custom" style="width: 100%; justify-content: center;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        
                                        <!-- Baris Bawah: Container Flex Row untuk ABK & TNA -->
                                        <div style="display: flex; gap: 6px; width: 100%;">
                                            <!-- Tombol ABK: flex 1 agar membagi rata sisa ruang -->
                                            <a href="abk.php?nip=<?= htmlspecialchars($row['nip']) ?>" class="btn btn-info-dark btn-sm-custom" style="flex: 1; justify-content: center;">
                                                <i class="fas fa-briefcase"></i> ABK
                                            </a>
                                            
                                            <!-- Tombol TNA: flex 1 agar membagi rata sisa ruang -->
                                            <a href="tna.php?nip=<?= htmlspecialchars($row['nip']) ?>" class="btn btn-success-dark btn-sm-custom" style="flex: 1; justify-content: center;">
                                                <i class="fas fa-clipboard-list"></i> TNA
                                            </a>
                                        </div>

                                    </div>
                                </td>
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