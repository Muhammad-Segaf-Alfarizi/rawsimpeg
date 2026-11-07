<?php
session_start();
include 'koneksi.php';

// Cek apakah user adalah super admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

// Cek apakah ID tersedia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index_super_admin.php");
    exit;
}

$id = $_GET['id'];

// Ambil NIP berdasarkan ID pegawai
$stmt = mysqli_prepare($koneksi, "SELECT nip FROM pegawai WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "<script>
            alert('Data pegawai tidak ditemukan.');
            window.location.href='index_super_admin.php';
          </script>";
    exit;
}

$nip = $data['nip'];

// Mulai transaksi
mysqli_begin_transaction($koneksi);

try {

    // Hapus dari tabel user
    $stmt_user = mysqli_prepare($koneksi, "DELETE FROM user WHERE nip = ?");
    mysqli_stmt_bind_param($stmt_user, "s", $nip);
    mysqli_stmt_execute($stmt_user);

    // Hapus dari tabel riwayat_status
    $stmt_riwayat = mysqli_prepare($koneksi, "DELETE FROM riwayat_status WHERE nip = ?");
    mysqli_stmt_bind_param($stmt_riwayat, "s", $nip);
    mysqli_stmt_execute($stmt_riwayat);

    // Hapus dari tabel pegawai
    $stmt_pegawai = mysqli_prepare($koneksi, "DELETE FROM pegawai WHERE id = ?");
    mysqli_stmt_bind_param($stmt_pegawai, "i", $id);
    mysqli_stmt_execute($stmt_pegawai);

    // Pastikan data pegawai benar-benar terhapus
    if (mysqli_stmt_affected_rows($stmt_pegawai) > 0) {

        // Simpan perubahan
        mysqli_commit($koneksi);

        echo "<script>
                alert('Data berhasil dihapus.');
                window.location.href='index_super_admin.php';
              </script>";
        exit;

    } else {

        throw new Exception("Data pegawai gagal dihapus.");

    }

} catch (Exception $e) {

    // Batalkan semua perubahan jika terjadi error
    mysqli_rollback($koneksi);

    echo "<script>
            alert('Gagal menghapus data pegawai.');
            window.location.href='index_super_admin.php';
          </script>";
    exit;
}
?>