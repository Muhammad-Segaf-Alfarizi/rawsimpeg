<?php
error_reporting(0); ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');
session_start();
include 'koneksi.php';
include 'include/penugasan_helper.php';

$current_role = $_SESSION['role'] ?? '';
if (!in_array($current_role, ['super_admin','admin','kepala','pegawai'])) {
    header("Location: login.php"); exit;
}
$is_editor = in_array($current_role, ['super_admin','admin']);
$back_page = match($current_role) {
    'super_admin' => 'index_super_admin.php',
    'admin'       => 'index_admin.php',
    'kepala'      => 'index_pengarah_kepala.php',
    default       => 'index.php',
};
$msg = ''; $msg_type = '';

// ── AJAX ENDPOINTS ──────────────────────────────────────────
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_GET['action'] === 'cari_pegawai') {
        $keyword = $_GET['q'] ?? '';
        $results = cariPegawaiByKeyword($koneksi, $keyword);
        echo json_encode($results);
        exit;
    }

    if ($_GET['action'] === 'detect_zona') {
        $kab = $_GET['kab'] ?? '';
        echo json_encode(['zona' => detectZona($kab)]);
        exit;
    }

    if ($_GET['action'] === 'get_penugasan') {
        $idKeg = (int)($_GET['id_kegiatan'] ?? 0);
        $stmt = mysqli_prepare($koneksi,
            "SELECT p.*, k.kode_kegiatan, k.nama_kegiatan
             FROM penugasan_sdm p JOIN kegiatan k ON k.id_kegiatan=p.id_kegiatan
             WHERE p.id_kegiatan=? ORDER BY p.nama_pegawai");
        mysqli_stmt_bind_param($stmt, 'i', $idKeg);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
        mysqli_stmt_close($stmt);
        echo json_encode($rows);
        exit;
    }

    if ($_GET['action'] === 'cek_bentrok') {
        $nip = $_GET['nip'] ?? '';
        $m   = $_GET['mulai'] ?? '';
        $s   = $_GET['selesai'] ?? '';
        $exc = isset($_GET['exclude']) ? (int)$_GET['exclude'] : null;
        $konflik = cekBentrokJadwal($koneksi, $nip, $m, $s, $exc);
        echo json_encode($konflik);
        exit;
    }
}

// ── POST HANDLERS ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_editor) {
    $action = $_POST['action'] ?? '';

    if ($action === 'simpan_kegiatan') {
        $id = simpanKegiatan($koneksi, $_POST);
        $msg = $id ? "✅ Kegiatan berhasil ditambahkan." : "❌ Gagal menyimpan kegiatan.";
        $msg_type = $id ? 'success' : 'danger';
    }
    elseif ($action === 'update_kegiatan') {
        $ok = updateKegiatan($koneksi, (int)$_POST['id_kegiatan'], $_POST);
        $msg = $ok ? "✅ Kegiatan berhasil diperbarui." : "❌ Gagal memperbarui.";
        $msg_type = $ok ? 'success' : 'danger';
    }
    elseif ($action === 'hapus_kegiatan') {
        $ok = hapusKegiatan($koneksi, (int)$_POST['id_kegiatan']);
        $msg = $ok ? "✅ Kegiatan & penugasan terkait dihapus." : "❌ Gagal menghapus.";
        $msg_type = $ok ? 'success' : 'danger';
    }
    elseif ($action === 'simpan_penugasan') {
        $id = simpanPenugasan($koneksi, $_POST);
        $msg = $id ? "✅ Penugasan SDM berhasil ditambahkan." : "❌ Gagal menyimpan penugasan.";
        $msg_type = $id ? 'success' : 'danger';
    }
    elseif ($action === 'hapus_penugasan') {
        $ok = hapusPenugasan($koneksi, (int)$_POST['id_penugasan']);
        $msg = $ok ? "✅ Penugasan dihapus." : "❌ Gagal menghapus.";
        $msg_type = $ok ? 'success' : 'danger';
    }
    elseif ($action === 'bulk_assign') {
        $cnt = plotSeluruhPegawai(
            $koneksi, (int)$_POST['id_kegiatan'], $_POST['kategori_peran'],
            $_POST['zona'] ?? 'NON_ZONA', $_POST['tanggal_mulai_tugas'] ?? null,
            $_POST['tanggal_selesai_tugas'] ?? null, $_POST['kab_kota_lokus'] ?? null
        );
        $msg = "✅ $cnt pegawai berhasil ditugaskan secara bulk.";
        $msg_type = 'success';
    }

    if ($msg && !isset($_GET['action'])) {
        header("Location: kegiatan_sdm.php?msg=" . urlencode($msg) . "&mt=$msg_type");
        exit;
    }
}

if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_type = $_GET['mt'] ?? 'info'; }

// ── QUERY DATA ──────────────────────────────────────────────
$kegiatanList = [];
$res = mysqli_query($koneksi,
    "SELECT k.*, (SELECT COUNT(*) FROM penugasan_sdm WHERE id_kegiatan=k.id_kegiatan) AS jml_sdm
     FROM kegiatan k ORDER BY CAST(k.kode_kegiatan AS UNSIGNED) ASC");
if ($res) { while ($r = mysqli_fetch_assoc($res)) $kegiatanList[] = $r; }

$totalKeg   = count($kegiatanList);
$totalPen   = (int)(mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM penugasan_sdm"))[0] ?? 0);
$totalDone  = (int)(mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM kegiatan WHERE status_kegiatan='DONE'"))[0] ?? 0);
$totalSched = (int)(mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM kegiatan WHERE status_kegiatan='TERJADWAL'"))[0] ?? 0);

include 'include/header.php';
?>
<style>
.alert-float{position:fixed;top:80px;right:24px;z-index:9999;min-width:300px;animation:slideIn .3s}
@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}
.sdm-list-table{font-size:12px;width:100%;border-collapse:collapse}
.sdm-list-table th{font-size:10px;padding:6px 8px;color:var(--text-muted);text-transform:uppercase;border-bottom:1px solid var(--border)}
.sdm-list-table td{padding:6px 8px;border-bottom:1px solid var(--border);color:var(--text-secondary);vertical-align:middle}
.sdm-list-table tr:hover{background:rgba(255,255,255,.03)}
.autocomplete-list{position:absolute;z-index:2000;background:var(--bg-card);border:1px solid var(--border);border-radius:8px;max-height:200px;overflow-y:auto;width:100%}
.autocomplete-list .ac-item{padding:8px 12px;cursor:pointer;font-size:13px;color:var(--text-secondary)}
.autocomplete-list .ac-item:hover{background:rgba(99,102,241,.15);color:var(--accent-light)}
.conflict-box{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:12px;margin-top:10px;font-size:12px;color:#f87171}
.conflict-box.ok{background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.3);color:#34d399}
</style>
<div id="wrapper">
    <?php include 'include/sidebar.php'; ?>
    <div id="content-wrapper">
        <div id="topbar">
            <div class="topbar-left">
                <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <a href="<?= $back_page ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                <span class="page-title"><i class="fas fa-calendar-alt" style="color:var(--accent);margin-right:8px"></i>Kegiatan & Penugasan SDM</span>
            </div>
            <div class="topbar-right">
                <a href="rekap_penugasan.php" class="btn-info-dark"><i class="fas fa-chart-bar"></i> Rekap</a>
                <a href="logout.php" class="btn-logout confirm-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div id="main-content">

<?php if ($msg): ?>
<div class="alert-float alert alert-<?= htmlspecialchars($msg_type) ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($msg) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="grid grid-4" style="margin-bottom:24px">
    <div class="stat-card"><div class="stat-icon accent"><i class="fas fa-calendar-check"></i></div><div class="stat-text"><div class="stat-value"><?= $totalKeg ?></div><div class="stat-label">Total Kegiatan</div></div></div>
    <div class="stat-card"><div class="stat-icon info"><i class="fas fa-user-check"></i></div><div class="stat-text"><div class="stat-value"><?= $totalPen ?></div><div class="stat-label">Total Penugasan</div></div></div>
    <div class="stat-card"><div class="stat-icon success"><i class="fas fa-check-circle"></i></div><div class="stat-text"><div class="stat-value"><?= $totalDone ?></div><div class="stat-label">Kegiatan Selesai</div></div></div>
    <div class="stat-card"><div class="stat-icon warning"><i class="fas fa-clock"></i></div><div class="stat-text"><div class="stat-value"><?= $totalSched ?></div><div class="stat-label">Terjadwal</div></div></div>
</div>

<!-- Tabel Kegiatan -->
<div class="card-dark">
    <div class="card-header-dark">
        <h5><i class="fas fa-table header-icon"></i> Daftar Kegiatan 2026</h5>
        <?php if ($is_editor): ?>
        <button class="btn-primary-dark" data-toggle="modal" data-target="#modalTambahKegiatan"><i class="fas fa-plus"></i> Tambah Kegiatan</button>
        <?php endif; ?>
    </div>
    <div class="card-body-dark" style="padding:0;overflow-x:auto">
        <table class="table-dark-custom auto-datatable" style="width:100%">
            <thead><tr>
                <th>No</th><th>Kode</th><th>Nama Kegiatan</th><th>Kuota</th><th>Tanggal</th><th>Tempat</th><th>Status</th><th>SDM</th><th>Aksi</th>
            </tr></thead>
            <tbody>
            <?php foreach ($kegiatanList as $i => $kg): ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><strong><?= htmlspecialchars($kg['kode_kegiatan']) ?></strong></td>
                    <td><?= htmlspecialchars($kg['nama_kegiatan']) ?></td>
                    <td style="font-size:11px"><?= htmlspecialchars($kg['kuota_keterangan'] ?? '-') ?></td>
                    <td style="white-space:nowrap;font-size:11px"><?= $kg['tanggal_mulai'] ?> s.d. <?= $kg['tanggal_selesai'] ?></td>
                    <td style="font-size:12px"><?= htmlspecialchars($kg['tempat_lokus'] ?? '-') ?></td>
                    <td>
                        <?php if ($kg['status_kegiatan'] === 'DONE'): ?>
                            <span class="badge-custom badge-success"><i class="fas fa-check"></i> DONE</span>
                        <?php else: ?>
                            <span class="badge-custom badge-warning"><i class="fas fa-clock"></i> Terjadwal</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge-custom badge-primary"><?= $kg['jml_sdm'] ?> orang</span></td>
                    <td style="white-space:nowrap">
                        <button class="btn-info-dark btn-sm-custom btn-detail-sdm"
                            data-id="<?= $kg['id_kegiatan'] ?>"
                            data-kode="<?= htmlspecialchars($kg['kode_kegiatan']) ?>"
                            data-nama="<?= htmlspecialchars($kg['nama_kegiatan']) ?>"
                            data-mulai="<?= $kg['tanggal_mulai'] ?>"
                            data-selesai="<?= $kg['tanggal_selesai'] ?>"
                            data-tempat="<?= htmlspecialchars($kg['tempat_lokus'] ?? '') ?>"
                            data-toggle="modal" data-target="#modalSDM">
                            <i class="fas fa-users"></i> <?= $is_editor ? 'Detail/Plot' : 'Lihat' ?> SDM
                        </button>
                        <?php if ($is_editor): ?>
                        <button class="btn-warning-dark btn-sm-custom btn-edit-keg"
                            data-id="<?= $kg['id_kegiatan'] ?>"
                            data-kode="<?= htmlspecialchars($kg['kode_kegiatan']) ?>"
                            data-nama="<?= htmlspecialchars($kg['nama_kegiatan']) ?>"
                            data-kuota="<?= htmlspecialchars($kg['kuota_keterangan'] ?? '') ?>"
                            data-mulai="<?= $kg['tanggal_mulai'] ?>"
                            data-selesai="<?= $kg['tanggal_selesai'] ?>"
                            data-tempat="<?= htmlspecialchars($kg['tempat_lokus'] ?? '') ?>"
                            data-status="<?= $kg['status_kegiatan'] ?>"
                            data-toggle="modal" data-target="#modalEditKegiatan">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="post" style="display:inline" onsubmit="return confirm('Hapus kegiatan ini beserta semua penugasannya?')">
                            <input type="hidden" name="action" value="hapus_kegiatan">
                            <input type="hidden" name="id_kegiatan" value="<?= $kg['id_kegiatan'] ?>">
                            <button class="btn-danger-dark btn-sm-custom"><i class="fas fa-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

        </div><!-- main-content -->
    </div><!-- content-wrapper -->
</div><!-- wrapper -->

<!-- ═══════ MODAL: Tambah Kegiatan ═══════ -->
<div class="modal fade" id="modalTambahKegiatan" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus-circle"></i> Tambah Kegiatan</h5><button class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form method="post"><input type="hidden" name="action" value="simpan_kegiatan">
    <div class="modal-body">
        <div class="form-group"><label class="form-label-dark">Kode Kegiatan</label><input type="text" name="kode_kegiatan" class="form-control-dark" required></div>
        <div class="form-group"><label class="form-label-dark">Nama Kegiatan</label><input type="text" name="nama_kegiatan" class="form-control-dark" required></div>
        <div class="form-group"><label class="form-label-dark">Kuota / Keterangan</label><textarea name="kuota_keterangan" class="form-control-dark" rows="2"></textarea></div>
        <div class="grid grid-2">
            <div class="form-group"><label class="form-label-dark">Tanggal Mulai</label><input type="date" name="tanggal_mulai" class="form-control-dark"></div>
            <div class="form-group"><label class="form-label-dark">Tanggal Selesai</label><input type="date" name="tanggal_selesai" class="form-control-dark"></div>
        </div>
        <div class="form-group"><label class="form-label-dark">Tempat / Lokus</label><input type="text" name="tempat_lokus" class="form-control-dark"></div>
        <div class="form-group"><label class="form-label-dark">Status</label><select name="status_kegiatan" class="form-control-dark"><option value="TERJADWAL">Terjadwal</option><option value="DONE">DONE</option></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
    </form>
</div></div></div>

<!-- ═══════ MODAL: Edit Kegiatan ═══════ -->
<div class="modal fade" id="modalEditKegiatan" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-edit"></i> Edit Kegiatan</h5><button class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form method="post"><input type="hidden" name="action" value="update_kegiatan"><input type="hidden" name="id_kegiatan" id="editKegId">
    <div class="modal-body">
        <div class="form-group"><label class="form-label-dark">Kode</label><input type="text" name="kode_kegiatan" id="editKode" class="form-control-dark" required></div>
        <div class="form-group"><label class="form-label-dark">Nama</label><input type="text" name="nama_kegiatan" id="editNama" class="form-control-dark" required></div>
        <div class="form-group"><label class="form-label-dark">Kuota</label><textarea name="kuota_keterangan" id="editKuota" class="form-control-dark" rows="2"></textarea></div>
        <div class="grid grid-2">
            <div class="form-group"><label class="form-label-dark">Mulai</label><input type="date" name="tanggal_mulai" id="editMulai" class="form-control-dark"></div>
            <div class="form-group"><label class="form-label-dark">Selesai</label><input type="date" name="tanggal_selesai" id="editSelesai" class="form-control-dark"></div>
        </div>
        <div class="form-group"><label class="form-label-dark">Tempat</label><input type="text" name="tempat_lokus" id="editTempat" class="form-control-dark"></div>
        <div class="form-group"><label class="form-label-dark">Status</label><select name="status_kegiatan" id="editStatus" class="form-control-dark"><option value="TERJADWAL">Terjadwal</option><option value="DONE">DONE</option></select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-warning">Update</button></div>
    </form>
</div></div></div>

<!-- ═══════ MODAL: Detail/Plot SDM (Large) ═══════ -->
<div class="modal fade" id="modalSDM" tabindex="-1">
<div class="modal-dialog modal-lg" style="max-width:900px"><div class="modal-content">
    <div class="modal-header bg-info">
        <h5 class="modal-title"><i class="fas fa-users"></i> SDM Kegiatan: <span id="sdmKegNama"></span></h5>
        <button class="close" data-dismiss="modal"><span>&times;</span></button>
    </div>
    <div class="modal-body" style="max-height:70vh;overflow-y:auto">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
            Kode: <strong id="sdmKegKode"></strong> | Tanggal: <span id="sdmKegTgl"></span> | Tempat: <span id="sdmKegTempat"></span>
        </div>
        <div id="sdmTableArea"><p style="text-align:center;color:var(--text-muted)"><i class="fas fa-spinner fa-spin"></i> Memuat...</p></div>

        <?php if ($is_editor): ?>
        <hr class="divider">
        <div class="section-title">Tambah Penugasan SDM</div>
        <form method="post" id="formTambahSDM">
            <input type="hidden" name="action" value="simpan_penugasan">
            <input type="hidden" name="id_kegiatan" id="sdmIdKegiatan">
            <input type="hidden" name="nip" id="sdmNip">
            <div class="grid grid-2">
                <div class="form-group" style="position:relative">
                    <label class="form-label-dark">Nama Pegawai / Alias</label>
                    <input type="text" id="sdmNamaInput" name="nama_pegawai" class="form-control-dark" autocomplete="off" required placeholder="Ketik nama atau alias...">
                    <div id="acList" class="autocomplete-list" style="display:none"></div>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Kategori Peran</label>
                    <select name="kategori_peran" id="sdmPeran" class="form-control-dark" required>
                        <option value="Dinas Luar">Dinas Luar</option>
                        <option value="Panitia Hotel">Panitia Hotel</option>
                        <option value="Panitia UPT">Panitia UPT</option>
                        <option value="Fasilitator">Fasilitator</option>
                        <option value="Peserta Hotel">Peserta Hotel</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-2">
                <div class="form-group">
                    <label class="form-label-dark">Kabupaten / Kota</label>
                    <select name="kab_kota_lokus" id="sdmKab" class="form-control-dark">
                        <option value="">-- Pilih --</option>
                        <?php foreach (getAllKabKota() as $kab => $z): ?>
                        <option value="<?= $kab ?>"><?= $kab ?> (<?= $z ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label-dark">Zona (otomatis)</label>
                    <input type="text" name="zona" id="sdmZona" class="form-control-dark" readonly value="NON_ZONA">
                </div>
            </div>
            <div class="grid grid-2">
                <div class="form-group"><label class="form-label-dark">Tgl Mulai Tugas</label><input type="date" name="tanggal_mulai_tugas" id="sdmTglMulai" class="form-control-dark"></div>
                <div class="form-group"><label class="form-label-dark">Tgl Selesai Tugas</label><input type="date" name="tanggal_selesai_tugas" id="sdmTglSelesai" class="form-control-dark"></div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <button type="submit" class="btn-primary-dark"><i class="fas fa-save"></i> Simpan Penugasan</button>
                <button type="button" id="btnCekBentrok" class="btn-warning-dark"><i class="fas fa-exclamation-triangle"></i> Cek Bentrok</button>
                <button type="button" id="btnBulkAssign" class="btn-success-dark"><i class="fas fa-users"></i> Plot Seluruh Pegawai</button>
            </div>
        </form>
        <div id="bentrokResult"></div>
        <?php endif; ?>
    </div>
</div></div></div>

<!-- ═══════ MODAL: Bulk Assign ═══════ -->
<div class="modal fade" id="modalBulk" tabindex="-1">
<div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-success"><h5 class="modal-title"><i class="fas fa-users"></i> Plot Seluruh Pegawai</h5><button class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <form method="post"><input type="hidden" name="action" value="bulk_assign"><input type="hidden" name="id_kegiatan" id="bulkIdKeg">
    <div class="modal-body">
        <p style="font-size:13px;color:var(--text-secondary)">Semua pegawai aktif akan ditugaskan ke kegiatan ini dengan peran dan zona yang sama.</p>
        <div class="form-group"><label class="form-label-dark">Kategori Peran</label>
            <select name="kategori_peran" class="form-control-dark" required>
                <option value="Dinas Luar">Dinas Luar</option><option value="Panitia Hotel">Panitia Hotel</option><option value="Panitia UPT">Panitia UPT</option><option value="Fasilitator">Fasilitator</option><option value="Peserta Hotel">Peserta Hotel</option>
            </select></div>
        <div class="form-group"><label class="form-label-dark">Zona</label>
            <select name="zona" class="form-control-dark"><option value="NON_ZONA">NON_ZONA</option><option value="Z1">Z1</option><option value="Z2">Z2</option><option value="Z3">Z3</option><option value="Z4">Z4</option><option value="K38_ALL">K38_ALL</option></select></div>
        <div class="form-group"><label class="form-label-dark">Kab/Kota</label><input type="text" name="kab_kota_lokus" class="form-control-dark"></div>
        <div class="grid grid-2">
            <div class="form-group"><label class="form-label-dark">Tgl Mulai</label><input type="date" name="tanggal_mulai_tugas" id="bulkTglM" class="form-control-dark"></div>
            <div class="form-group"><label class="form-label-dark">Tgl Selesai</label><input type="date" name="tanggal_selesai_tugas" id="bulkTglS" class="form-control-dark"></div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-success" onclick="return confirm('Yakin plot SELURUH pegawai?')">Plot Semua</button></div>
    </form>
</div></div></div>

<script>
$(function(){
    // Auto-dismiss alert
    setTimeout(function(){ $('.alert-float').fadeOut(); }, 4000);

    // Edit Kegiatan — populate modal
    $(document).on('click','.btn-edit-keg',function(){
        var d = $(this).data();
        $('#editKegId').val(d.id); $('#editKode').val(d.kode); $('#editNama').val(d.nama);
        $('#editKuota').val(d.kuota); $('#editMulai').val(d.mulai); $('#editSelesai').val(d.selesai);
        $('#editTempat').val(d.tempat); $('#editStatus').val(d.status);
    });

    // Detail SDM — load penugasan via AJAX
    $(document).on('click','.btn-detail-sdm',function(){
        var d = $(this).data();
        $('#sdmKegNama').text(d.nama); $('#sdmKegKode').text(d.kode);
        $('#sdmKegTgl').text(d.mulai + ' s.d. ' + d.selesai); $('#sdmKegTempat').text(d.tempat);
        $('#sdmIdKegiatan').val(d.id); $('#bulkIdKeg').val(d.id);
        $('#sdmTglMulai').val(d.mulai); $('#sdmTglSelesai').val(d.selesai);
        $('#bulkTglM').val(d.mulai); $('#bulkTglS').val(d.selesai);
        loadSDMTable(d.id);
    });

    function loadSDMTable(idKeg){
        $('#sdmTableArea').html('<p style="text-align:center;color:var(--text-muted)"><i class="fas fa-spinner fa-spin"></i> Memuat...</p>');
        $.getJSON('kegiatan_sdm.php?action=get_penugasan&id_kegiatan='+idKeg, function(data){
            if(!data.length){ $('#sdmTableArea').html('<p style="color:var(--text-muted)">Belum ada penugasan SDM.</p>'); return; }
            var h='<table class="sdm-list-table"><thead><tr><th>No</th><th>Nama</th><th>Peran</th><th>Kab/Kota</th><th>Zona</th><th>Mulai</th><th>Selesai</th><th>Hari</th><?php if($is_editor): ?><th>Aksi</th><?php endif; ?></tr></thead><tbody>';
            $.each(data,function(i,r){
                h+='<tr><td>'+(i+1)+'</td><td style="font-weight:500;color:var(--text-primary)">'+esc(r.nama_pegawai)+'</td><td><span class="badge-custom badge-secondary">'+esc(r.kategori_peran)+'</span></td><td>'+esc(r.kab_kota_lokus||'-')+'</td><td>'+esc(r.zona)+'</td><td>'+esc(r.tanggal_mulai_tugas||'-')+'</td><td>'+esc(r.tanggal_selesai_tugas||'-')+'</td><td>'+r.jumlah_hari+'</td>';
                <?php if($is_editor): ?>
                h+='<td><form method="post" style="display:inline" onsubmit="return confirm(\'Hapus penugasan ini?\')"><input type="hidden" name="action" value="hapus_penugasan"><input type="hidden" name="id_penugasan" value="'+r.id_penugasan+'"><button class="btn-danger-dark btn-sm-custom"><i class="fas fa-trash"></i></button></form></td>';
                <?php endif; ?>
                h+='</tr>';
            });
            h+='</tbody></table>';
            $('#sdmTableArea').html(h);
        });
    }
    function esc(s){ return $('<span>').text(s||'').html(); }

    // Autocomplete pegawai
    var acTimer;
    $('#sdmNamaInput').on('keyup',function(){
        clearTimeout(acTimer);
        var q=$(this).val();
        if(q.length<2){$('#acList').hide();return;}
        acTimer=setTimeout(function(){
            $.getJSON('kegiatan_sdm.php?action=cari_pegawai&q='+encodeURIComponent(q),function(d){
                if(!d.length){$('#acList').hide();return;}
                var h='';
                $.each(d,function(i,r){h+='<div class="ac-item" data-nip="'+esc(r.nip)+'" data-nama="'+esc(r.nama)+'">'+esc(r.nama)+' <small style="color:var(--text-muted)">('+esc(r.nip)+')</small></div>';});
                $('#acList').html(h).show();
            });
        },300);
    });
    $(document).on('click','.ac-item',function(){
        $('#sdmNamaInput').val($(this).data('nama'));
        $('#sdmNip').val($(this).data('nip'));
        $('#acList').hide();
    });
    $(document).on('click',function(e){ if(!$(e.target).closest('#sdmNamaInput,#acList').length) $('#acList').hide(); });

    // Auto-detect zona from Kab/Kota
    $('#sdmKab').on('change',function(){
        var kab=$(this).val();
        if(!kab){$('#sdmZona').val('NON_ZONA');return;}
        $.getJSON('kegiatan_sdm.php?action=detect_zona&kab='+encodeURIComponent(kab),function(d){ $('#sdmZona').val(d.zona); });
    });

    // Cek Bentrok
    $('#btnCekBentrok').on('click',function(){
        var nip=$('#sdmNip').val(), m=$('#sdmTglMulai').val(), s=$('#sdmTglSelesai').val();
        if(!nip||!m||!s){alert('Isi NIP, tanggal mulai & selesai dulu.');return;}
        $.getJSON('kegiatan_sdm.php?action=cek_bentrok&nip='+nip+'&mulai='+m+'&selesai='+s,function(d){
            if(!d.length){ $('#bentrokResult').html('<div class="conflict-box ok"><i class="fas fa-check-circle"></i> Tidak ada bentrok jadwal.</div>'); }
            else{
                var h='<div class="conflict-box"><i class="fas fa-exclamation-triangle"></i> <strong>Bentrok Terdeteksi:</strong><ul style="margin:8px 0 0 16px">';
                $.each(d,function(i,c){h+='<li>['+c.tipe+'] '+esc(c.detail)+'</li>';});
                h+='</ul></div>';
                $('#bentrokResult').html(h);
            }
        });
    });

    // Bulk assign
    $('#btnBulkAssign').on('click',function(){
        $('#modalBulk').modal('show');
    });
});
</script>
<?php include 'include/footer.php'; ?>

