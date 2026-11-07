<?php
error_reporting(0);
ini_set('display_errors', 0);
// Mencegah error gc session di hosting gratis
ini_set('session.gc_probability', 0); 

session_start();
include 'koneksi.php';
include 'gdrive_config.php'; // <-- INI KUNCI UTAMANYA: Menggunakan config GDrive yang sudah berhasil
date_default_timezone_set('Asia/Jakarta');

// ==========================================
// PENTING: Tentukan ID Folder GDrive untuk ABK
// ==========================================
$folder_id_abk = '1nQteRc92JF6PdoS3RckI3Iq3Ro_Eu2iS'; 

// --- Keamanan & Autentikasi ---
$allowed_roles = ['kepala', 'admin', 'super_admin', 'pegawai'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}
$current_role = $_SESSION['role'];

// Header harus dipanggil setelah validasi autentikasi agar redirect tidak terkena 'headers already sent'.
include 'include/header.php';

// --- Validasi NIP dari URL ---
if (!isset($_GET['nip']) || empty($_GET['nip'])) {
    echo "<script>alert('NIP pegawai tidak ditemukan atau kosong.'); window.location.href='index_super_admin.php';</script>";
    exit;
}
$nip_url = $_GET['nip'];

// Proteksi jika role pegawai, hanya boleh akses NIP sendiri
if ($current_role === 'pegawai' && (string)$nip_url !== (string)($_SESSION['nip'] ?? '')) {
    echo "<script>alert('Anda tidak memiliki izin untuk mengakses data pegawai lain!'); window.location.href='abk.php?nip=" . $_SESSION['nip'] . "';</script>";
    exit;
}

// --- Ambil Data Pegawai untuk Judul Halaman ---
$nama_pegawai = "Pegawai";
$query_nama_pegawai = "SELECT nama FROM pegawai WHERE nip = ?";
if ($stmt_nama_pegawai = mysqli_prepare($koneksi, $query_nama_pegawai)) {
    mysqli_stmt_bind_param($stmt_nama_pegawai, "s", $nip_url);
    mysqli_stmt_execute($stmt_nama_pegawai);
    $result_nama_pegawai = mysqli_stmt_get_result($stmt_nama_pegawai);
    if ($row_nama_pegawai = mysqli_fetch_assoc($result_nama_pegawai)) {
        $nama_pegawai = $row_nama_pegawai['nama'];
    }
    mysqli_stmt_close($stmt_nama_pegawai);
}

// --- Fungsi untuk menangani Unggahan File ke Google Drive ---
function uploadKeGDrive($fileInputName, $folderId, $nipPrefix) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null; 
    }

    $file_tmp  = $_FILES[$fileInputName]['tmp_name'];
    $file_name = basename($_FILES[$fileInputName]['name']);
    $mime_type = (new finfo(FILEINFO_MIME_TYPE))->file($file_tmp) ?: 'application/octet-stream';
    $file_size = $_FILES[$fileInputName]['size'];

    // Validasi Ekstensi File
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png', 'jpeg'];
    $allowedMimeTypes = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png']
    ];
    
    if (!in_array($file_ext, $allowedExtensions, true) || !isset($allowedMimeTypes[$file_ext]) || !in_array($mime_type, $allowedMimeTypes[$file_ext], true)) {
        error_log("Ekstensi file tidak diizinkan: " . $file_ext);
        return false;
    }

    // Validasi ukuran file (Maksimal 5MB)
    if ($file_size > 5 * 1024 * 1024) {
        error_log("Ukuran file melebihi batas 5MB.");
        return false;
    }

    try {
        $driveService = getDriveService(); 
        
        $fileMetadata = new Google_Service_Drive_DriveFile(array(
            'name' => $nipPrefix . '_ABK_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name),
            'parents' => array($folderId)
        ));
        
        $content = file_get_contents($file_tmp);
        $uploadedFile = $driveService->files->create($fileMetadata, array(
            'data' => $content,
            'mimeType' => $mime_type,
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink'
        ));

        // Buka akses file
        $permission = new Google_Service_Drive_Permission(array(
            'type' => 'anyone',
            'role' => 'reader'
        ));
        $driveService->permissions->create($uploadedFile->id, $permission);

        return $uploadedFile->webViewLink ?: ('https://drive.google.com/file/d/' . $uploadedFile->id . '/view'); 

    } catch (Exception $e) {
        error_log("Error GDrive Upload (ABK): " . $e->getMessage());
        return false; 
    }
}

// --- Handler POST: Tambah/Edit ABK ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['add_abk']) || isset($_POST['edit_abk']))) {
    $id_abk = isset($_POST['id_abk']) && ctype_digit((string)$_POST['id_abk']) ? (int)$_POST['id_abk'] : null;
    $uraian_tugas = trim((string)($_POST['uraian_tugas'] ?? ''));
    $satuan_hasil = trim((string)($_POST['satuan_hasil'] ?? ''));
    $waktu_penyelesaian = trim((string)($_POST['waktu_penyelesaian'] ?? ''));
    $volume_kerja = trim((string)($_POST['volume_kerja'] ?? ''));
    $uraian_tahapan_kerja = trim((string)($_POST['uraian_tahapan_kerja'] ?? ''));
    $kategori_tugas = trim((string)($_POST['kategori_tugas'] ?? ''));
    $peralatan_kerja = trim((string)($_POST['peralatan_kerja'] ?? ''));
    $catatan_norma_waktu = trim((string)($_POST['catatan_norma_waktu'] ?? ''));

    if ($uraian_tugas === '' || $satuan_hasil === '' || $waktu_penyelesaian === '' || $volume_kerja === '') {
        echo "<script>alert('Uraian tugas, satuan hasil, waktu penyelesaian, dan volume kerja wajib diisi.'); window.history.back();</script>";
        exit;
    }
    if (!is_numeric($waktu_penyelesaian) || !is_numeric($volume_kerja) || (float)$waktu_penyelesaian < 0 || (float)$volume_kerja < 0) {
        echo "<script>alert('Waktu penyelesaian dan volume kerja harus berupa angka yang valid.'); window.history.back();</script>";
        exit;
    }

    $file_path = null;

    if ($id_abk) { 
        $query_old_file = "SELECT dokumen_pendukung FROM abk WHERE id_abk = ? AND nip = ?";
        if ($stmt_old_file = mysqli_prepare($koneksi, $query_old_file)) {
            mysqli_stmt_bind_param($stmt_old_file, "is", $id_abk, $nip_url);
            mysqli_stmt_execute($stmt_old_file);
            if ($row_old_file = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_old_file))) {
                $file_path = $row_old_file['dokumen_pendukung'];
            }
            mysqli_stmt_close($stmt_old_file);
        }
    }

    // --- UPLOAD KE GOOGLE DRIVE ---
    if (isset($_FILES['dokumen_pendukung']) && $_FILES['dokumen_pendukung']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadKeGDrive('dokumen_pendukung', $folder_id_abk, $nip_url);
        if ($upload_result !== null) { 
            if ($upload_result === false) { 
                echo "<script>alert('Gagal mengunggah dokumen ke GDrive. Pastikan format file benar dan maksimal 5MB.'); window.history.back();</script>";
                exit;
            } else {
                // TAMBAHAN: Jika ini proses Edit (file lama ada), hapus file lama di GDrive sebelum menimpanya
                if (!empty($file_path)) {
                    deleteFromGDrive($file_path);
                }
                
                $file_path = $upload_result;
            }
        }
    }

    if (isset($_POST['add_abk'])) {
        $query = "INSERT INTO abk (nip, uraian_tugas, satuan_hasil, waktu_penyelesaian, volume_kerja, uraian_tahapan_kerja, kategori_tugas, peralatan_kerja, catatan_norma_waktu, dokumen_pendukung, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        if ($stmt = mysqli_prepare($koneksi, $query)) {
            mysqli_stmt_bind_param($stmt, "ssssssssss", $nip_url, $uraian_tugas, $satuan_hasil, $waktu_penyelesaian, $volume_kerja, $uraian_tahapan_kerja, $kategori_tugas, $peralatan_kerja, $catatan_norma_waktu, $file_path);
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Data ABK berhasil ditambahkan!'); window.location.href='abk.php?nip=" . urlencode($nip_url) . "';</script>";
            } else { echo "<script>alert('Gagal menambahkan data ABK.'); window.history.back();</script>"; }
            mysqli_stmt_close($stmt);
        }
    } elseif (isset($_POST['edit_abk'])) {
        $query = "UPDATE abk SET uraian_tugas=?, satuan_hasil=?, waktu_penyelesaian=?, volume_kerja=?, uraian_tahapan_kerja=?, kategori_tugas=?, peralatan_kerja=?, catatan_norma_waktu=?, dokumen_pendukung=?, updated_at=NOW() WHERE id_abk=? AND nip=?";
        if ($stmt = mysqli_prepare($koneksi, $query)) {
            mysqli_stmt_bind_param($stmt, "sssssssssis", $uraian_tugas, $satuan_hasil, $waktu_penyelesaian, $volume_kerja, $uraian_tahapan_kerja, $kategori_tugas, $peralatan_kerja, $catatan_norma_waktu, $file_path, $id_abk, $nip_url);
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Data ABK berhasil diperbarui!'); window.location.href='abk.php?nip=" . urlencode($nip_url) . "';</script>";
            } else { echo "<script>alert('Gagal memperbarui data ABK.'); window.history.back();</script>"; }
            mysqli_stmt_close($stmt);
        }
    }
    exit;
}

// --- Handler POST: Hapus ABK ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_abk'])) {
    $id_abk_to_delete = isset($_POST['id_abk']) && ctype_digit((string)$_POST['id_abk']) ? (int)$_POST['id_abk'] : null;
    if ($id_abk_to_delete === null) {
        echo "<script>alert('ID data ABK tidak valid.'); window.history.back();</script>";
        exit;
    }

    // 1. Ambil URL file dari database sebelum datanya dihapus
    $file_to_delete = null;
    $query_get_file = "SELECT dokumen_pendukung FROM abk WHERE id_abk = ? AND nip = ?";
    if ($stmt_get_file = mysqli_prepare($koneksi, $query_get_file)) {
        mysqli_stmt_bind_param($stmt_get_file, "is", $id_abk_to_delete, $nip_url);
        mysqli_stmt_execute($stmt_get_file);
        $result_get_file = mysqli_stmt_get_result($stmt_get_file);
        if ($row_file = mysqli_fetch_assoc($result_get_file)) {
            $file_to_delete = $row_file['dokumen_pendukung'];
        }
        mysqli_stmt_close($stmt_get_file);
    }

    // 2. Hapus data dari tabel database
    $query = "DELETE FROM abk WHERE id_abk = ? AND nip = ?";
    if ($stmt = mysqli_prepare($koneksi, $query)) {
        mysqli_stmt_bind_param($stmt, "is", $id_abk_to_delete, $nip_url);
        if (mysqli_stmt_execute($stmt)) {
            
            // 3. Eksekusi Hapus di GDrive
            if (!empty($file_to_delete)) {
                $status_hapus_drive = deleteFromGDrive($file_to_delete);
                
                if ($status_hapus_drive !== true) {
                    echo "<script>alert('Data ABK terhapus, TAPI File di GDrive gagal dihapus! Alasan: " . addslashes($status_hapus_drive) . "'); window.location.href='abk.php?nip=" . urlencode($nip_url) . "';</script>";
                    exit;
                }
            }
            
            echo "<script>alert('Data ABK beserta lampiran file berhasil dihapus penuh!'); window.location.href='abk.php?nip=" . urlencode($nip_url) . "';</script>";
        } else { 
            echo "<script>alert('Gagal menghapus data ABK.');</script>"; 
        }
        mysqli_stmt_close($stmt);
    }
    exit;
}

// --- Ambil Data ABK ---
$query_abk = "SELECT * FROM abk WHERE nip = ? ORDER BY id_abk DESC";
if ($stmt_abk = mysqli_prepare($koneksi, $query_abk)) {
    mysqli_stmt_bind_param($stmt_abk, "s", $nip_url);
    mysqli_stmt_execute($stmt_abk);
    $result_abk = mysqli_stmt_get_result($stmt_abk);
}

// Tentukan halaman kembali yang pasti berdasarkan role
$url_kembali = match($current_role) {
    'super_admin' => 'index_super_admin.php',
    'admin'       => 'index_admin.php',
    'kepala'      => 'index_pengarah_kepala.php',
    'pegawai'     => 'index.php',
    default       => 'login.php'
};
?>

<div id="wrapper">
    <?php include 'include/sidebar.php'; ?>
    
<div id="content-wrapper">
        <div id="topbar">
            <div class="topbar-left">
                <button type="button" id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                
                <!-- Ganti javascript:history.back() dengan $url_kembali -->
                <a href="<?= $url_kembali ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                
                <span class="page-title"><i class="fas fa-tasks" style="color:var(--accent);margin-right:8px;"></i>Analisis Beban Kerja &mdash; <?= htmlspecialchars($nama_pegawai) ?></span>
            </div>
            <div class="topbar-right">
                <a href="logout.php" class="btn-logout confirm-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div id="main-content">
            <div class="card-dark">
                <div class="card-header-dark" style="display:flex;justify-content:space-between;align-items:center;">
                    <h5><i class="fas fa-list-check header-icon"></i> Daftar Analisis Beban Kerja &mdash; <span style="color:var(--text-muted);font-weight:400;"><?= htmlspecialchars($nip_url) ?></span></h5>
                    <?php if (in_array($current_role, $allowed_roles, true)): ?>
                    <button type="button" class="btn-primary-dark" style="padding:8px 16px;" data-toggle="modal" data-target="#addAbkModal">
                        <i class="fas fa-plus"></i> Tambah Uraian Tugas
                    </button>
                    <?php endif; ?>
                </div>
                <div style="overflow-x:auto;">
                    <table class="table-dark-custom" id="tableAbk">
                        <thead>
                            <tr>
                                <th width="5%">No.</th>
                                <th width="30%">Uraian Tugas Pokok</th>
                                <th width="15%">Satuan Hasil</th>
                                <th width="10%" style="text-align:center;">Waktu (Menit)</th>
                                <th width="10%" style="text-align:center;">Volume Kerja</th>
                                <th width="15%" style="text-align:center; color: var(--accent-light);">Total Menit</th>
                                <th style="text-align:center;" width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $grand_total_menit = 0; // Deklarasi variabel total keseluruhan

                            if (isset($result_abk) && mysqli_num_rows($result_abk) > 0) {
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result_abk)) {
                                    $id_abk = $row['id_abk'] ?? '';
                                    $uraian_tugas = $row['uraian_tugas'] ?? '';
                                    $satuan_hasil = $row['satuan_hasil'] ?? '-';
                                    $waktu_penyelesaian = (float)($row['waktu_penyelesaian'] ?? 0);
                                    $volume_kerja = (float)($row['volume_kerja'] ?? 0);
                                    
                                    // Hitung total menit per baris
                                    $total_menit_baris = $waktu_penyelesaian * $volume_kerja;
                                    // Tambahkan ke grand total
                                    $grand_total_menit += $total_menit_baris;

                                    $uraian_tahapan_kerja = $row['uraian_tahapan_kerja'] ?? '-';
                                    $kategori_tugas = $row['kategori_tugas'] ?? '-';
                                    $peralatan_kerja = $row['peralatan_kerja'] ?? '-';
                                    $catatan_norma_waktu = $row['catatan_norma_waktu'] ?? '-';
                                    $dokumen_pendukung = $row['dokumen_pendukung'] ?? '';
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($uraian_tugas) ?></td>
                                <td><?= htmlspecialchars($satuan_hasil) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($waktu_penyelesaian) ?></td>
                                <td style="text-align:center;"><?= htmlspecialchars($volume_kerja) ?></td>
                                <td style="text-align:center; font-weight:600; color:var(--text-primary);">
                                    <?= number_format($total_menit_baris, 0, ',', '.') ?>
                                </td>
                                <td style="text-align:center;white-space:nowrap;">
                                    <button type="button" class="btn btn-info btn-sm btn-detail-abk"
                                        data-tugas="<?= htmlspecialchars($uraian_tugas, ENT_QUOTES) ?>"
                                        data-satuan="<?= htmlspecialchars($satuan_hasil, ENT_QUOTES) ?>"
                                        data-waktu="<?= htmlspecialchars($waktu_penyelesaian, ENT_QUOTES) ?>"
                                        data-volume="<?= htmlspecialchars($volume_kerja, ENT_QUOTES) ?>"
                                        data-tahapan="<?= htmlspecialchars($uraian_tahapan_kerja, ENT_QUOTES) ?>"
                                        data-kategori="<?= htmlspecialchars($kategori_tugas, ENT_QUOTES) ?>"
                                        data-peralatan="<?= htmlspecialchars($peralatan_kerja, ENT_QUOTES) ?>"
                                        data-catatan="<?= htmlspecialchars($catatan_norma_waktu, ENT_QUOTES) ?>"
                                        data-file="<?= htmlspecialchars($dokumen_pendukung, ENT_QUOTES) ?>"
                                        title="Detail Lengkap"><i class="fas fa-eye"></i></button>
                                        
                                    <button type="button" class="btn btn-warning btn-sm btn-edit-abk"
                                        data-id="<?= htmlspecialchars($id_abk, ENT_QUOTES) ?>"
                                        data-tugas="<?= htmlspecialchars($uraian_tugas, ENT_QUOTES) ?>"
                                        data-satuan="<?= htmlspecialchars($row['satuan_hasil'] ?? '', ENT_QUOTES) ?>"
                                        data-waktu="<?= htmlspecialchars($waktu_penyelesaian, ENT_QUOTES) ?>"
                                        data-volume="<?= htmlspecialchars($volume_kerja, ENT_QUOTES) ?>"
                                        data-tahapan="<?= htmlspecialchars($row['uraian_tahapan_kerja'] ?? '', ENT_QUOTES) ?>"
                                        data-kategori="<?= htmlspecialchars($row['kategori_tugas'] ?? '', ENT_QUOTES) ?>"
                                        data-peralatan="<?= htmlspecialchars($row['peralatan_kerja'] ?? '', ENT_QUOTES) ?>"
                                        data-catatan="<?= htmlspecialchars($row['catatan_norma_waktu'] ?? '', ENT_QUOTES) ?>"
                                        data-file="<?= htmlspecialchars($dokumen_pendukung, ENT_QUOTES) ?>"
                                        title="Edit"><i class="fas fa-edit"></i></button>
                                        
                                    <form action="" method="POST" onsubmit="return confirm('Hapus data ABK ini?');" style="display:inline;">
                                        <input type="hidden" name="delete_abk" value="1">
                                        <input type="hidden" name="id_abk" value="<?= htmlspecialchars($id_abk) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                                // Update colspan menjadi 7 karena ada tambahan 1 kolom baru
                                echo "<tr><td colspan='7' style='text-align:center;color:var(--text-muted);padding:32px;'><i class='fas fa-inbox' style='font-size:24px;display:block;margin-bottom:8px;'></i>Belum ada data Analisis Beban Kerja.</td></tr>";
                            }
                            if (isset($stmt_abk)) mysqli_stmt_close($stmt_abk);
                            ?>
                        </tbody>
                        <?php if (isset($grand_total_menit) && $grand_total_menit > 0): ?>
                        <tfoot style="background: rgba(255,255,255,0.03); border-top: 2px solid var(--border);">
                            <tr>
                                <td colspan="5" style="text-align:right; font-weight:600; padding-right: 15px; color: var(--text-secondary);">
                                    Total Keseluruhan Waktu Penugasan:
                                </td>
                                <td style="text-align:center; font-weight:700; font-size: 15px; color: var(--accent-light);">
                                    <?= number_format($grand_total_menit, 0, ',', '.') ?> Menit
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div><!-- /#wrapper -->

<!-- ============ MODALS DIPINDAHKAN KELUAR WRAPPER ============ -->
<!-- Letaknya di sini agar layar tercover penuh (termasuk sidebar) & z-index aman -->

<!-- Modal Tambah ABK -->
<div class="modal fade" id="addAbkModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Tambah Analisis Beban Kerja (ABK)</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_abk" value="1">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 border-right border-secondary">
                            <h6 class="font-weight-bold text-primary border-bottom border-secondary pb-2">Informasi Utama Tugas</h6>
                            <div class="form-group">
                                <label>1. Uraian Tugas Pokok:</label>
                                <textarea class="form-control" name="uraian_tugas" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>2. Satuan Hasil / Dokumen:</label>
                                <input type="text" class="form-control" name="satuan_hasil" placeholder="Contoh: Dokumen / Berkas / Laporan" required>
                            </div>
                            <div class="form-group">
                                <label>3. Waktu Penyelesaian (Menit):</label>
                                <input type="number" class="form-control" name="waktu_penyelesaian" required>
                            </div>
                            <div class="form-group">
                                <label>4. Volume Kerja (1 Tahun):</label>
                                <input type="number" class="form-control" name="volume_kerja" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-success border-bottom border-secondary pb-2">Detail Analisis Beban Kerja</h6>
                            <div class="form-group">
                                <label>5. Uraian Tahapan Kerja:</label>
                                <textarea class="form-control" name="uraian_tahapan_kerja" rows="2" placeholder="Memeriksa kelengkapan fisik..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>6. Kategori Tugas:</label>
                                <select name="kategori_tugas" class="form-control">
                                    <option value="">-- Pilih Kategori Tugas --</option>
                                    <option value="Tugas Pokok Utama">Tugas Pokok Utama</option>
                                    <option value="Tugas Tambahan">Tugas Tambahan</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>7. Peralatan & Sistem Kerja:</label>
                                <textarea class="form-control" name="peralatan_kerja" rows="2" placeholder="Contoh: Laptop, Ms Excel, Printer"></textarea>
                            </div>
                            <div class="form-group">
                                <label>8. Alasan / Catatan Norma Waktu:</label>
                                <textarea class="form-control" name="catatan_norma_waktu" rows="2" placeholder="Dibutuhkan norma waktu karena..."></textarea>
                            </div>
                            <div class="form-group p-2 border border-secondary rounded">
                                <label>9. Dokumen Pendukung:</label>
                                <input type="file" class="form-control-file" name="dokumen_pendukung" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                <small class="text-danger">SOP / SK / KAK / TOR / Uraian Jabatan / SKP (Maks. 5MB)</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan ABK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit ABK -->
<div class="modal fade" id="editAbkModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Edit Analisis Beban Kerja (ABK)</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_abk" value="1">
                <input type="hidden" name="id_abk" id="edit_id_abk">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 border-right border-secondary">
                            <h6 class="font-weight-bold text-primary border-bottom border-secondary pb-2">Informasi Utama Tugas</h6>
                            <div class="form-group">
                                <label>1. Uraian Tugas Pokok:</label>
                                <textarea class="form-control" id="edit_uraian_tugas" name="uraian_tugas" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>2. Satuan Hasil / Dokumen:</label>
                                <input type="text" class="form-control" id="edit_satuan_hasil" name="satuan_hasil" required>
                            </div>
                            <div class="form-group">
                                <label>3. Waktu Penyelesaian (Menit):</label>
                                <input type="number" class="form-control" id="edit_waktu_penyelesaian" name="waktu_penyelesaian" required>
                            </div>
                            <div class="form-group">
                                <label>4. Volume Kerja (1 Tahun):</label>
                                <input type="number" class="form-control" id="edit_volume_kerja" name="volume_kerja" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-success border-bottom border-secondary pb-2">Detail Analisis Beban Kerja</h6>
                            <div class="form-group">
                                <label>5. Uraian Tahapan Kerja:</label>
                                <textarea class="form-control" id="edit_uraian_tahapan_kerja" name="uraian_tahapan_kerja" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>6. Kategori Tugas:</label>
                                <select name="kategori_tugas" id="edit_kategori_tugas" class="form-control">
                                    <option value="">-- Pilih Kategori Tugas --</option>
                                    <option value="Tugas Pokok Utama">Tugas Pokok Utama</option>
                                    <option value="Tugas Tambahan">Tugas Tambahan</option>
                                    <option value="Tugas Berkala">Tugas Berkala</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>7. Peralatan & Sistem Kerja:</label>
                                <textarea class="form-control" id="edit_peralatan_kerja" name="peralatan_kerja" rows="2"></textarea>
                            </div>
                            <div class="form-group">
                                <label>8. Alasan / Catatan Norma Waktu:</label>
                                <textarea class="form-control" id="edit_catatan_norma_waktu" name="catatan_norma_waktu" rows="2"></textarea>
                            </div>
                            <div class="form-group p-2 border border-secondary rounded">
                                <label>9. Dokumen Pendukung Baru:</label>
                                <input type="file" class="form-control-file" name="dokumen_pendukung" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                <small class="text-muted">Biarkan kosong jika tidak mengubah file.</small>
                                <div id="edit_file_display" class="mt-2 font-weight-bold"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <button class="btn btn-warning font-weight-bold" type="submit">Update ABK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail ABK (View Only) -->
<div class="modal fade" id="detailAbkModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Detail Analisis Beban Kerja</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 border-right border-secondary">
                        <table class="table table-sm table-borderless text-white">
                            <tr><th width="40%">Uraian Tugas Pokok</th><td id="det_tugas"></td></tr>
                            <tr><th>Satuan Hasil</th><td id="det_satuan"></td></tr>
                            <tr><th>Waktu Penyelesaian</th><td id="det_waktu"></td></tr>
                            <tr><th>Volume Kerja (1 Tahun)</th><td id="det_volume"></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless text-white">
                            <tr><th width="40%">Uraian Tahapan Kerja</th><td id="det_tahapan"></td></tr>
                            <tr><th>Kategori Tugas</th><td id="det_kategori"></td></tr>
                            <tr><th>Peralatan & Sistem Kerja</th><td id="det_peralatan"></td></tr>
                            <tr><th>Catatan Norma Waktu</th><td id="det_catatan"></td></tr>
                            <tr><th>Dokumen Pendukung</th><td id="det_file"></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>

<script>
    /* Modal Edit & Detail — runs after jQuery + Bootstrap JS are loaded by footer.php */
    $(document).ready(function() {

        function setSelectValue(elementId, value) {
            var select = document.getElementById(elementId);
            if (!select) return;
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === value) {
                    select.selectedIndex = i;
                    break;
                }
            }
        }

        // === Modal Edit ===
        $('.btn-edit-abk').on('click', function(e) {
            e.preventDefault(); // Mencegah scroll ke bawah
            
            var btn = $(this);
            document.getElementById('edit_id_abk').value               = btn.data('id');
            document.getElementById('edit_uraian_tugas').value          = btn.data('tugas');
            document.getElementById('edit_satuan_hasil').value          = btn.data('satuan');
            document.getElementById('edit_waktu_penyelesaian').value    = btn.data('waktu');
            document.getElementById('edit_volume_kerja').value          = btn.data('volume');
            document.getElementById('edit_uraian_tahapan_kerja').value  = btn.data('tahapan');
            document.getElementById('edit_peralatan_kerja').value       = btn.data('peralatan');
            document.getElementById('edit_catatan_norma_waktu').value   = btn.data('catatan');
            setSelectValue('edit_kategori_tugas', btn.data('kategori'));

            var file = btn.data('file');
            var fileDisplay = document.getElementById('edit_file_display');
            if (file && file !== '') {
                fileDisplay.innerHTML = '<i class="fas fa-file-alt text-info"></i> <a href="' + encodeURI(file) + '" target="_blank" rel="noopener noreferrer" class="text-info">Lihat Dokumen Tersimpan</a>';
            } else {
                fileDisplay.innerHTML = '<span class="text-danger">Belum ada dokumen yang diunggah.</span>';
            }
            $('#editAbkModal').modal('show');
        });

        // === Modal Detail ===
        $('.btn-detail-abk').on('click', function(e) {
            e.preventDefault(); // Mencegah scroll ke bawah
            
            var btn = $(this);
            document.getElementById('det_tugas').innerText    = ': ' + btn.data('tugas');
            document.getElementById('det_satuan').innerText   = ': ' + btn.data('satuan');
            document.getElementById('det_waktu').innerText    = ': ' + btn.data('waktu') + ' Menit';
            document.getElementById('det_volume').innerText   = ': ' + btn.data('volume');
            document.getElementById('det_tahapan').innerText  = ': ' + btn.data('tahapan');
            document.getElementById('det_kategori').innerText = ': ' + btn.data('kategori');
            document.getElementById('det_peralatan').innerText= ': ' + btn.data('peralatan');
            document.getElementById('det_catatan').innerText  = ': ' + btn.data('catatan');

            var file = btn.data('file');
            var fileDetDisplay = document.getElementById('det_file');
            if (file && file !== '') {
                fileDetDisplay.innerHTML = ': <a href="' + encodeURI(file) + '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-info"><i class="fas fa-download"></i> Unduh/Lihat Dokumen</a>';
            } else {
                fileDetDisplay.innerHTML = ': <span class="text-muted">Tidak ada lampiran dokumen</span>';
            }
            $('#detailAbkModal').modal('show');
        });
    });
</script>