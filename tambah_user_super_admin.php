<?php
error_reporting(0); ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');
session_start(); include 'koneksi.php'; include 'include/header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php"); exit;
}

$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $nip      = trim($_POST['nip']      ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = $_POST['role']          ?? '';

    $allowed_roles = ['super_admin', 'admin', 'kepala', 'pegawai'];

    if (empty($nama) || empty($nip) || empty($password) || empty($role)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!in_array($role, $allowed_roles)) {
        $error = 'Role tidak valid.';
    } else {
        // Cek duplikat NIP
        $stmt_cek = mysqli_prepare($koneksi, "SELECT nip FROM user WHERE nip = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_cek, 's', $nip);
        mysqli_stmt_execute($stmt_cek);
        mysqli_stmt_store_result($stmt_cek);
        $dup = mysqli_stmt_num_rows($stmt_cek) > 0;
        mysqli_stmt_close($stmt_cek);

        if ($dup) {
            $error = 'NIP sudah terdaftar sebagai user.';
        } else {
            $pwd_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt_ins = mysqli_prepare($koneksi,
                "INSERT INTO user (nama, nip, password, role) VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt_ins, 'ssss', $nama, $nip, $pwd_hash, $role);
            if (mysqli_stmt_execute($stmt_ins)) {
                $success = "User <strong>".htmlspecialchars($nama)."</strong> berhasil ditambahkan.";
            } else {
                $error = 'Gagal menambahkan user. Coba lagi.';
            }
            mysqli_stmt_close($stmt_ins);
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
                <a href="index_super_admin.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                <span class="page-title"><i class="fas fa-user-plus" style="color:var(--accent);margin-right:8px;"></i>Tambah User Baru</span>
            </div>
            <div class="topbar-right">
                <a href="logout.php" class="btn-logout confirm-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div id="main-content">
            <div class="card-dark" style="max-width:600px;margin:0 auto;">
                <div class="card-header-dark">
                    <h5><i class="fas fa-user-shield header-icon"></i> Tambah Akun User Baru</h5>
                </div>
                <div class="card-body-dark">
                    <?php if ($success): ?>
                    <div class="alert-dark alert-success-dark"><i class="fas fa-check-circle"></i> <?= $success ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                    <div class="alert-dark alert-danger-dark"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label-dark">Nama Lengkap <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="nama" class="form-control-dark"
                                   placeholder="Masukkan nama lengkap"
                                   value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label-dark">NIP <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="nip" class="form-control-dark"
                                   placeholder="Masukkan NIP"
                                   value="<?= htmlspecialchars($_POST['nip'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label-dark">Password <span style="color:var(--danger);">*</span></label>
                            <div style="position:relative;">
                                <input type="password" name="password" id="pwd_input" class="form-control-dark"
                                       placeholder="Buat password" required>
                                <button type="button" onclick="togglePwd()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:14px;">
                                    <i class="fas fa-eye" id="pwd_eye"></i>
                                </button>
                            </div>
                            <small style="color:var(--text-muted);font-size:11px;">Password akan di-enkripsi secara otomatis.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label-dark">Role <span style="color:var(--danger);">*</span></label>
                            <select name="role" class="form-control-dark" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                                <option value="kepala">Pimpinan (Kepala)</option>
                                <option value="pegawai">Pegawai</option>
                            </select>
                        </div>

                        <hr class="divider">
                        <div style="display:flex;gap:12px;">
                            <button type="submit" class="btn-primary-dark">
                                <i class="fas fa-user-plus"></i> Tambah User
                            </button>
                            <a href="index_super_admin.php" class="btn-back">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function togglePwd() {
    const i = document.getElementById('pwd_input');
    const e = document.getElementById('pwd_eye');
    i.type = i.type === 'password' ? 'text' : 'password';
    e.className = i.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
<?php include 'include/footer.php'; ?>