<?php
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');

session_start();
include 'koneksi.php';
include 'include/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php"); exit;
}

$today = date('Y-m-d');

// Statistik ringkasan
$total_pegawai   = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM pegawai"))[0] ?? 0;
$total_dl        = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(DISTINCT nip) FROM riwayat_status WHERE status='DL' AND '$today' BETWEEN tanggal_mulai AND tanggal_selesai"))[0] ?? 0;
$total_cuti      = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(DISTINCT nip) FROM riwayat_status WHERE status='Cuti' AND '$today' BETWEEN tanggal_mulai AND tanggal_selesai"))[0] ?? 0;
$total_abk_belum = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM pegawai WHERE nip NOT IN (SELECT DISTINCT nip FROM abk)"))[0] ?? 0;
?>
<div id="wrapper">
    <?php include 'include/sidebar.php'; ?>
    <div id="content-wrapper">
        <!-- Topbar -->
        <div id="topbar">
            <div class="topbar-left">
                <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <span class="page-title"><i class="fas fa-th-large" style="color:var(--accent);margin-right:8px;"></i>Dashboard Super Admin</span>
            </div>
            <div class="topbar-right">
                <a href="logout.php" class="btn-logout confirm-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div id="main-content">
            <!-- Stats -->
            <div class="grid grid-4" style="margin-bottom:24px;">
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
                <div class="stat-card">
                    <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-text">
                        <div class="stat-value"><?= $total_abk_belum ?></div>
                        <div class="stat-label">Belum Isi ABK</div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="card-dark">
                <div class="card-header-dark">
                    <h5><i class="fas fa-table header-icon"></i> Data Pegawai BGTK</h5>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <a href="input_super_admin.php" class="btn-primary-dark">
                            <i class="fas fa-user-plus"></i> Tambah Pegawai
                        </a>
                        <a href="tambah_user_super_admin.php" class="btn-info-dark">
                            <i class="fas fa-user-shield"></i> Tambah User
                        </a>
                    </div>
                </div>
                <div class="card-body-dark" style="padding:0;">
                    <div style="overflow-x:auto;">
                        <table class="table-dark-custom auto-datatable" id="tblPegawai">
                            <thead>
                                <tr>
                                    <th>Nama Pegawai</th>
                                    <th>NIP</th>
                                    <th>Gol / Jabatan</th>
                                    <th>Status Hari Ini</th>
                                    <th>Detail Status</th>
                                    <th style="text-align:center;">ABK</th>
                                    <th style="text-align:center;">TNA</th>
                                    <th style="text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $stmt_peg = mysqli_prepare($koneksi,
                                "SELECT p.nip, p.nama, p.golongan, p.jabatan,
                                    (SELECT COUNT(id_abk) FROM abk WHERE nip = p.nip) AS jml_abk,
                                    (SELECT COUNT(id_tna) FROM tna WHERE nip = p.nip) AS jml_tna,
                                    (SELECT COUNT(id_tna) FROM tna WHERE nip = p.nip AND status_pelaksanaan = 'Belum') AS tna_belum,
                                    (SELECT COUNT(id_tna) FROM tna WHERE nip = p.nip AND status_pelaksanaan = 'Sedang Berjalan') AS tna_berjalan
                                 FROM pegawai p ORDER BY p.nama ASC"
                            );
                            mysqli_stmt_execute($stmt_peg);
                            $res_peg = mysqli_stmt_get_result($stmt_peg);

                            $stmt_rw = mysqli_prepare($koneksi,
                                "SELECT status, keterangan, tanggal_mulai, tanggal_selesai, no_spt, file
                                 FROM riwayat_status WHERE nip = ? AND ? BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1"
                            );

                            while ($row = mysqli_fetch_assoc($res_peg)):
                                // Status harian
                                $st = '-'; $detail = '-';
                                mysqli_stmt_bind_param($stmt_rw, 'ss', $row['nip'], $today);
                                mysqli_stmt_execute($stmt_rw);
                                $rw_res = mysqli_stmt_get_result($stmt_rw);
                                if ($rw = mysqli_fetch_assoc($rw_res)) {
                                    $st = $rw['status'];
                                    $detail = htmlspecialchars($rw['keterangan'] ?? '-');
                                    if (!empty($rw['file'])) {
                                        $detail .= ' &nbsp;<a href="'.htmlspecialchars($rw['file']).'" target="_blank" style="font-size:11px;">[SPT]</a>';
                                    }
                                }

                                // Status badge
                                $st_badge = match($st) {
                                    'DL'   => '<span class="badge-custom badge-info"><i class="fas fa-plane-departure"></i> DL</span>',
                                    'Cuti' => '<span class="badge-custom badge-warning"><i class="fas fa-umbrella-beach"></i> Cuti</span>',
                                    'DK'   => '<span class="badge-custom badge-secondary"><i class="fas fa-building"></i> DK</span>',
                                    default => '<span class="badge-custom badge-secondary">&mdash;</span>',
                                };

                                // ABK badge
                                $abk_badge = $row['jml_abk'] > 0
                                    ? '<span class="badge-custom badge-success"><i class="fas fa-check-circle"></i> Sudah</span>'
                                    : '<span class="badge-custom badge-danger"><i class="fas fa-times-circle"></i> Belum</span>';

                                // TNA badge
                                if ($row['tna_belum'] > 0)
                                    $tna_badge = '<span class="badge-custom badge-danger"><i class="fas fa-exclamation-triangle"></i> Butuh</span>';
                                elseif ($row['tna_berjalan'] > 0)
                                    $tna_badge = '<span class="badge-custom badge-primary"><i class="fas fa-spinner fa-spin"></i> Berjalan</span>';
                                elseif ($row['jml_tna'] > 0)
                                    $tna_badge = '<span class="badge-custom badge-success"><i class="fas fa-check"></i> Selesai</span>';
                                else
                                    $tna_badge = '<span class="badge-custom badge-secondary">Belum Analisis</span>';
                            ?>
                            <tr>
                                <td>
                                    <a href="index.php?nip=<?= urlencode($row['nip']) ?>" class="employee-link">
                                        <i class="fas fa-user-circle" style="color:var(--accent);margin-right:6px;"></i>
                                        <?= htmlspecialchars($row['nama']) ?>
                                    </a>
                                </td>
                                <td style="color:var(--text-muted);font-family:monospace;"><?= htmlspecialchars($row['nip']) ?></td>
                                <td>
                                    <span style="font-weight:600;"><?= htmlspecialchars($row['golongan'] ?? '-') ?></span><br>
                                    <span style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($row['jabatan'] ?? '-') ?></span>
                                </td>
                                <td><?= $st_badge ?></td>
                                <td style="font-size:12px;color:var(--text-secondary);"><?= $detail ?></td>
                                <td style="text-align:center;"><?= $abk_badge ?></td>
                                <td style="text-align:center;"><?= $tna_badge ?></td>
                                <td style="vertical-align: middle; width: 140px;">
                                    <!-- Container Utama: Flex Column (Bawah ke atas) -->
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
                            
                                        <!-- Baris Atas: Tombol Edit (Lebar penuh) -->
                                        <a href="edit_super_admin.php?nip=<?= htmlspecialchars($row['nip']) ?>" class="btn btn-warning-dark btn-sm-custom" style="width: 100%; justify-content: center;">
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
                            <?php endwhile; mysqli_stmt_close($stmt_rw); mysqli_stmt_close($stmt_peg); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div><!-- /#main-content -->
    </div><!-- /#content-wrapper -->
</div><!-- /#wrapper -->
<?php include 'include/footer.php'; ?>