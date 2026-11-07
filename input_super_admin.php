<?php
session_start();
include 'koneksi.php';
include 'include/header.php';

// Cek apakah user sudah login dan berperan sebagai admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Ambil data teks dari form
    $nama = $_POST['nama'];
    $nip = $_POST['nip'];
    $status_pegawai = $_POST['status_pegawai'];
    $golongan = $_POST['golongan'];
    $jabatan = $_POST['jabatan'];
    $tgl_lahir = $_POST['tgl_lahir'];
    $tmt_awal = $_POST['tmt_awal'];

    // 2. Tentukan direktori penyimpanan file
    $target_dir = "uploads/dokumen_pegawai/"; // Pastikan direktori ini ada dan memiliki izin tulis (write permissions)

    // Buat direktori jika belum ada
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // 0777 adalah izin penuh, true untuk rekursif
    }

    // 3. Fungsi untuk menangani unggahan file
    function uploadFile($fileInputName, $target_dir, $nip) {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] == 0) {
            $file_tmp = $_FILES[$fileInputName]['tmp_name'];
            $file_name = basename($_FILES[$fileInputName]['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Tambahan: Validasi Tipe dan Ukuran File (disarankan)
            $allowed_extensions = ['pdf']; // Sesuaikan dengan jenis file yang diizinkan
            $max_file_size = 5 * 1024 * 1024; // Maksimal 10 MB

            if (!in_array($file_ext, $allowed_extensions)) {
                // Handle error: Tipe file tidak diizinkan
                error_log("Error: Tipe file " . $file_ext . " tidak diizinkan untuk " . $fileInputName);
                return false;
            }
            if ($_FILES[$fileInputName]['size'] > $max_file_size) {
                // Handle error: Ukuran file terlalu besar
                error_log("Error: Ukuran file " . $fileInputName . " (" . $_FILES[$fileInputName]['size'] . " bytes) melebihi batas " . $max_file_size . " bytes.");
                return false;
            }

            // Buat nama file unik untuk menghindari tabrakan, contoh: NIP_JENISFILE.ekstensi
            $new_file_name = $nip . "_" . $fileInputName . "." . $file_ext;
            $target_file = $target_dir . $new_file_name;

            // Pindahkan file dari lokasi sementara ke lokasi target
            if (move_uploaded_file($file_tmp, $target_file)) {
                return $target_file; // Kembalikan path file yang disimpan
            } else {
                error_log("Error: Gagal memindahkan file " . $file_tmp . " ke " . $target_file);
                return false; // Gagal mengunggah
            }
        }
        return null; // Tidak ada file yang diunggah, atau ada error dari PHP itu sendiri
    }

    // 4. Unggah setiap file dan simpan path-nya
    $path_sk_cpns = uploadFile('sk_cpns', $target_dir, $nip);
    $path_sk_pns = uploadFile('sk_pns', $target_dir, $nip);
    $path_sk_tukin = uploadFile('sk_tukin', $target_dir, $nip);
    $path_sk_jabatan_terakhir = uploadFile('sk_jabatan_terakhir', $target_dir, $nip);
    $path_sk_pak = uploadFile('sk_pak', $target_dir, $nip);
    $path_skp_terakhir = uploadFile('skp_terakhir', $target_dir, $nip);

    // 5. Query INSERT ke database
    // Tambahkan kolom created_at dan updated_at
    $query = "INSERT INTO pegawai (
                nama, nip, status_pegawai, golongan, jabatan, tanggal_lahir, tmt_awal,
                sk_cpns, sk_pns, sk_tukin, sk_jabatan_terakhir, sk_pak, skp_terakhir,
                created_at, updated_at
              ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
              )";

    // Menggunakan Prepared Statements untuk keamanan (melindungi dari SQL Injection)
    if ($stmt = mysqli_prepare($koneksi, $query)) {
        // 's' untuk string, jumlah 's' harus sesuai dengan jumlah tanda '?'
        // Kecuali untuk NOW(), yang langsung dieksekusi oleh MySQL, jadi tidak perlu di bind_param
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssssssss", // Total 13 's' untuk 13 parameter sebelum NOW()
            $nama, $nip, $status_pegawai, $golongan, $jabatan, $tgl_lahir, $tmt_awal,
            $path_sk_cpns, $path_sk_pns, $path_sk_tukin, $path_sk_jabatan_terakhir, $path_sk_pak, $path_skp_terakhir
        );

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Data dan file berhasil ditambahkan!'); window.location.href='index_super_admin.php';</script>";
        } else {
            // Tampilkan error database lebih detail untuk debugging
            echo "<script>alert('Gagal menambahkan data: " . mysqli_stmt_error($stmt) . "');</script>";
            error_log("SQL Error: " . mysqli_stmt_error($stmt) . " Query: " . $query);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Gagal menyiapkan query: " . mysqli_error($koneksi) . "');</script>";
        error_log("Prepare failed: " . mysqli_error($koneksi));
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
                <span class="page-title"><i class="fas fa-user-plus" style="color:var(--accent);margin-right:8px;"></i>Tambah Data Pegawai</span>
            </div>
            <div class="topbar-right">
                <a href="logout.php" class="btn-logout confirm-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div id="main-content">
            <div class="card-dark" style="max-width:860px;margin:0 auto;">
                <div class="card-header-dark">
                    <h5><i class="fas fa-user-plus header-icon"></i> Form Tambah Data Pegawai Baru</h5>
                </div>
                <div class="card-body-dark">
                    <form method="POST" enctype="multipart/form-data">
                        <p class="section-title">Data Identitas Pegawai</p>
                        <div class="grid grid-3">
                            <div class="form-group">
                                <label class="form-label-dark">Nama Lengkap <span style="color:var(--danger);">*</span></label>
                                <input type="text" name="nama" class="form-control-dark" placeholder="Masukkan nama lengkap" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label-dark">NIP <span style="color:var(--danger);">*</span></label>
                                <input type="text" name="nip" class="form-control-dark" placeholder="Nomor Induk Pegawai" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label-dark">Status Pegawai <span style="color:var(--danger);">*</span></label>
                                <select name="status_pegawai" class="form-control-dark">
                                    <option value="">-- Pilih Status --</option>
                                    <option value="PNS">PNS</option>
                                    <option value="PPPK">PPPK</option>
                                    <option value="PPPNP">PPPNP</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-4">
                            <div class="form-group">
                                <label class="form-label-dark">Golongan</label>
                                <input type="text" name="golongan" class="form-control-dark" placeholder="Contoh: III/a">
                            </div>
                            <div class="form-group">
                                <label class="form-label-dark">Jabatan</label>
                                <input type="text" name="jabatan" class="form-control-dark" placeholder="Jabatan fungsional">
                            </div>
                            <div class="form-group">
                                <label class="form-label-dark">Tanggal Lahir</label>
                                <input type="date" name="tgl_lahir" class="form-control-dark">
                            </div>
                            <div class="form-group">
                                <label class="form-label-dark">TMT Awal <span style="color:var(--danger);">*</span></label>
                                <input type="date" name="tmt_awal" class="form-control-dark" required>
                            </div>
                        </div>

                        <hr class="divider">
                        <p class="section-title">Unggah Dokumen SK <span style="color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0;">(opsional, format PDF, maks 5MB)</span></p>

                        <div class="grid grid-3">
                            <?php
                            $sk_fields = [
                                ['name'=>'sk_cpns',              'label'=>'SK CPNS'],
                                ['name'=>'sk_pns',               'label'=>'SK PNS'],
                                ['name'=>'sk_tukin',             'label'=>'SK Tukin'],
                                ['name'=>'sk_jabatan_terakhir',  'label'=>'SK Jabatan Terakhir'],
                                ['name'=>'sk_pak',               'label'=>'SK PAK'],
                                ['name'=>'skp_terakhir',         'label'=>'SKP Terakhir'],
                            ];
                            foreach ($sk_fields as $sf): ?>
                            <div class="form-group">
                                <label class="form-label-dark"><?= $sf['label'] ?></label>
                                <input type="file" name="<?= $sf['name'] ?>" class="form-control-dark" accept=".pdf" style="padding:8px;">
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <hr class="divider">
                        <div style="display:flex;gap:12px;">
                            <button type="submit" class="btn-primary-dark">
                                <i class="fas fa-save"></i> Simpan Data Pegawai
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