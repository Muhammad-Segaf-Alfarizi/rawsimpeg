<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
include 'koneksi.php'; 
include 'gdrive_config.php'; // Menghubungkan ke API Google Drive
include 'include/header.php'; 

// Tentukan ID Folder GDrive Anda di sini
$folder_id_pegawai = '1nQteRc92JF6PdoS3RckI3Iq3Ro_Eu2iS';

// --- PENTING: Atur Timezone PHP di sini untuk konsistensi ---
date_default_timezone_set('Asia/Jakarta');

// --- Keamanan & Autentikasi ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

// --- Validasi NIP dari URL ---
if (!isset($_GET['nip']) || empty($_GET['nip'])) {
    echo "<script>alert('NIP pegawai tidak ditemukan atau kosong.'); window.location.href='index_super_admin.php';</script>";
    exit;
}
$nip_url = $_GET['nip'];

$back_url = 'index_super_admin.php'; // Default back URL
// --- PENGAMBILAN DATA UTAMA (Logika Baru yang Sudah Benar) ---

// 1. Ambil data pokok pegawai dari tabel 'pegawai'
$query_pegawai = "SELECT * FROM pegawai WHERE nip = ? LIMIT 1";
if ($stmt_pegawai = mysqli_prepare($koneksi, $query_pegawai)) {
    mysqli_stmt_bind_param($stmt_pegawai, "s", $nip_url);
    mysqli_stmt_execute($stmt_pegawai);
    $result_pegawai = mysqli_stmt_get_result($stmt_pegawai);
    $data = mysqli_fetch_assoc($result_pegawai);
    mysqli_stmt_close($stmt_pegawai);
} else {
    error_log("Error preparing data pegawai query: " . mysqli_error($koneksi));
    echo "<script>alert('Terjadi kesalahan saat mengambil data pegawai.'); window.location.href='index_super_admin.php';</script>";
    exit;
}

// Jika data pegawai tidak ditemukan, hentikan proses
if (!$data) {
    echo "<script>alert('Data pegawai tidak ditemukan.'); window.location.href='index_super_admin.php';</script>";
    exit;
}

// 2. Jadikan Status Hari Ini sebagai data utama
$today_date_wib = date('Y-m-d');
$status_hari_ini = null;
$query_status_hari_ini = "
    SELECT status, keterangan, tanggal_mulai, tanggal_selesai, no_spt, file AS riwayat_file, set_by, id AS riwayat_id
    FROM riwayat_status
    WHERE nip = ?
      AND ? BETWEEN tanggal_mulai AND tanggal_selesai
    LIMIT 1
";

if ($stmt_status_hari_ini = mysqli_prepare($koneksi, $query_status_hari_ini)) {
    mysqli_stmt_bind_param($stmt_status_hari_ini, "ss", $nip_url, $today_date_wib);
    mysqli_stmt_execute($stmt_status_hari_ini);
    $result_status_hari_ini = mysqli_stmt_get_result($stmt_status_hari_ini);
    $status_hari_ini = mysqli_fetch_assoc($result_status_hari_ini);
    mysqli_stmt_close($stmt_status_hari_ini);
} else {
    error_log("Error preparing status hari ini query: " . mysqli_error($koneksi));
}

// 3. Gabungkan data status ke data pegawai atau set default 'DK'
if ($status_hari_ini) {
    // Jika ADA status aktif hari ini, gabungkan datanya ke array $data
    $data = $status_hari_ini + $data;
} else {
    // Jika TIDAK ADA status aktif hari ini, set nilai default 'DK'
    $data['status'] = 'DK';
    $data['keterangan'] = '';
    $data['tanggal_mulai'] = '';
    $data['tanggal_selesai'] = '';
    $data['no_spt'] = '';
    $data['riwayat_file'] = null;
    $data['riwayat_id'] = null;
}

// --- BLOK KODE LAMA YANG PERLU DIHAPUS SUDAH DIHILANGKAN DARI SINI ---


$current_admin_nip = $_SESSION['nip'] ?? '';
$current_admin_role = $_SESSION['role'] ?? '';

// --- Fungsi Pembantu: Format Tanggal ---
function formatTanggal($tanggal) {
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
    $ts = strtotime($tanggal);
    return $ts ? date('d-m-Y', $ts) : '-';
}

// --- Fungsi untuk menangani Unggahan File ke Google Drive ---
function uploadKeGDrive($fileInputName, $folderId) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null; 
    }

    $file_tmp  = $_FILES[$fileInputName]['tmp_name'];
    $file_name = $_FILES[$fileInputName]['name'];
    $mime_type = $_FILES[$fileInputName]['type'];
    $file_size = $_FILES[$fileInputName]['size'];

    // Opsional: Validasi ukuran file (contoh: maksimal 10MB)
    if ($file_size > 10 * 1024 * 1024) {
        error_log("Ukuran file melebihi batas 10MB.");
        return false;
    }

    try {
        $driveService = getDriveService(); // Dipanggil dari gdrive_config.php
        
        $fileMetadata = new Google_Service_Drive_DriveFile(array(
            'name' => time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name), // Nama diperbaiki agar bersih
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
        error_log("Error GDrive Upload: " . $e->getMessage());
        return false; 
    }
}

// --- Handler POST untuk "Cabut Status" ---
// ... (Bagian ini sudah benar, tidak perlu diubah) ...
if (isset($_POST['cabut_status'])) {
    $id_riwayat_to_delete = $_POST['id_riwayat'] ?? null;
    if (empty($id_riwayat_to_delete)) {
        echo "<script>alert('ID Riwayat tidak valid.'); window.location.href='edit_super_admin.php?nip=" . htmlspecialchars($data['nip']) . "';</script>";
        exit;
    }
    $query_get_riwayat = "SELECT nip, set_by, file FROM riwayat_status WHERE id = ?";
    if ($stmt_get_riwayat = mysqli_prepare($koneksi, $query_get_riwayat)) {
        mysqli_stmt_bind_param($stmt_get_riwayat, "i", $id_riwayat_to_delete);
        mysqli_stmt_execute($stmt_get_riwayat);
        $result_get_riwayat = mysqli_stmt_get_result($stmt_get_riwayat);
        $riwayat_detail = mysqli_fetch_assoc($result_get_riwayat);
        mysqli_stmt_close($stmt_get_riwayat);
    } else {
        error_log("Error preparing get riwayat query: " . mysqli_error($koneksi));
        echo "<script>alert('Terjadi kesalahan saat mengambil detail riwayat.'); window.location.href='edit_super_admin.php?nip=" . htmlspecialchars($data['nip']) . "';</script>";
        exit;
    }
    if (!$riwayat_detail) {
        echo "<script>alert('Riwayat status tidak ditemukan.'); window.location.href='edit_super_admin.php?nip=" . htmlspecialchars($data['nip']) . "';</script>";
        exit;
    }
    $riwayat_set_by = $riwayat_detail['set_by'];
    $riwayat_file = $riwayat_detail['file'];
    if ($current_admin_nip === $riwayat_set_by || $current_admin_role === 'super_admin') {
        $delete_query = "DELETE FROM riwayat_status WHERE id = ?";
        if ($stmt_delete = mysqli_prepare($koneksi, $delete_query)) {
            mysqli_stmt_bind_param($stmt_delete, "i", $id_riwayat_to_delete);
            if (mysqli_stmt_execute($stmt_delete)) {  
                echo "<script>
                    alert('Status berhasil dicabut.');
                    window.location.href='edit_super_admin.php?nip=" . htmlspecialchars($data['nip']) . "';
                </script>";
                exit;
            } else {
                error_log("Error executing delete riwayat: " . mysqli_stmt_error($stmt_delete));
                echo "<script>alert('Gagal mencabut status.');</script>";
                exit;
            }
            mysqli_stmt_close($stmt_delete);
        } else {
            error_log("Error preparing delete riwayat query: " . mysqli_error($koneksi));
            echo "<script>alert('Terjadi kesalahan saat menyiapkan proses pencabutan status.');</script>";
            exit;
        }
    } else {
        echo "<script>alert('Anda tidak memiliki izin untuk mencabut status ini.');</script>";
        exit;
    }
}


// --- Handler POST untuk Update Data Pegawai & Tambah Riwayat Status ---
// ... (Bagian ini sudah benar, tidak perlu diubah) ...
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['cabut_status'])) {
    $nama = $_POST['nama'];
    $golongan = $_POST['golongan'];
    $jabatan = $_POST['jabatan'];
    $tgl_lahir = $_POST['tgl_lahir'];
    $tmt_awal = $_POST['tmt_awal'];
    $status_pegawai = $_POST['status_pegawai'];
    $pendidikan_terakhir = $_POST['pendidikan_terakhir'];
    $lulusan_pendidikan = $_POST['lulusan_pendidikan'];
    $status_riwayat = $_POST['status'];
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $keterangan = $_POST['keterangan'];
    $no_spt = $_POST['no_spt'];
    $set_by_riwayat = ($status_riwayat !== 'DK') ? $current_admin_nip : NULL;

    // --- 1. PROSES UPLOAD FILE KE GOOGLE DRIVE ---
    
    // 1. SK CPNS
    $link_sk_cpns = $data['sk_cpns']; 
    if (isset($_FILES['sk_cpns']) && $_FILES['sk_cpns']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadKeGDrive('sk_cpns', $folder_id_pegawai);
        if ($upload_result) {
            if (!empty($link_sk_cpns)) deleteFromGDrive($link_sk_cpns); // Hapus file lama di GDrive
            $link_sk_cpns = $upload_result; 
        } else {
            echo "<script>alert('Gagal mengunggah SK CPNS ke sistem cloud.');</script>";
        }
    }

    // 2. SK PNS
    $link_sk_pns = $data['sk_pns'];
    if (isset($_FILES['sk_pns']) && $_FILES['sk_pns']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadKeGDrive('sk_pns', $folder_id_pegawai);
        if ($upload_result) {
            if (!empty($link_sk_pns)) deleteFromGDrive($link_sk_pns); // Hapus file lama
            $link_sk_pns = $upload_result;
        }
    }

    // 3. SK TUKIN
    $link_sk_tukin = $data['sk_tukin'];
    if (isset($_FILES['sk_tukin']) && $_FILES['sk_tukin']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadKeGDrive('sk_tukin', $folder_id_pegawai);
        if ($upload_result) {
            if (!empty($link_sk_tukin)) deleteFromGDrive($link_sk_tukin); // Hapus file lama
            $link_sk_tukin = $upload_result;
        }
    }

    // 4. SK JABATAN TERAKHIR
    $link_sk_jabatan_terakhir = $data['sk_jabatan_terakhir'];
    if (isset($_FILES['sk_jabatan_terakhir']) && $_FILES['sk_jabatan_terakhir']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadKeGDrive('sk_jabatan_terakhir', $folder_id_pegawai);
        if ($upload_result) {
            if (!empty($link_sk_jabatan_terakhir)) deleteFromGDrive($link_sk_jabatan_terakhir); // Hapus file lama
            $link_sk_jabatan_terakhir = $upload_result;
        }
    }

    // 5. SK PAK
    $link_sk_pak = $data['sk_pak'];
    if (isset($_FILES['sk_pak']) && $_FILES['sk_pak']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadKeGDrive('sk_pak', $folder_id_pegawai);
        if ($upload_result) {
            if (!empty($link_sk_pak)) deleteFromGDrive($link_sk_pak); // Hapus file lama
            $link_sk_pak = $upload_result;
        }
    }

    // 6. SKP TERAKHIR
    $link_skp_terakhir = $data['skp_terakhir'];
    if (isset($_FILES['skp_terakhir']) && $_FILES['skp_terakhir']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadKeGDrive('skp_terakhir', $folder_id_pegawai);
        if ($upload_result) {
            if (!empty($link_skp_terakhir)) deleteFromGDrive($link_skp_terakhir); // Hapus file lama
            $link_skp_terakhir = $upload_result;
        }
    }

    // 7. FILE PENDUKUNG RIWAYAT (CUTI/DL)
    $riwayat_file_link = $data['riwayat_file'];
    if ($status_riwayat !== 'DK' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadKeGDrive('file', $folder_id_pegawai);
        if ($upload_result) {
            if (!empty($riwayat_file_link)) deleteFromGDrive($riwayat_file_link); // Hapus file lama
            $riwayat_file_link = $upload_result;
        }
    } else if ($status_riwayat === 'DK') {
        // Jika diubah jadi Di Kantor, kita tidak menghapus file lama secara paksa 
        // karena history file tersebut seharusnya tetap ada di tabel riwayat_status (rekam jejak)
        $riwayat_file_link = NULL;
    }

    // --- 2. CEK BENTROK TANGGAL CUTI/DL ---
    if (in_array($status_riwayat, ['DL', 'Cuti'])) {
        if (in_array($data['status'], ['DL', 'Cuti']) && $data['set_by'] && $data['set_by'] !== $current_admin_nip) {
            $mulai_lama = $data['tanggal_mulai'];
            $selesai_lama = $data['tanggal_selesai'];
            $bentrok = (($tanggal_mulai <= $selesai_lama && $tanggal_selesai >= $mulai_lama));
            if ($bentrok) {
                $set_by_nip = $data['set_by'];
                $query_admin_nama = "SELECT nama FROM user WHERE nip = ?";
                if ($stmt_admin_nama = mysqli_prepare($koneksi, $query_admin_nama)) {
                    mysqli_stmt_bind_param($stmt_admin_nama, "s", $set_by_nip);
                    mysqli_stmt_execute($stmt_admin_nama);
                    $result_admin_nama = mysqli_stmt_get_result($stmt_admin_nama);
                    $admin_row = mysqli_fetch_assoc($result_admin_nama);
                    $admin_nama = $admin_row['nama'] ?? 'Admin tidak diketahui';
                    mysqli_stmt_close($stmt_admin_nama);
                } else {
                    $admin_nama = 'Admin tidak diketahui (Error)';
                    error_log("Error preparing admin name query: " . mysqli_error($koneksi));
                }
                $tanggal_kunci = formatTanggal($mulai_lama) . " - " . formatTanggal($selesai_lama);
                echo "<script>
                    alert('Tanggal bentrok dengan data yang telah dikunci oleh: " . htmlspecialchars($admin_nama) . "\\nPeriode penguncian: " . htmlspecialchars($tanggal_kunci) . "');
                    window.location.href='edit_super_admin.php?nip=" . htmlspecialchars($data['nip']) . "';
                </script>";
                exit;
            }
        }
    }

    // --- 3. UPDATE DATA PEGAWAI (Database) ---
    $query_update_pegawai = "UPDATE pegawai SET
        nama=?,
        status_pegawai=?,
        golongan=?,
        jabatan=?,
        tanggal_lahir=?,
        tmt_awal=?,
        pendidikan_terakhir=?, 
        lulusan_pendidikan=?,
        sk_cpns=?,
        sk_pns=?,
        sk_tukin=?,
        sk_jabatan_terakhir=?,
        sk_pak=?,
        skp_terakhir=?,
        updated_at=NOW()
        WHERE nip=?";

    if ($stmt_update_pegawai = mysqli_prepare($koneksi, $query_update_pegawai)) {
        mysqli_stmt_bind_param(
            $stmt_update_pegawai,
            "sssssssssssssss", // 15 Parameter String
            $nama, $status_pegawai, $golongan, $jabatan, $tgl_lahir, $tmt_awal,
            $pendidikan_terakhir, $lulusan_pendidikan, 
            $link_sk_cpns, $link_sk_pns, $link_sk_tukin, $link_sk_jabatan_terakhir, 
            $link_sk_pak, $link_skp_terakhir,
            $data['nip'] 
        );
        $update_pegawai_success = mysqli_stmt_execute($stmt_update_pegawai);
        mysqli_stmt_close($stmt_update_pegawai);
        if (!$update_pegawai_success) {
            error_log("Error executing pegawai update: " . mysqli_stmt_error($stmt_update_pegawai));
            echo "<script>alert('Gagal memperbarui data pegawai: " . htmlspecialchars(mysqli_stmt_error($stmt_update_pegawai)) . "');</script>";
            exit;
        }
    } else {
        error_log("Error preparing pegawai update query: " . mysqli_error($koneksi));
        echo "<script>alert('Terjadi kesalahan saat menyiapkan pembaruan data pegawai.');</script>";
        exit;
    }

    // --- 4. INSERT DATA RIWAYAT STATUS JIKA PERLU ---
    if ($status_riwayat !== 'DK') {
        $query_check_riwayat = "SELECT id FROM riwayat_status WHERE nip = ? AND status = ? AND tanggal_mulai = ? AND tanggal_selesai = ?";
        if ($stmt_check_riwayat = mysqli_prepare($koneksi, $query_check_riwayat)) {
            mysqli_stmt_bind_param($stmt_check_riwayat, "ssss", $data['nip'], $status_riwayat, $tanggal_mulai, $tanggal_selesai);
            mysqli_stmt_execute($stmt_check_riwayat);
            mysqli_stmt_store_result($stmt_check_riwayat);
            $riwayat_exists = mysqli_stmt_num_rows($stmt_check_riwayat) > 0;
            mysqli_stmt_close($stmt_check_riwayat);
            if (!$riwayat_exists) {
                $query_insert_riwayat = "INSERT INTO riwayat_status (
                    nip, status, keterangan, tanggal_mulai, tanggal_selesai,
                    no_spt, file, set_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                if ($stmt_insert_riwayat = mysqli_prepare($koneksi, $query_insert_riwayat)) {
                    mysqli_stmt_bind_param($stmt_insert_riwayat, "ssssssss",
                        $data['nip'], $status_riwayat, $keterangan, $tanggal_mulai, $tanggal_selesai,
                        $no_spt, $riwayat_file_link, $set_by_riwayat 
                    );
                    $insert_riwayat_success = mysqli_stmt_execute($stmt_insert_riwayat);
                    mysqli_stmt_close($stmt_insert_riwayat);
                    if (!$insert_riwayat_success) {
                        error_log("Error executing riwayat insert: " . mysqli_stmt_error($stmt_insert_riwayat));
                        echo "<script>alert('Gagal menyimpan riwayat status: " . htmlspecialchars(mysqli_stmt_error($stmt_insert_riwayat)) . "');</script>";
                        exit;
                    }
                } else {
                    error_log("Error preparing riwayat insert query: " . mysqli_error($koneksi));
                    echo "<script>alert('Terjadi kesalahan saat menyiapkan penambahan riwayat status.');</script>";
                    exit;
                }
            }
        } else {
            error_log("Error preparing check riwayat query: " . mysqli_error($koneksi));
            echo "<script>alert('Terjadi kesalahan saat memeriksa riwayat status.');</script>";
            exit;
        }
    }
    echo "<script>alert('Data berhasil diperbarui.'); window.location.href='index_super_admin.php';</script>";
    exit;
}
?>
<div id="wrapper">
    <?php include 'include/sidebar.php'; ?>
    <div id="content-wrapper">
        <div id="topbar">
            <div class="topbar-left">
                <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <a href="<?= $back_url ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                <span class="page-title"><i class="fas fa-user-edit" style="color:var(--accent);margin-right:8px;"></i>Edit Data Pegawai &mdash; <?= htmlspecialchars($data['nama'] ?? '') ?></span>
            </div>
            <div class="topbar-right">
                <a href="logout.php" class="btn-logout confirm-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div id="main-content">
            <div class="grid grid-2" style="align-items:start;">
                <!-- Form Edit Pegawai -->
                <div class="card-dark">
                    <div class="card-header-dark">
                        <h5><i class="fas fa-user-cog header-icon"></i> Edit Data Pegawai</h5>
                    </div>
                    <div class="card-body-dark">
                        <form method="POST" enctype="multipart/form-data">
                            <p class="section-title">Data Identitas</p>
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label-dark">Nama</label>
                                    <input type="text" name="nama" class="form-control-dark" value="<?= htmlspecialchars($data['nama']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label-dark">NIP</label>
                                    <input type="text" class="form-control-dark" value="<?= htmlspecialchars($data['nip']) ?>" readonly>
                                </div>
                            </div>
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label-dark">Status Pegawai</label>
                                    <select name="status_pegawai" class="form-control-dark" required>
                                        <option value="">-- Pilih Status --</option>
                                        <option value="PNS"   <?= ($data['status_pegawai'] ?? '') === 'PNS'   ? 'selected' : '' ?>>PNS</option>
                                        <option value="PPPK"  <?= ($data['status_pegawai'] ?? '') === 'PPPK'  ? 'selected' : '' ?>>PPPK</option>
                                        <option value="PPPNP" <?= ($data['status_pegawai'] ?? '') === 'PPPNP' ? 'selected' : '' ?>>PPPNP</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label-dark">Golongan</label>
                                    <input type="text" name="golongan" class="form-control-dark" value="<?= htmlspecialchars($data['golongan'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label-dark">Jabatan</label>
                                <input type="text" name="jabatan" class="form-control-dark" value="<?= htmlspecialchars($data['jabatan'] ?? '') ?>" required>
                            </div>
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label-dark">Tanggal Lahir</label>
                                    <input type="date" name="tgl_lahir" class="form-control-dark" value="<?= htmlspecialchars($data['tanggal_lahir'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label-dark">TMT Awal</label>
                                    <input type="date" name="tmt_awal" class="form-control-dark" value="<?= htmlspecialchars($data['tmt_awal'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label-dark">Pendidikan Terakhir</label>
                                    <select name="pendidikan_terakhir" class="form-control-dark">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach (['SMP/ Sederajat','SMA/ Sederajat','D1','D2','D3','S1/ Sederajat','S2','S3'] as $p): ?>
                                        <option value="<?= $p ?>" <?= ($data['pendidikan_terakhir'] ?? '') === $p ? 'selected' : '' ?>><?= $p ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label-dark">Lulusan Pendidikan</label>
                                    <input type="text" name="lulusan_pendidikan" class="form-control-dark" value="<?= htmlspecialchars($data['lulusan_pendidikan'] ?? '') ?>">
                                </div>
                            </div>

                            <hr class="divider">
                            <p class="section-title">Dokumen SK (kosongkan jika tidak diubah)</p>
                            <?php
                            $doc_fields_sa = [
                                'sk_cpns'             => ['label'=>'SK CPNS',             'key'=>'sk_cpns'],
                                'sk_pns'              => ['label'=>'SK PNS',              'key'=>'sk_pns'],
                                'sk_tukin'            => ['label'=>'SK Tukin',            'key'=>'sk_tukin'],
                                'sk_jabatan_terakhir' => ['label'=>'SK Jabatan Terakhir', 'key'=>'sk_jabatan_terakhir'],
                                'sk_pak'              => ['label'=>'SK PAK',              'key'=>'sk_pak'],
                                'skp_terakhir'        => ['label'=>'SKP Terakhir',        'key'=>'skp_terakhir'],
                            ];
                            $sa_keys = array_keys($doc_fields_sa);
                            for ($di = 0; $di < count($sa_keys); $di += 2):
                                $k1 = $sa_keys[$di]; $k2 = $sa_keys[$di+1] ?? null;
                            ?>
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label-dark"><?= $doc_fields_sa[$k1]['label'] ?></label>
                                    <input type="file" name="<?= $k1 ?>" class="form-control-dark" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="padding:8px;">
                                    <?php if (!empty($data[$doc_fields_sa[$k1]['key']])): ?>
                                    <small style="color:var(--text-muted);font-size:11px;">Saat ini: <a href="<?= htmlspecialchars($data[$doc_fields_sa[$k1]['key']]) ?>" target="_blank" style="color:var(--accent-light);">Lihat File</a></small>
                                    <?php endif; ?>
                                </div>
                                <?php if ($k2): ?>
                                <div class="form-group">
                                    <label class="form-label-dark"><?= $doc_fields_sa[$k2]['label'] ?></label>
                                    <input type="file" name="<?= $k2 ?>" class="form-control-dark" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="padding:8px;">
                                    <?php if (!empty($data[$doc_fields_sa[$k2]['key']])): ?>
                                    <small style="color:var(--text-muted);font-size:11px;">Saat ini: <a href="<?= htmlspecialchars($data[$doc_fields_sa[$k2]['key']]) ?>" target="_blank" style="color:var(--accent-light);">Lihat File</a></small>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endfor; ?>

                            <hr class="divider">
                            <p class="section-title">Status Kedinasan</p>
                            <div class="form-group">
                                <label class="form-label-dark">Status</label>
                                <select name="status" id="statusSelect" class="form-control-dark">
                                    <option value="DK"   <?= ($data['status'] ?? '') === 'DK'   ? 'selected' : '' ?>>DK - Di Kantor</option>
                                    <option value="DL"   <?= ($data['status'] ?? '') === 'DL'   ? 'selected' : '' ?>>DL - Dinas Luar</option>
                                    <option value="Cuti" <?= ($data['status'] ?? '') === 'Cuti' ? 'selected' : '' ?>>Cuti</option>
                                </select>
                            </div>
                            <div id="extraFields" style="display:<?= in_array(($data['status'] ?? ''), ['DL', 'Cuti']) ? 'block' : 'none' ?>;">
                                <div class="grid grid-2">
                                    <div class="form-group">
                                        <label class="form-label-dark">Keterangan</label>
                                        <input type="text" name="keterangan" class="form-control-dark" value="<?= htmlspecialchars($data['keterangan'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label-dark">No. SPT</label>
                                        <input type="text" name="no_spt" class="form-control-dark" value="<?= htmlspecialchars($data['no_spt'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="grid grid-2">
                                    <div class="form-group">
                                        <label class="form-label-dark">Tanggal Mulai</label>
                                        <input type="date" name="tanggal_mulai" class="form-control-dark" value="<?= htmlspecialchars($data['tanggal_mulai'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label-dark">Tanggal Selesai</label>
                                        <input type="date" name="tanggal_selesai" class="form-control-dark" value="<?= htmlspecialchars($data['tanggal_selesai'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label-dark">Upload File Pendukung (SPT/Cuti)</label>
                                    <input type="file" name="file" class="form-control-dark" accept=".pdf,.jpg,.jpeg,.png" style="padding:8px;">
                                    <?php if (!empty($data['riwayat_file'])): ?>
                                    <small style="color:var(--text-muted);font-size:11px;">Saat ini: <a href="<?= htmlspecialchars($data['riwayat_file']) ?>" target="_blank" style="color:var(--accent-light);">Lihat File</a></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <hr class="divider">
                            <button type="submit" class="btn-primary-dark">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Riwayat Status -->
                <div class="card-dark">
                    <div class="card-header-dark">
                        <h5><i class="fas fa-history header-icon"></i> Riwayat Status Dinas/Cuti</h5>
                    </div>
                    <div class="card-body-dark" style="padding:0;">
                        <div style="overflow-x:auto;max-height:600px;overflow-y:auto;">
                            <table class="table-dark-custom">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Tgl Mulai</th>
                                        <th>Tgl Selesai</th>
                                        <th>Ditetapkan Oleh</th>
                                        <th>File</th>
                                        <th style="text-align:center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query_all_riwayat = "SELECT rs.*, u.nama AS admin_nama FROM riwayat_status rs LEFT JOIN user u ON rs.set_by = u.nip WHERE rs.nip = ? ORDER BY rs.tanggal_mulai DESC";
                                    if ($stmt_all_riwayat = mysqli_prepare($koneksi, $query_all_riwayat)) {
                                        mysqli_stmt_bind_param($stmt_all_riwayat, "s", $data['nip']);
                                        mysqli_stmt_execute($stmt_all_riwayat);
                                        $result_all_riwayat = mysqli_stmt_get_result($stmt_all_riwayat);
                                        if (mysqli_num_rows($result_all_riwayat) > 0):
                                            while ($row_riwayat = mysqli_fetch_assoc($result_all_riwayat)):
                                                $st_b = match($row_riwayat['status']) {
                                                    'DL'   => '<span class="badge-custom badge-info">DL</span>',
                                                    'Cuti' => '<span class="badge-custom badge-warning">Cuti</span>',
                                                    default=> '<span class="badge-custom badge-secondary">'.$row_riwayat['status'].'</span>',
                                                };
                                    ?>
                                    <tr>
                                        <td><?= $st_b ?></td>
                                        <td style="font-size:12px;white-space:nowrap;"><?= formatTanggal($row_riwayat['tanggal_mulai']) ?></td>
                                        <td style="font-size:12px;white-space:nowrap;"><?= formatTanggal($row_riwayat['tanggal_selesai']) ?></td>
                                        <td style="font-size:12px;"><?= htmlspecialchars($row_riwayat['admin_nama'] ?? $row_riwayat['set_by'] ?? '-') ?></td>
                                        <td><?php if (!empty($row_riwayat['file'])): ?><a href="<?= htmlspecialchars($row_riwayat['file']) ?>" target="_blank" style="color:var(--accent-light);font-size:12px;"><i class="fas fa-file"></i></a><?php else: ?><span style="color:var(--text-muted);">-</span><?php endif; ?></td>
                                        <td style="text-align:center;">
                                            <?php $can_cabut = ($current_admin_nip === ($row_riwayat['set_by'] ?? '') || $current_admin_role === 'super_admin'); ?>
                                            <?php if ($can_cabut): ?>
                                            <form method="post" onsubmit="return confirm('Cabut status ini?');" style="display:inline;">
                                                <input type="hidden" name="cabut_status" value="1">
                                                <input type="hidden" name="id_riwayat" value="<?= htmlspecialchars($row_riwayat['id']) ?>">
                                                <button type="submit" class="btn-danger-dark btn-sm-custom"><i class="fas fa-times"></i> Cabut</button>
                                            </form>
                                            <?php else: ?><span style="color:var(--text-muted);font-size:11px;">—</span><?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:32px;"><i class="fas fa-inbox" style="font-size:24px;margin-bottom:8px;"></i><br>Tidak ada riwayat status.</td></tr>
                                    <?php endif; mysqli_stmt_close($stmt_all_riwayat); } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'include/footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusSelect = document.getElementById('statusSelect');
        const extraFields = document.getElementById('extraFields');
        function toggleExtraFields() {
            extraFields.style.display = (statusSelect.value === 'DL' || statusSelect.value === 'Cuti') ? 'block' : 'none';
        }
        if (statusSelect) statusSelect.addEventListener('change', toggleExtraFields);
    });
</script>
