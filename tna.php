<?php
error_reporting(E_ALL);        
ini_set('display_errors', 100);   
ini_set('session.gc_probability', 0); 

session_start();
include 'koneksi.php';
include 'gdrive_config.php';

// Header dipindah ke bawah untuk mencegah 'headers already sent' jika ada redirect
// include 'include/header.php'; 

date_default_timezone_set('Asia/Jakarta');

// ==========================================
// PENTING: Tentukan ID Folder GDrive untuk TNA
// ==========================================
$folder_id_tna = '1nQteRc92JF6PdoS3RckI3Iq3Ro_Eu2iS'; 

// --- Keamanan & Autentikasi Role ---
$allowed_roles = ['kepala', 'admin', 'super_admin', 'pegawai'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}
$current_role = $_SESSION['role'];

// --- Validasi NIP dari URL ---
if (!isset($_GET['nip']) || empty($_GET['nip'])) {
    echo "<script>alert('NIP pegawai tidak ditemukan atau kosong.'); window.location.href='index.php';</script>";
    exit;
}
$nip_url = $_GET['nip'];

// Proteksi jika role pegawai, hanya boleh akses NIP sendiri
if ($current_role === 'pegawai' && $nip_url !== $_SESSION['nip']) {
    echo "<script>alert('Anda tidak memiliki izin untuk mengakses data pegawai lain!'); window.location.href='tna.php?nip=" . $_SESSION['nip'] . "';</script>";
    exit;
}

// SEKARANG aman untuk include header
include 'include/header.php';

// --- Ambil Data Pegawai untuk Judul ---
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
    $file_name = $_FILES[$fileInputName]['name'];
    $mime_type = $_FILES[$fileInputName]['type'];
    $file_size = $_FILES[$fileInputName]['size'];

    // Validasi Ekstensi File
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png', 'jpeg'];
    
    if (!in_array($file_ext, $allowedExtensions)) {
        error_log("Ekstensi file tidak diizinkan: " . $file_ext);
        return false;
    }

    // Validasi ukuran file (Maksimal 5MB)
    if ($file_size > 5 * 1024 * 1024) {
        error_log("Ukuran file melebihi batas 5MB.");
        return false;
    }

    try {
        $driveService = getDriveService(); // Dipanggil dari gdrive_config.php
        
        $fileMetadata = new Google_Service_Drive_DriveFile(array(
            'name' => $nipPrefix . '_TNA_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name),
            'parents' => array($folderId)
        ));
        
        $content = file_get_contents($file_tmp);
        $uploadedFile = $driveService->files->create($fileMetadata, array(
            'data' => $content,
            'mimeType' => $mime_type,
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink'
        ));

        // Buka akses file agar bisa dilihat lewat link
        $permission = new Google_Service_Drive_Permission(array(
            'type' => 'anyone',
            'role' => 'reader'
        ));
        $driveService->permissions->create($uploadedFile->id, $permission);

        return $uploadedFile->webViewLink; 

    } catch (Exception $e) {
        error_log("Error GDrive Upload (TNA): " . $e->getMessage());
        return false; 
    }
}


// =================================================================================
// 1. HANDLER POST: PERSETUJUAN OLEH SUPER ADMIN / ADMIN
// =================================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['proses_persetujuan'])) {
    if ($current_role === 'super_admin' || $current_role === 'admin') {
        $id_tna_proses = $_POST['id_tna'];
        $aksi_proses = $_POST['aksi_proses']; // 'Disetujui' atau 'Ditolak'
        
        $query_proses = "UPDATE tna SET status_persetujuan = ?, updated_at = NOW() WHERE id_tna = ? AND nip = ?";
        if ($stmt_proses = mysqli_prepare($koneksi, $query_proses)) {
            mysqli_stmt_bind_param($stmt_proses, "sis", $aksi_proses, $id_tna_proses, $nip_url);
            mysqli_stmt_execute($stmt_proses);
            mysqli_stmt_close($stmt_proses);
            echo "<script>alert('Status usulan TNA berhasil diubah menjadi: $aksi_proses'); window.location.href='tna.php?nip=" . urlencode($nip_url) . "';</script>";
            exit;
        }
    }
}

// =================================================================================
// 2. HANDLER POST: TAMBAH & EDIT TNA
// =================================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['add_tna']) || isset($_POST['edit_tna']))) {
    $id_tna = $_POST['id_tna'] ?? null;
    $kompetensi_dibutuhkan = $_POST['kompetensi_dibutuhkan'];
    $kondisi_saat_ini = $_POST['kondisi_saat_ini'];
    $usulan_pelatihan = $_POST['usulan_pelatihan'];
    $prioritas = $_POST['prioritas'];
    $status_pelaksanaan = $_POST['status_pelaksanaan'];
    
    $materi_pelatihan = $_POST['materi_pelatihan'];
    $indikator_keberhasilan = $_POST['indikator_keberhasilan'];
    $kategori_kesenjangan = $_POST['kategori_kesenjangan'];
    $metode_pelaksanaan = $_POST['metode_pelaksanaan'];
    $penyelenggara = $_POST['penyelenggara'];
    $jadwal_pelaksanaan = $_POST['jadwal_pelaksanaan'];
    $evaluasi_pasca = $_POST['evaluasi_pasca'];
    $catatan_rekomendasi = $_POST['catatan_rekomendasi'];

    // LOGIKA PERSETUJUAN: Jika Pegawai, otomatis Menunggu. Jika Admin, otomatis Disetujui.
    $status_persetujuan = ($current_role === 'pegawai') ? 'Menunggu Persetujuan' : 'Disetujui';

    // Ambil file lama jika ini adalah proses Edit
    $file_path = null;
    if ($id_tna) { 
        $query_old_file = "SELECT dokumen_pendukung FROM tna WHERE id_tna = ? AND nip = ?";
        if ($stmt_old_file = mysqli_prepare($koneksi, $query_old_file)) {
            mysqli_stmt_bind_param($stmt_old_file, "is", $id_tna, $nip_url);
            mysqli_stmt_execute($stmt_old_file);
            if ($row_old_file = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_old_file))) {
                $file_path = $row_old_file['dokumen_pendukung'];
            }
            mysqli_stmt_close($stmt_old_file);
        }
    }

    // --- UPLOAD KE GOOGLE DRIVE ---
    if (isset($_FILES['dokumen_pendukung']) && $_FILES['dokumen_pendukung']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadKeGDrive('dokumen_pendukung', $folder_id_tna, $nip_url);
        
        if ($upload_result !== null) { 
            if ($upload_result === false) { 
                echo "<script>alert('Gagal mengunggah dokumen ke GDrive. Pastikan format file benar dan maksimal 5MB.'); window.history.back();</script>";
                exit;
            } else {
                // Jika ini proses Edit (file lama ada), hapus file lama di GDrive sebelum menimpanya
                if (!empty($file_path)) {
                    deleteFromGDrive($file_path);
                }
                
                // Berhasil upload ke drive, simpan URL file yang baru
                $file_path = $upload_result;
            }
        }
    }

    // Simpan ke Database
    if (isset($_POST['add_tna'])) {
        $query = "INSERT INTO tna (nip, kompetensi_dibutuhkan, kondisi_saat_ini, usulan_pelatihan, prioritas, status_pelaksanaan, status_persetujuan, materi_pelatihan, indikator_keberhasilan, kategori_kesenjangan, metode_pelaksanaan, penyelenggara, jadwal_pelaksanaan, evaluasi_pasca, catatan_rekomendasi, dokumen_pendukung, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        if ($stmt = mysqli_prepare($koneksi, $query)) {
            mysqli_stmt_bind_param($stmt, "ssssssssssssssss", $nip_url, $kompetensi_dibutuhkan, $kondisi_saat_ini, $usulan_pelatihan, $prioritas, $status_pelaksanaan, $status_persetujuan, $materi_pelatihan, $indikator_keberhasilan, $kategori_kesenjangan, $metode_pelaksanaan, $penyelenggara, $jadwal_pelaksanaan, $evaluasi_pasca, $catatan_rekomendasi, $file_path);
            if (mysqli_stmt_execute($stmt)) {
                $msg = ($current_role === 'pegawai') ? 'Data TNA berhasil diajukan dan menunggu persetujuan.' : 'Data TNA berhasil ditambahkan!';
                echo "<script>alert('$msg'); window.location.href='tna.php?nip=" . urlencode($nip_url) . "';</script>";
            } else { echo "<script>alert('Gagal menambahkan data TNA.');</script>"; }
            mysqli_stmt_close($stmt);
        }
    } elseif (isset($_POST['edit_tna'])) {
        $query = "UPDATE tna SET kompetensi_dibutuhkan=?, kondisi_saat_ini=?, usulan_pelatihan=?, prioritas=?, status_pelaksanaan=?, status_persetujuan=?, materi_pelatihan=?, indikator_keberhasilan=?, kategori_kesenjangan=?, metode_pelaksanaan=?, penyelenggara=?, jadwal_pelaksanaan=?, evaluasi_pasca=?, catatan_rekomendasi=?, dokumen_pendukung=?, updated_at=NOW() WHERE id_tna=? AND nip=?";
        if ($stmt = mysqli_prepare($koneksi, $query)) {
            mysqli_stmt_bind_param($stmt, "sssssssssssssssis", $kompetensi_dibutuhkan, $kondisi_saat_ini, $usulan_pelatihan, $prioritas, $status_pelaksanaan, $status_persetujuan, $materi_pelatihan, $indikator_keberhasilan, $kategori_kesenjangan, $metode_pelaksanaan, $penyelenggara, $jadwal_pelaksanaan, $evaluasi_pasca, $catatan_rekomendasi, $file_path, $id_tna, $nip_url);
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Data TNA berhasil diperbarui!'); window.location.href='tna.php?nip=" . urlencode($nip_url) . "';</script>";
            } else { echo "<script>alert('Gagal memperbarui data TNA.');</script>"; }
            mysqli_stmt_close($stmt);
        }
    }
    exit;
}

// =================================================================================
// 3. HANDLER POST: HAPUS TNA
// =================================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_tna'])) {
    $id_tna_to_delete = $_POST['id_tna'] ?? null;
    
    // 1. Ambil URL file dari database sebelum datanya dihapus
    $file_to_delete = null;
    $query_get_file = "SELECT dokumen_pendukung FROM tna WHERE id_tna = ? AND nip = ?";
    if ($stmt_get_file = mysqli_prepare($koneksi, $query_get_file)) {
        mysqli_stmt_bind_param($stmt_get_file, "is", $id_tna_to_delete, $nip_url);
        mysqli_stmt_execute($stmt_get_file);
        $result_get_file = mysqli_stmt_get_result($stmt_get_file);
        if ($row_file = mysqli_fetch_assoc($result_get_file)) {
            $file_to_delete = $row_file['dokumen_pendukung'];
        }
        mysqli_stmt_close($stmt_get_file);
    }
    
    // 2. Hapus data dari tabel database
    $query = "DELETE FROM tna WHERE id_tna = ? AND nip = ?";
    if ($stmt = mysqli_prepare($koneksi, $query)) {
        mysqli_stmt_bind_param($stmt, "is", $id_tna_to_delete, $nip_url);
        if (mysqli_stmt_execute($stmt)) {
            
            // 3. Eksekusi Hapus di GDrive
            if (!empty($file_to_delete)) {
                $status_hapus_drive = deleteFromGDrive($file_to_delete);
                
                if ($status_hapus_drive !== true) {
                    // Jika gagal, tampilkan pesan error dari Google Drive
                    echo "<script>alert('Data TNA terhapus, TAPI File di GDrive gagal dihapus! Alasan: " . addslashes($status_hapus_drive) . "'); window.location.href='tna.php?nip=" . urlencode($nip_url) . "';</script>";
                    exit;
                }
            }
            
            // Jika sukses DB dan Drive
            echo "<script>alert('Data TNA beserta lampiran file berhasil dihapus penuh!'); window.location.href='tna.php?nip=" . urlencode($nip_url) . "';</script>";
        } else { 
            echo "<script>alert('Gagal menghapus data TNA.');</script>"; 
        }
        mysqli_stmt_close($stmt);
    }
    exit;
}

// --- Ambil Data TNA untuk Ditampilkan ---
$query_tna = "SELECT * FROM tna WHERE nip = ? ORDER BY id_tna DESC";
if ($stmt_tna = mysqli_prepare($koneksi, $query_tna)) {
    mysqli_stmt_bind_param($stmt_tna, "s", $nip_url);
    mysqli_stmt_execute($stmt_tna);
    $result_tna = mysqli_stmt_get_result($stmt_tna);
}

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
                
                <a href="<?= $url_kembali ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                
                <span class="page-title"><i class="fas fa-tasks" style="color:var(--accent);margin-right:8px;"></i>Analisis Beban Kerja &mdash; <?= htmlspecialchars($nama_pegawai) ?></span>
            </div>
            <div class="topbar-right">
                <a href="logout.php" class="btn-logout confirm-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div id="main-content">
            <?php if ($current_role === 'pegawai'): ?>
            <div class="alert-dark alert-info-dark" style="margin-bottom:20px;">
                <i class="fas fa-info-circle"></i> Setiap pengajuan TNA yang Anda tambahkan akan berstatus <strong>Menunggu Persetujuan</strong> dan perlu diverifikasi oleh Tim Kepegawaian.
            </div>
            <?php endif; ?>

            <div class="card-dark">
                <div class="card-header-dark" style="display:flex;justify-content:space-between;align-items:center;">
                    <h5><i class="fas fa-brain header-icon"></i> Daftar Analisis &amp; Usulan Pelatihan &mdash; <span style="color:var(--text-muted);font-weight:400;"><?= htmlspecialchars($nip_url) ?></span></h5>
                    <button class="btn-primary-dark" style="padding:8px 16px;" data-toggle="modal" data-target="#addTnaModal">
                        <i class="fas fa-plus"></i> Tambah Usulan Pelatihan
                    </button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="table-dark-custom">
                        <thead>
                            <tr>
                                <th width="5%">No.</th>
                                <th width="20%">Kompetensi Dibutuhkan</th>
                                <th width="25%">Usulan Pelatihan</th>
                                <th width="10%" style="text-align:center;">Status</th>
                                <th width="10%" style="text-align:center;">Persetujuan</th>
                                <th style="text-align:center;" width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (isset($result_tna) && mysqli_num_rows($result_tna) > 0) {
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result_tna)) {
                                    $id_tna = $row['id_tna'] ?? '';
                                    $kompetensi_dibutuhkan = $row['kompetensi_dibutuhkan'] ?? '';
                                    $kondisi_saat_ini = $row['kondisi_saat_ini'] ?? '-';
                                    $usulan_pelatihan = $row['usulan_pelatihan'] ?? '';
                                    $prioritas = $row['prioritas'] ?? 'Sedang';
                                    $status_pelaksanaan = $row['status_pelaksanaan'] ?? 'Belum';
                                    $status_persetujuan = $row['status_persetujuan'] ?? 'Disetujui';
                                    $materi_pelatihan = $row['materi_pelatihan'] ?? '-';
                                    $indikator_keberhasilan = $row['indikator_keberhasilan'] ?? '-';
                                    $kategori_kesenjangan = $row['kategori_kesenjangan'] ?? '-';
                                    $metode_pelaksanaan = $row['metode_pelaksanaan'] ?? '-';
                                    $penyelenggara = $row['penyelenggara'] ?? '-';
                                    $jadwal_pelaksanaan = $row['jadwal_pelaksanaan'] ?? '-';
                                    $evaluasi_pasca = $row['evaluasi_pasca'] ?? '-';
                                    $catatan_rekomendasi = $row['catatan_rekomendasi'] ?? '-';
                                    $dokumen_pendukung = $row['dokumen_pendukung'] ?? '';

                                    $badge_status = match($status_pelaksanaan) {
                                        'Selesai' => 'badge-success', 'Sedang Berjalan' => 'badge-info', default => 'badge-secondary'
                                    };
                                    $badge_persetujuan = match($status_persetujuan) {
                                        'Disetujui' => 'badge-success', 'Ditolak' => 'badge-danger', default => 'badge-warning text-dark'
                                    };
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($kompetensi_dibutuhkan) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($usulan_pelatihan) ?></strong><br>
                                    <small style="color:var(--text-muted);">Prioritas: <?= htmlspecialchars($prioritas) ?></small>
                                </td>
                                <td style="text-align:center;"><span class="badge <?= $badge_status ?> p-2"><?= htmlspecialchars($status_pelaksanaan) ?></span></td>
                                <td style="text-align:center;"><span class="badge <?= $badge_persetujuan ?> p-2"><?= htmlspecialchars($status_persetujuan) ?></span></td>
                                <td style="text-align:center;white-space:nowrap;">
                                    <?php if ($status_persetujuan === 'Menunggu Persetujuan' && ($current_role === 'super_admin' || $current_role === 'admin')): ?>
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="proses_persetujuan" value="1">
                                        <input type="hidden" name="id_tna" value="<?= htmlspecialchars($id_tna) ?>">
                                        <input type="hidden" name="aksi_proses" value="Disetujui">
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Setujui?');"><i class="fas fa-check"></i></button>
                                    </form>
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="proses_persetujuan" value="1">
                                        <input type="hidden" name="id_tna" value="<?= htmlspecialchars($id_tna) ?>">
                                        <input type="hidden" name="aksi_proses" value="Ditolak">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tolak?');"><i class="fas fa-times"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <button class="btn btn-info btn-sm btn-detail-tna"
                                        data-kompetensi="<?= htmlspecialchars($kompetensi_dibutuhkan, ENT_QUOTES) ?>"
                                        data-kondisi="<?= htmlspecialchars($kondisi_saat_ini, ENT_QUOTES) ?>"
                                        data-usulan="<?= htmlspecialchars($usulan_pelatihan, ENT_QUOTES) ?>"
                                        data-prioritas="<?= htmlspecialchars($prioritas, ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars($status_pelaksanaan, ENT_QUOTES) ?>"
                                        data-persetujuan="<?= htmlspecialchars($status_persetujuan, ENT_QUOTES) ?>"
                                        data-materi="<?= htmlspecialchars($materi_pelatihan, ENT_QUOTES) ?>"
                                        data-indikator="<?= htmlspecialchars($indikator_keberhasilan, ENT_QUOTES) ?>"
                                        data-kategori="<?= htmlspecialchars($kategori_kesenjangan, ENT_QUOTES) ?>"
                                        data-metode="<?= htmlspecialchars($metode_pelaksanaan, ENT_QUOTES) ?>"
                                        data-penyelenggara="<?= htmlspecialchars($penyelenggara, ENT_QUOTES) ?>"
                                        data-jadwal="<?= htmlspecialchars($jadwal_pelaksanaan, ENT_QUOTES) ?>"
                                        data-evaluasi="<?= htmlspecialchars($evaluasi_pasca, ENT_QUOTES) ?>"
                                        data-catatan="<?= htmlspecialchars($catatan_rekomendasi, ENT_QUOTES) ?>"
                                        data-file="<?= htmlspecialchars($dokumen_pendukung, ENT_QUOTES) ?>"
                                        title="Detail"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-warning btn-sm btn-edit-tna text-dark"
                                        data-id="<?= htmlspecialchars($id_tna, ENT_QUOTES) ?>"
                                        data-kompetensi="<?= htmlspecialchars($kompetensi_dibutuhkan, ENT_QUOTES) ?>"
                                        data-kondisi="<?= htmlspecialchars($kondisi_saat_ini, ENT_QUOTES) ?>"
                                        data-usulan="<?= htmlspecialchars($usulan_pelatihan, ENT_QUOTES) ?>"
                                        data-prioritas="<?= htmlspecialchars($prioritas, ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars($status_pelaksanaan, ENT_QUOTES) ?>"
                                        data-materi="<?= htmlspecialchars($materi_pelatihan == '-' ? '' : $materi_pelatihan, ENT_QUOTES) ?>"
                                        data-indikator="<?= htmlspecialchars($indikator_keberhasilan == '-' ? '' : $indikator_keberhasilan, ENT_QUOTES) ?>"
                                        data-kategori="<?= htmlspecialchars($kategori_kesenjangan == '-' ? '' : $kategori_kesenjangan, ENT_QUOTES) ?>"
                                        data-metode="<?= htmlspecialchars($metode_pelaksanaan == '-' ? '' : $metode_pelaksanaan, ENT_QUOTES) ?>"
                                        data-penyelenggara="<?= htmlspecialchars($penyelenggara == '-' ? '' : $penyelenggara, ENT_QUOTES) ?>"
                                        data-jadwal="<?= htmlspecialchars($jadwal_pelaksanaan == '-' ? '' : $jadwal_pelaksanaan, ENT_QUOTES) ?>"
                                        data-evaluasi="<?= htmlspecialchars($evaluasi_pasca == '-' ? '' : $evaluasi_pasca, ENT_QUOTES) ?>"
                                        data-catatan="<?= htmlspecialchars($catatan_rekomendasi == '-' ? '' : $catatan_rekomendasi, ENT_QUOTES) ?>"
                                        data-file="<?= htmlspecialchars($dokumen_pendukung, ENT_QUOTES) ?>"
                                        title="Edit"><i class="fas fa-edit"></i></button>
                                    <form action="" method="POST" onsubmit="return confirm('Hapus data TNA?');" style="display:inline;">
                                        <input type="hidden" name="delete_tna" value="1">
                                        <input type="hidden" name="id_tna" value="<?= htmlspecialchars($id_tna) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center;color:var(--text-muted);padding:32px;'><i class='fas fa-inbox' style='font-size:24px;display:block;margin-bottom:8px;'></i>Belum ada data TNA.</td></tr>";
                            }
                            if (isset($stmt_tna)) mysqli_stmt_close($stmt_tna);
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div><!-- /#wrapper -->

<!-- ========================================================================= -->
<!-- MODAL DIPINDAHKAN KELUAR DARI WRAPPER AGAR TIDAK TERPOTONG OLEH SIDEBAR -->
<!-- ========================================================================= -->

<!-- Modal Tambah TNA -->
<div class="modal fade" id="addTnaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">Tambah Analisis Kebutuhan Pelatihan (TNA)</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_tna" value="1">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 border-right border-secondary">
                            <h6 class="font-weight-bold text-primary border-bottom border-secondary pb-2">Informasi Dasar & Analisis Gap</h6>
                            <div class="form-group"><label>1. Kompetensi yang Dibutuhkan:</label><input type="text" class="form-control" name="kompetensi_dibutuhkan" required></div>
                            <div class="form-group"><label>2. Kondisi Saat Ini (Kesenjangan/Gap):</label><textarea class="form-control" name="kondisi_saat_ini" rows="2" required></textarea></div>
                            <div class="form-group">
                                <label>3. Kategori Kesenjangan Kompetensi:</label>
                                <select name="kategori_kesenjangan" class="form-control">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="Keterampilan Teknis">Keterampilan Teknis</option><option value="Pengetahuan">Pengetahuan</option><option value="Sikap Perilaku">Sikap Perilaku</option>
                                </select>
                            </div>
                            <div class="form-group"><label>4. Usulan Nama Pelatihan/Diklat:</label><input type="text" class="form-control" name="usulan_pelatihan" required></div>
                            <div class="form-group"><label>5. Materi/Rincian Pelatihan:</label><textarea class="form-control" name="materi_pelatihan" rows="2"></textarea></div>
                            <div class="form-group"><label>6. Indikator Keberhasilan / Output:</label><textarea class="form-control" name="indikator_keberhasilan" rows="2"></textarea></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-success border-bottom border-secondary pb-2">Pelaksanaan & Evaluasi</h6>
                            <div class="form-group">
                                <label>7. Metode / Moda Pelaksanaan:</label>
                                <select name="metode_pelaksanaan" class="form-control">
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="Klasikal (Luring)">Klasikal (Luring)</option><option value="Non-Klasikal (Daring)">Non-Klasikal (Daring)</option><option value="Blended Learning">Blended Learning</option><option value="Mentoring">Mentoring</option>
                                </select>
                            </div>
                            <div class="form-group"><label>8. Narasumber / Lemdiklat Penyelenggara:</label><input type="text" class="form-control" name="penyelenggara"></div>
                            <div class="form-group"><label>9. Jadwal Pelaksanaan (Rencana/Aktual):</label><input type="text" class="form-control" name="jadwal_pelaksanaan"></div>
                            <div class="form-group"><label>10. Evaluasi Pasca-Pelatihan:</label><textarea class="form-control" name="evaluasi_pasca" rows="2"></textarea></div>
                            <div class="form-group"><label>11. Catatan / Rekomendasi Pejabat Penilai:</label><textarea class="form-control" name="catatan_rekomendasi" rows="2"></textarea></div>
                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Prioritas:</label>
                                    <select name="prioritas" class="form-control" required>
                                        <option value="Tinggi">Tinggi</option><option value="Sedang" selected>Sedang</option><option value="Rendah">Rendah</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Status Pelaksanaan:</label>
                                    <select name="status_pelaksanaan" class="form-control" required>
                                        <option value="Belum" selected>Belum Dilaksanakan</option><option value="Sedang Berjalan">Sedang Berjalan</option><option value="Selesai">Selesai</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group p-2 border border-secondary rounded">
                                <label>12. Unggah Dokumen Pendukung (Maks 5MB):</label>
                                <input type="file" class="form-control-file" name="dokumen_pendukung" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" type="submit">Simpan Analisis</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit TNA -->
<div class="modal fade" id="editTnaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title font-weight-bold">Edit Analisis Kebutuhan Pelatihan (TNA)</h5>
                <button class="close text-dark" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_tna" value="1">
                <input type="hidden" name="id_tna" id="edit_id_tna">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 border-right border-secondary">
                            <h6 class="font-weight-bold text-primary border-bottom border-secondary pb-2">Informasi Dasar & Analisis Gap</h6>
                            <div class="form-group"><label>1. Kompetensi yang Dibutuhkan:</label><input type="text" class="form-control" id="edit_kompetensi_dibutuhkan" name="kompetensi_dibutuhkan" required></div>
                            <div class="form-group"><label>2. Kondisi Saat Ini (Kesenjangan/Gap):</label><textarea class="form-control" id="edit_kondisi_saat_ini" name="kondisi_saat_ini" rows="2" required></textarea></div>
                            <div class="form-group">
                                <label>3. Kategori Kesenjangan:</label>
                                <select name="kategori_kesenjangan" id="edit_kategori_kesenjangan" class="form-control">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="Keterampilan Teknis">Keterampilan Teknis</option><option value="Pengetahuan">Pengetahuan</option><option value="Sikap Perilaku">Sikap Perilaku</option>
                                </select>
                            </div>
                            <div class="form-group"><label>4. Usulan Nama Pelatihan/Diklat:</label><input type="text" class="form-control" id="edit_usulan_pelatihan" name="usulan_pelatihan" required></div>
                            <div class="form-group"><label>5. Materi/Rincian Pelatihan:</label><textarea class="form-control" id="edit_materi_pelatihan" name="materi_pelatihan" rows="2"></textarea></div>
                            <div class="form-group"><label>6. Indikator Keberhasilan / Output:</label><textarea class="form-control" id="edit_indikator_keberhasilan" name="indikator_keberhasilan" rows="2"></textarea></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold text-success border-bottom border-secondary pb-2">Pelaksanaan & Evaluasi</h6>
                            <div class="form-group">
                                <label>7. Metode / Moda Pelaksanaan:</label>
                                <select name="metode_pelaksanaan" id="edit_metode_pelaksanaan" class="form-control">
                                    <option value="">-- Pilih Metode --</option>
                                    <option value="Klasikal (Luring)">Klasikal (Luring)</option><option value="Non-Klasikal (Daring)">Non-Klasikal (Daring)</option><option value="Blended Learning">Blended Learning</option><option value="Mentoring">Mentoring</option>
                                </select>
                            </div>
                            <div class="form-group"><label>8. Narasumber / Lemdiklat Penyelenggara:</label><input type="text" class="form-control" id="edit_penyelenggara" name="penyelenggara"></div>
                            <div class="form-group"><label>9. Jadwal Pelaksanaan (Rencana/Aktual):</label><input type="text" class="form-control" id="edit_jadwal_pelaksanaan" name="jadwal_pelaksanaan"></div>
                            <div class="form-group"><label>10. Evaluasi Pasca-Pelatihan:</label><textarea class="form-control" id="edit_evaluasi_pasca" name="evaluasi_pasca" rows="2"></textarea></div>
                            <div class="form-group"><label>11. Catatan / Rekomendasi Pejabat Penilai:</label><textarea class="form-control" id="edit_catatan_rekomendasi" name="catatan_rekomendasi" rows="2"></textarea></div>
                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Prioritas:</label>
                                    <select name="prioritas" id="edit_prioritas" class="form-control" required>
                                        <option value="Tinggi">Tinggi</option><option value="Sedang">Sedang</option><option value="Rendah">Rendah</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label>Status Pelaksanaan:</label>
                                    <select name="status_pelaksanaan" id="edit_status_pelaksanaan" class="form-control" required>
                                        <option value="Belum">Belum Dilaksanakan</option><option value="Sedang Berjalan">Sedang Berjalan</option><option value="Selesai">Selesai</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group p-2 border border-secondary rounded">
                                <label>12. Unggah Dokumen Pendukung Baru (Maks 5MB):</label>
                                <input type="file" class="form-control-file" name="dokumen_pendukung" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png">
                                <small class="text-muted">Biarkan kosong jika tidak mengubah file.</small>
                                <div id="edit_file_display" class="mt-2 font-weight-bold text-info"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                    <button class="btn btn-warning font-weight-bold text-dark" type="submit">Update Analisis</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail TNA (View Only) -->
<div class="modal fade" id="detailTnaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold">Detail Lengkap TNA</h5>
                <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 border-right border-secondary">
                        <table class="table table-sm table-borderless text-white">
                            <tr><th width="40%">Kompetensi Dibutuhkan</th><td id="det_kompetensi"></td></tr>
                            <tr><th>Kondisi Saat Ini</th><td id="det_kondisi"></td></tr>
                            <tr><th>Kategori Kesenjangan</th><td id="det_kategori"></td></tr>
                            <tr><th>Usulan Pelatihan</th><td id="det_usulan"></td></tr>
                            <tr><th>Materi/Rincian</th><td id="det_materi"></td></tr>
                            <tr><th>Indikator Keberhasilan</th><td id="det_indikator"></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless text-white">
                            <tr><th width="40%">Metode Pelaksanaan</th><td id="det_metode"></td></tr>
                            <tr><th>Penyelenggara</th><td id="det_penyelenggara"></td></tr>
                            <tr><th>Jadwal Pelaksanaan</th><td id="det_jadwal"></td></tr>
                            <tr><th>Evaluasi Pasca-Pelatihan</th><td id="det_evaluasi"></td></tr>
                            <tr><th>Catatan / Rekomendasi</th><td id="det_catatan"></td></tr>
                            <tr><th>Status Pelaksanaan</th><td><span id="det_prioritas"></span> | <span id="det_status"></span></td></tr>
                            <tr><th>Status Persetujuan</th><td><span id="det_persetujuan" class="badge p-2"></span></td></tr>
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
    document.addEventListener('DOMContentLoaded', function() {
        
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

        // Script untuk tombol Edit (menggunakan jQuery dari footer)
        $('.btn-edit-tna').on('click', function(e) {
            e.preventDefault(); // Mencegah scroll ke bawah saat diklik
            
            document.getElementById('edit_id_tna').value = this.dataset.id;
            document.getElementById('edit_kompetensi_dibutuhkan').value = this.dataset.kompetensi;
            document.getElementById('edit_kondisi_saat_ini').value = this.dataset.kondisi;
            document.getElementById('edit_usulan_pelatihan').value = this.dataset.usulan;
            document.getElementById('edit_materi_pelatihan').value = this.dataset.materi;
            document.getElementById('edit_indikator_keberhasilan').value = this.dataset.indikator;
            document.getElementById('edit_penyelenggara').value = this.dataset.penyelenggara;
            document.getElementById('edit_jadwal_pelaksanaan').value = this.dataset.jadwal;
            document.getElementById('edit_evaluasi_pasca').value = this.dataset.evaluasi;
            document.getElementById('edit_catatan_rekomendasi').value = this.dataset.catatan;
            
            setSelectValue('edit_prioritas', this.dataset.prioritas);
            setSelectValue('edit_status_pelaksanaan', this.dataset.status);
            setSelectValue('edit_kategori_kesenjangan', this.dataset.kategori);
            setSelectValue('edit_metode_pelaksanaan', this.dataset.metode);
            
            var file = this.dataset.file;
            var fileDisplay = document.getElementById('edit_file_display');
            if (file && file !== '') {
                fileDisplay.innerHTML = '<i class="fas fa-file-alt"></i> <a href="' + file + '" target="_blank" class="text-info">Lihat Dokumen Tersimpan</a>';
            } else {
                fileDisplay.innerHTML = '<span class="text-danger">Belum ada dokumen pendukung.</span>';
            }
            $('#editTnaModal').modal('show');
        });

        // Script untuk tombol Detail (menggunakan jQuery dari footer)
        $('.btn-detail-tna').on('click', function(e) {
            e.preventDefault(); // Mencegah scroll ke bawah saat diklik
            
            document.getElementById('det_kompetensi').innerText = ': ' + this.dataset.kompetensi;
            document.getElementById('det_kondisi').innerText = ': ' + this.dataset.kondisi;
            document.getElementById('det_kategori').innerText = ': ' + this.dataset.kategori;
            document.getElementById('det_usulan').innerText = ': ' + this.dataset.usulan;
            document.getElementById('det_materi').innerText = ': ' + this.dataset.materi;
            document.getElementById('det_indikator').innerText = ': ' + this.dataset.indikator;
            document.getElementById('det_metode').innerText = ': ' + this.dataset.metode;
            document.getElementById('det_penyelenggara').innerText = ': ' + this.dataset.penyelenggara;
            document.getElementById('det_jadwal').innerText = ': ' + this.dataset.jadwal;
            document.getElementById('det_evaluasi').innerText = ': ' + this.dataset.evaluasi;
            document.getElementById('det_catatan').innerText = ': ' + this.dataset.catatan;
            
            document.getElementById('det_prioritas').innerHTML = '<span class="badge badge-dark">' + this.dataset.prioritas + '</span>';
            document.getElementById('det_status').innerHTML = '<span class="badge badge-secondary">' + this.dataset.status + '</span>';
            
            var p_status = this.dataset.persetujuan;
            var p_badge = document.getElementById('det_persetujuan');
            p_badge.innerText = p_status;
            p_badge.className = 'badge p-2 ' + (p_status === 'Disetujui' ? 'badge-success' : (p_status === 'Ditolak' ? 'badge-danger' : 'badge-warning text-dark'));

            var file = this.dataset.file;
            var fileDetDisplay = document.getElementById('det_file');
            if (file && file !== '') {
                fileDetDisplay.innerHTML = ': <a href="' + file + '" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-external-link-alt"></i> Buka Dokumen</a>';
            } else {
                fileDetDisplay.innerHTML = ': <span class="text-muted">Tidak ada lampiran</span>';
            }
            $('#detailTnaModal').modal('show');
        });
        
    });
</script>