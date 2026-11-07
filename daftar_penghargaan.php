<?php
error_reporting(0); ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');
session_start(); include 'koneksi.php';

$allowed_roles = ['admin', 'super_admin', 'kepala', 'pegawai'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php"); exit;
}
$role = $_SESSION['role'];

if (!isset($_GET['nip']) || empty($_GET['nip'])) {
    header("Location: login.php"); exit;
}
$nip_url = $_GET['nip'];

if ($role === 'pegawai' && $nip_url !== $_SESSION['nip']) {
    header("Location: daftar_penghargaan.php?nip=".$_SESSION['nip']); exit;
}

$aksi = $_GET['aksi'] ?? 'tampil';

if ($aksi === 'hapus' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = mysqli_prepare($koneksi, "DELETE FROM daftar_penghargaan WHERE id_daftar_penghargaan=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: daftar_penghargaan.php?nip=".urlencode($nip_url)."&saved=hapus"); exit;
}

if ($aksi === 'simpan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_penghargaan'] ?? '');
    $id   = (int)($_POST['id_penghargaan'] ?? 0);
    if (empty($nama)) {
        header("Location: daftar_penghargaan.php?nip=".urlencode($nip_url)."&aksi=".($id ? 'edit&id='.$id : 'tambah')."&err=1"); exit;
    }
    if ($id) {
        $stmt = mysqli_prepare($koneksi, "UPDATE daftar_penghargaan SET nama_penghargaan=? WHERE id_daftar_penghargaan=?");
        mysqli_stmt_bind_param($stmt, 'si', $nama, $id);
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO daftar_penghargaan (nip, nama_penghargaan) VALUES (?,?)");
        mysqli_stmt_bind_param($stmt, 'ss', $nip_url, $nama);
    }
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: daftar_penghargaan.php?nip=".urlencode($nip_url)."&saved=".($id ? 'edit' : 'tambah')); exit;
}

$nama_pegawai = 'Pegawai Tidak Ditemukan';
$stmt_p = mysqli_prepare($koneksi, "SELECT nama FROM pegawai WHERE nip=? LIMIT 1");
mysqli_stmt_bind_param($stmt_p, 's', $nip_url);
mysqli_stmt_execute($stmt_p);
$rp = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_p));
mysqli_stmt_close($stmt_p);
if ($rp) $nama_pegawai = $rp['nama'];

$data_edit = null;
if ($aksi === 'edit' && isset($_GET['id'])) {
    $id_e = (int)$_GET['id'];
    $stmt_e = mysqli_prepare($koneksi, "SELECT * FROM daftar_penghargaan WHERE id_daftar_penghargaan=?");
    mysqli_stmt_bind_param($stmt_e, 'i', $id_e);
    mysqli_stmt_execute($stmt_e);
    $data_edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_e));
    mysqli_stmt_close($stmt_e);
}

$list = [];
$stmt_l = mysqli_prepare($koneksi, "SELECT * FROM daftar_penghargaan WHERE nip=? ORDER BY id_daftar_penghargaan ASC");
mysqli_stmt_bind_param($stmt_l, 's', $nip_url);
mysqli_stmt_execute($stmt_l);
$res_l = mysqli_stmt_get_result($stmt_l);
while ($r = mysqli_fetch_assoc($res_l)) $list[] = $r;
mysqli_stmt_close($stmt_l);

include 'include/header.php';
?>
<div id="wrapper">
    <?php include 'include/sidebar.php'; ?>
    <div id="content-wrapper">
        <div id="topbar">
            <div class="topbar-left">
                <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <a href="index.php?nip=<?= urlencode($nip_url) ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                <span class="page-title"><i class="fas fa-award" style="color:var(--accent);margin-right:8px;"></i>Daftar Penghargaan</span>
            </div>
            <div class="topbar-right">
                <a href="logout.php" class="btn-logout confirm-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div id="main-content">
            <?php if (isset($_GET['saved'])): ?>
            <div class="alert-dark alert-success-dark" style="margin-bottom:16px;">
                <i class="fas fa-check-circle"></i>
                <?= ['tambah'=>'Data penghargaan berhasil ditambahkan.','edit'=>'Data penghargaan berhasil diperbarui.','hapus'=>'Data penghargaan berhasil dihapus.'][$_GET['saved']] ?? 'Berhasil.' ?>
            </div>
            <?php endif; ?>

            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding:16px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);">
                <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:white;">
                    <?= strtoupper(substr($nama_pegawai, 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight:600;"><?= htmlspecialchars($nama_pegawai) ?></div>
                    <div style="font-size:12px;color:var(--text-muted);font-family:monospace;">NIP: <?= htmlspecialchars($nip_url) ?></div>
                </div>
                <span class="badge-custom badge-warning" style="margin-left:auto;"><i class="fas fa-award"></i> <?= count($list) ?> Penghargaan</span>
            </div>

            <?php if ($aksi === 'tambah' || $aksi === 'edit'): ?>
            <div class="card-dark" style="max-width:600px;">
                <div class="card-header-dark">
                    <h5><i class="fas fa-<?= $aksi === 'edit' ? 'edit' : 'plus-circle' ?> header-icon"></i>
                        <?= $aksi === 'edit' ? 'Edit Penghargaan' : 'Tambah Penghargaan Baru' ?>
                    </h5>
                    <a href="daftar_penghargaan.php?nip=<?= urlencode($nip_url) ?>" class="btn-back" style="font-size:12px;">Batal</a>
                </div>
                <div class="card-body-dark">
                    <?php if (isset($_GET['err'])): ?>
                    <div class="alert-dark alert-danger-dark"><i class="fas fa-exclamation-circle"></i> Nama penghargaan tidak boleh kosong.</div>
                    <?php endif; ?>
                    <form method="POST" action="daftar_penghargaan.php?nip=<?= urlencode($nip_url) ?>&aksi=simpan">
                        <?php if ($aksi === 'edit' && $data_edit): ?>
                        <input type="hidden" name="id_penghargaan" value="<?= $data_edit['id_daftar_penghargaan'] ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label class="form-label-dark">Nama Penghargaan <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="nama_penghargaan" class="form-control-dark"
                                   value="<?= htmlspecialchars($data_edit['nama_penghargaan'] ?? '') ?>"
                                   placeholder="Contoh: Satyalancana Karya Satya X Tahun" required>
                        </div>
                        <button type="submit" class="btn-primary-dark">
                            <i class="fas fa-save"></i> <?= $aksi === 'edit' ? 'Simpan Perubahan' : 'Simpan Penghargaan' ?>
                        </button>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <div class="card-dark">
                <div class="card-header-dark">
                    <h5><i class="fas fa-list header-icon"></i> Riwayat Penghargaan</h5>
                    <a href="daftar_penghargaan.php?nip=<?= urlencode($nip_url) ?>&aksi=tambah" class="btn-primary-dark">
                        <i class="fas fa-plus"></i> Tambah Penghargaan
                    </a>
                </div>
                <div class="card-body-dark" style="padding:0;">
                    <?php if (empty($list)): ?>
                    <div style="text-align:center;padding:48px;color:var(--text-muted);">
                        <i class="fas fa-award" style="font-size:40px;margin-bottom:12px;opacity:0.3;"></i>
                        <p>Belum ada data penghargaan.</p>
                        <a href="daftar_penghargaan.php?nip=<?= urlencode($nip_url) ?>&aksi=tambah" class="btn-primary-dark" style="margin-top:12px;">
                            <i class="fas fa-plus"></i> Tambah Penghargaan Pertama
                        </a>
                    </div>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="table-dark-custom auto-datatable">
                            <thead>
                                <tr>
                                    <th style="width:50px;text-align:center;">No</th>
                                    <th>Nama Penghargaan</th>
                                    <th style="width:140px;text-align:center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($list as $i => $p): ?>
                            <tr>
                                <td style="text-align:center;color:var(--text-muted);"><?= $i + 1 ?></td>
                                <td><i class="fas fa-medal" style="color:var(--warning);margin-right:8px;"></i><?= htmlspecialchars($p['nama_penghargaan']) ?></td>
                                <td style="text-align:center;">
                                    <div style="display:flex;gap:6px;justify-content:center;">
                                        <a href="daftar_penghargaan.php?nip=<?= urlencode($nip_url) ?>&aksi=edit&id=<?= $p['id_daftar_penghargaan'] ?>" class="btn-warning-dark btn-sm-custom">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="daftar_penghargaan.php?nip=<?= urlencode($nip_url) ?>&aksi=hapus&id=<?= $p['id_daftar_penghargaan'] ?>" class="btn-danger-dark btn-sm-custom confirm-delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include 'include/footer.php'; ?>