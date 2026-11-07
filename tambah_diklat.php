<?php
// Bagian Awal - Setup dan Keamanan
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'koneksi.php';

// Atur Timezone
date_default_timezone_set('Asia/Jakarta');

// Cek peran, hanya admin atau super_admin yang boleh mengakses
$allowed_roles = ['admin', 'super_admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

// Validasi NIP dari URL, pastikan NIP ada dan tidak kosong
if (!isset($_GET['nip']) || empty($_GET['nip'])) {
    echo "<script>alert('ERROR: NIP pegawai tidak ditemukan.'); window.history.back();</script>";
    exit;
}
$nip_url = $_GET['nip'];

// Ambil nama pegawai untuk ditampilkan di form, sekaligus validasi NIP
$nama_pegawai = '';
$query_pegawai = "SELECT nama FROM pegawai WHERE nip = ? LIMIT 1";
if ($stmt_pegawai = mysqli_prepare($koneksi, $query_pegawai)) {
    mysqli_stmt_bind_param($stmt_pegawai, "s", $nip_url);
    mysqli_stmt_execute($stmt_pegawai);
    $result_pegawai = mysqli_stmt_get_result($stmt_pegawai);
    if ($data_pegawai = mysqli_fetch_assoc($result_pegawai)) {
        $nama_pegawai = $data_pegawai['nama'];
    } else {
        // Jika NIP tidak ditemukan di database, hentikan proses
        echo "<script>alert('ERROR: Data pegawai dengan NIP ini tidak ditemukan.'); window.location.href='index_admin.php';</script>";
        exit;
    }
    mysqli_stmt_close($stmt_pegawai);
}

// --- Logika untuk Memproses Form (Saat Tombol "Simpan" Ditekan) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama_diklat = $_POST['nama_diklat'];

    // Validasi input tidak boleh kosong
    if (empty($nama_diklat)) {
        $error_message = "Nama diklat tidak boleh kosong.";
    } else {
        // Lanjutkan proses simpan ke database
        $query_insert = "INSERT INTO daftar_diklat (nip, nama_diklat) VALUES (?, ?)";
        
        if ($stmt_insert = mysqli_prepare($koneksi, $query_insert)) {
            mysqli_stmt_bind_param($stmt_insert, "ss", $nip_url, $nama_diklat);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                // Jika berhasil, tampilkan notifikasi dan redirect
                echo "<script>
                        alert('Data diklat berhasil ditambahkan.');
                        window.location.href = 'daftar_diklat.php?nip=" . urlencode($nip_url) . "';
                      </script>";
                exit;
            } else {
                // Jika query gagal dieksekusi
                $error_message = "Gagal menyimpan data. Terjadi kesalahan pada server.";
                error_log("Gagal insert diklat: " . mysqli_stmt_error($stmt_insert));
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $error_message = "Gagal menyiapkan query. Terjadi kesalahan pada server.";
            error_log("Gagal prepare query insert diklat: " . mysqli_error($koneksi));
        }
    }
}

// Sertakan header HTML setelah semua logika PHP, terutama redirect
include 'include/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="container-fluid">
                    <br>
                    <a href="daftar_diklat.php?nip=<?= htmlspecialchars($nip_url) ?>" class="btn btn-primary mb-3">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>

                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Form Tambah Diklat</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($error_message)): ?>
                                        <div class="alert alert-danger"><?= $error_message ?></div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="">
                                        <div class="form-group">
                                            <label>Nama Pegawai</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($nama_pegawai) ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>NIP</label>
                                            <input type="text" class="form-control" name="nip" value="<?= htmlspecialchars($nip_url) ?>" readonly>
                                        </div>
                                        <hr>
                                        <div class="form-group">
                                            <label for="nama_diklat">Nama Diklat</label>
                                            <input type="text" class="form-control" id="nama_diklat" name="nama_diklat" placeholder="Ketikkan Nama Diklat Disini" required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary mt-3">
                                            <i class="fas fa-save"></i> Simpan Diklat
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'include/footer.php'; ?>
        </div>
    </div>
</body>
</html>