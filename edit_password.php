<?php
error_reporting(0); ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');
session_start(); include 'koneksi.php'; include 'include/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php"); exit;
}

$nip_edit = $_GET['nip'] ?? '';
if (empty($nip_edit)) {
    echo "<script>alert('NIP tidak ditemukan.');window.location.href='index_super_admin.php';</script>"; exit;
}

// Ambil data user
$stmt_u = mysqli_prepare($koneksi, "SELECT * FROM user WHERE nip = ? LIMIT 1");
mysqli_stmt_bind_param($stmt_u, 's', $nip_edit);
mysqli_stmt_execute($stmt_u);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_u));
mysqli_stmt_close($stmt_u);

if (!$data) {
    echo "<script>alert('Data user tidak ditemukan.');window.location.href='index_super_admin.php';</script>"; exit;
}

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_baru     = trim($_POST['nama'] ?? '');
    $nip_baru      = trim($_POST['nip'] ?? '');
    $password_baru = trim($_POST['password'] ?? '');
    $role_baru     = $_POST['role'] ?? '';

    $allowed_roles = ['super_admin', 'admin', 'kepala', 'pegawai'];

    if (empty($nama_baru) || empty($nip_baru) || empty($role_baru)) {
        $error = 'Nama, NIP, dan Role wajib diisi.';
    } elseif (!in_array($role_baru, $allowed_roles)) {
        $error = 'Role tidak valid.';
    } else {
        // Cek duplikat NIP
        $stmt_cek = mysqli_prepare($koneksi, "SELECT nip FROM user WHERE nip = ? AND nip != ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_cek, 'ss', $nip_baru, $nip_edit);
        mysqli_stmt_execute($stmt_cek);
        mysqli_stmt_store_result($stmt_cek);
        $dup = mysqli_stmt_num_rows($stmt_cek) > 0;
        mysqli_stmt_close($stmt_cek);

        if ($dup) {
            $error = 'NIP sudah digunakan oleh user lain.';
        } else {
            // Hash password jika diubah
            if (!empty($password_baru)) {
                $pwd_hash = password_hash($password_baru, PASSWORD_DEFAULT);
            } else {
                $pwd_hash = $data['password'];
            }

            $stmt_upd = mysqli_prepare($koneksi,
                "UPDATE user SET nama=?, nip=?, password=?, role=? WHERE nip=?"
            );
            mysqli_stmt_bind_param($stmt_upd, 'sssss', $nama_baru, $nip_baru, $pwd_hash, $role_baru, $nip_edit);
            if (mysqli_stmt_execute($stmt_upd)) {
                $success = 'Data user berhasil diperbarui.';
                // Refresh data
                $stmt_u2 = mysqli_prepare($koneksi, "SELECT * FROM user WHERE nip = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt_u2, 's', $nip_baru);
                mysqli_stmt_execute($stmt_u2);
                $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_u2));
                mysqli_stmt_close($stmt_u2);
                $nip_edit = $nip_baru;
            } else {
                $error = 'Gagal memperbarui data. Coba lagi.';
            }
            mysqli_stmt_close($stmt_upd);
        }
    }
}
?>
<div id="wrapper">
    <?php include 'include/sidebar.php'; ?>
    <div id="content-wrapper">
        <div id="topbar">
            <div class="topbar-left">
                <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <a href="index.php?nip=<?= urlencode($nip_edit) ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali ke Profil
                </a>
                <span class="page-title"><i class="fas fa-user-edit" style="color:var(--accent);margin-right:8px;"></i>Edit Data User</span>
            </div>
            <div class="topbar-right">
                <a href="logout.php" class="btn-logout confirm-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div id="main-content">
            <div class="card-dark" style="max-width:640px;margin:0 auto;">
                <div class="card-header-dark">
                    <h5><i class="fas fa-user-cog header-icon"></i> Edit Data User &mdash; <?= htmlspecialchars($data['nama'] ?? '') ?></h5>
                </div>
                <div class="card-body-dark">
                    <?php if ($success): ?>
                    <div class="alert-dark alert-success-dark"><i class="fas fa-check-circle"></i> <?= $success ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                    <div class="alert-dark alert-danger-dark"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label-dark">Nama Lengkap <span style="color:var(--danger);">*</span></label>
                                <input type="text" name="nama" class="form-control-dark"
                                       value="<?= htmlspecialchars($data['nama'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label-dark">NIP <span style="color:var(--danger);">*</span></label>
                                <input type="text" name="nip" class="form-control-dark"
                                       value="<?= htmlspecialchars($data['nip'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label class="form-label-dark">Password Baru</label>
                                <input type="password" name="password" class="form-control-dark"
                                       placeholder="Kosongkan jika tidak diubah">
                                <small style="color:var(--text-muted);font-size:11px;">Biarkan kosong untuk mempertahankan password lama</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label-dark">Role <span style="color:var(--danger);">*</span></label>
                                <select name="role" class="form-control-dark" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="super_admin" <?= ($data['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                    <option value="admin"       <?= ($data['role'] ?? '') === 'admin'       ? 'selected' : '' ?>>Admin</option>
                                    <option value="kepala"      <?= ($data['role'] ?? '') === 'kepala'      ? 'selected' : '' ?>>Pimpinan (Kepala)</option>
                                    <option value="pegawai"     <?= ($data['role'] ?? '') === 'pegawai'     ? 'selected' : '' ?>>Pegawai</option>
                                </select>
                            </div>
                        </div>

                        <hr class="divider">
                        <div style="display:flex;gap:12px;">
                            <button type="submit" class="btn-primary-dark">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="index_super_admin.php" class="btn-back">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'include/footer.php'; ?>