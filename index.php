<?php
error_reporting(0); ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');

session_start(); 
include 'koneksi.php'; 

if (!isset($_SESSION['role'])) { header("Location: login.php"); exit; }

include 'include/header.php';

$role = $_SESSION['role'];
$nip_session = $_SESSION['nip'] ?? '';

if (isset($_GET['nip'])) {
    $nip = $_GET['nip'];
    // Proteksi: pegawai hanya bisa lihat profil sendiri
    if ($role === 'pegawai' && $nip !== $nip_session) {
        header("Location: index.php"); exit;
    }
} elseif ($nip_session) {
    $nip = $nip_session;
} else {
    echo "<script>alert('Sesi tidak valid.');window.location.href='login.php';</script>"; exit;
}

$back_url = match($role) {
    'admin'       => 'index_admin.php',
    'kepala'      => 'index_pengarah_kepala.php',
    'super_admin' => 'index_super_admin.php',
    default       => null,
};
$can_edit = ($role === 'super_admin');

// Ambil data pegawai + status aktif hari ini
$stmt_p = mysqli_prepare($koneksi,
    "SELECT p.*, rs.status, rs.keterangan, rs.no_spt, rs.tanggal_mulai, rs.tanggal_selesai, rs.file, rs.set_by
     FROM pegawai p
     LEFT JOIN (
         SELECT * FROM riwayat_status
         WHERE nip = ?
         AND (
             CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai
             OR (NOT EXISTS (
                 SELECT 1 FROM riwayat_status WHERE nip = ? AND CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai
             ) AND tanggal_mulai > CURDATE())
         )
         ORDER BY CASE WHEN CURDATE() BETWEEN tanggal_mulai AND tanggal_selesai THEN 0
                       ELSE DATEDIFF(tanggal_mulai, CURDATE()) END ASC
         LIMIT 1
     ) rs ON p.nip = rs.nip
     WHERE p.nip = ?"
);
mysqli_stmt_bind_param($stmt_p, 'sss', $nip, $nip, $nip);
mysqli_stmt_execute($stmt_p);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_p));
mysqli_stmt_close($stmt_p);

if (!$data) { 
    echo "<script>alert('Data pegawai tidak ditemukan.'); window.location.href='".($back_url ?? 'logout.php')."';</script>"; 
    exit; 
}

// Hitung usia
$age = '-';
if (!empty($data['tanggal_lahir']) && $data['tanggal_lahir'] !== '0000-00-00') {
    $age = (new DateTime())->diff(new DateTime($data['tanggal_lahir']))->y . ' tahun';
}

// Hitung KGB
$sk_kgb_terakhir = '-'; $sk_kgb_berikutnya = '-';
if (!empty($data['tmt_awal']) && $data['tmt_awal'] !== '0000-00-00') {
    $today_dt = new DateTime(); $start = new DateTime($data['tmt_awal']);
    $interval = new DateInterval('P2Y'); $kgb_dates = [];
    while ($start <= $today_dt) {
        $clone = clone $start;
        $clone->setDate((int)$clone->format('Y'), (int)$clone->format('m'), 1);
        $kgb_dates[] = clone $clone;
        $start->add($interval);
    }
    if (!empty($kgb_dates)) $sk_kgb_terakhir = end($kgb_dates)->format('01 F Y');
    $start->setDate((int)$start->format('Y'), (int)$start->format('m'), 1);
    $sk_kgb_berikutnya = $start->format('01 F Y');
}

function fmt_tgl($d) {
    if (empty($d) || $d === '0000-00-00') return '-';
    return date('d F Y', strtotime($d));
}
function doc_link($url, $label = 'Lihat Dokumen') {
    if (empty($url)) return '<span style="color:var(--text-muted);">-</span>';
    return '<a href="'.htmlspecialchars($url).'" target="_blank" style="color:var(--accent-light);display:inline-flex;align-items:center;gap:4px;font-size:13px;"><i class="fas fa-file-pdf"></i> '.$label.'</a>';
}
?>
<div id="wrapper">
    <?php include 'include/sidebar.php'; ?>
    <div id="content-wrapper">
        <div id="topbar">
            <div class="topbar-left">
                <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <?php if ($back_url): ?>
                <a href="<?= $back_url ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                <?php endif; ?>
                <span class="page-title"><i class="fas fa-id-card" style="color:var(--accent);margin-right:8px;"></i>Profil Pegawai</span>
            </div>
            <div class="topbar-right">
                <a href="logout.php" class="btn-logout confirm-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div id="main-content">
            <!-- Header Profil -->
            <div class="card-dark" style="margin-bottom:20px;">
                <div class="card-body-dark">
                    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                        <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:white;flex-shrink:0;box-shadow:0 0 24px var(--accent-glow);">
                            <?= strtoupper(substr($data['nama'] ?? 'P', 0, 1)) ?>
                        </div>
                        <div style="flex:1;">
                            <h2 style="font-size:20px;font-weight:700;margin-bottom:4px;"><?= htmlspecialchars($data['nama'] ?? '-') ?></h2>
                            <p style="color:var(--text-muted);font-size:13px;margin-bottom:8px;font-family:monospace;">NIP: <?= htmlspecialchars($data['nip'] ?? '-') ?></p>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <?php if (!empty($data['jabatan'])): ?>
                                <span class="badge-custom badge-primary"><i class="fas fa-briefcase"></i> <?= htmlspecialchars($data['jabatan']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($data['golongan'])): ?>
                                <span class="badge-custom badge-secondary">Gol. <?= htmlspecialchars($data['golongan']) ?></span>
                                <?php endif; ?>
                                <?php
                                $st_now = $data['status'] ?? null;
                                if ($st_now === 'DL'):
                                ?><span class="badge-custom badge-info"><i class="fas fa-plane-departure"></i> Dinas Luar</span>
                                <?php elseif ($st_now === 'Cuti'): ?>
                                <span class="badge-custom badge-warning"><i class="fas fa-umbrella-beach"></i> Cuti</span>
                                <?php else: ?>
                                <span class="badge-custom badge-success"><i class="fas fa-building"></i> Di Kantor</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($can_edit): ?>
                        <a href="edit_password.php?nip=<?= urlencode($nip) ?>" class="btn-warning-dark">
                            <i class="fas fa-user-edit"></i> Edit User
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-2" style="align-items:start;">
                <!-- Kolom Kiri: Data Kepegawaian -->
                <div style="display:flex;flex-direction:column;gap:1px;">
                    <!-- Data Pokok -->
                    <div class="card-dark">
                        <div class="card-header-dark">
                            <h6><i class="fas fa-user header-icon"></i> Data Pokok Pegawai</h6>
                        </div>
                        <div class="card-body-dark">
                            <div class="info-row">
                                <span class="info-label">Status Pegawai</span>
                                <span class="info-value"><?= htmlspecialchars($data['status_pegawai'] ?? '-') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Tanggal Lahir</span>
                                <span class="info-value"><?= fmt_tgl($data['tanggal_lahir'] ?? null) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Usia</span>
                                <span class="info-value"><?= $age ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">TMT Awal</span>
                                <span class="info-value"><?= fmt_tgl($data['tmt_awal'] ?? null) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">TMT Pensiun</span>
                                <span class="info-value">
                                    <?= (!empty($data['tanggal_lahir']) && $data['tanggal_lahir'] !== '0000-00-00')
                                        ? date('d F Y', strtotime('+696 months', strtotime($data['tanggal_lahir']))) : '-' ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Pendidikan Terakhir</span>
                                <span class="info-value"><?= htmlspecialchars($data['pendidikan_terakhir'] ?? '-') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Lulusan</span>
                                <span class="info-value"><?= htmlspecialchars($data['lulusan_pendidikan'] ?? '-') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">SK KGB Terakhir</span>
                                <span class="info-value"><?= $sk_kgb_terakhir !== '-' ? $sk_kgb_terakhir : '-' ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">SK KGB Berikutnya</span>
                                <span class="info-value" style="color:var(--warning);"><?= $sk_kgb_berikutnya !== '-' ? $sk_kgb_berikutnya : '-' ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Dokumen SK -->
                    <div class="card-dark">
                        <div class="card-header-dark">
                            <h6><i class="fas fa-file-alt header-icon"></i> Dokumen SK</h6>
                        </div>
                        <div class="card-body-dark">
                            <div class="info-row">
                                <span class="info-label">SK CPNS</span>
                                <span class="info-value"><?= doc_link($data['sk_cpns'] ?? ($data['sk_cpn'] ?? null)) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">SK PNS</span>
                                <span class="info-value"><?= doc_link($data['sk_pns'] ?? null) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">SK PAK</span>
                                <span class="info-value"><?= doc_link($data['sk_pak'] ?? null) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">SK TUKIN</span>
                                <span class="info-value"><?= doc_link($data['sk_tukin'] ?? null) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">SK Jabatan Terakhir</span>
                                <span class="info-value"><?= doc_link($data['sk_jabatan_terakhir'] ?? null) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">SKP Terakhir</span>
                                <span class="info-value"><?= doc_link($data['skp_terakhir'] ?? null) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Status Kedinasan -->
                    <div class="card-dark">
                        <div class="card-header-dark">
                            <h6><i class="fas fa-clipboard-list header-icon"></i> Status Kedinasan</h6>
                        </div>
                        <div class="card-body-dark">
                            <div class="info-row">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    <?php if (!empty($data['status']) && $data['status'] !== 'DK'): ?>
                                        <?php $st_c = match($data['status']) {'DL'=>'badge-info','Cuti'=>'badge-warning',default=>'badge-secondary'}; ?>
                                        <span class="badge-custom <?= $st_c ?>"><?= htmlspecialchars($data['status']) ?></span>
                                    <?php else: ?>
                                        <span class="badge-custom badge-success"><i class="fas fa-building"></i> Di Kantor</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if (!empty($data['status']) && $data['status'] !== 'DK'): ?>
                            <div class="info-row">
                                <span class="info-label">Keterangan</span>
                                <span class="info-value"><?= htmlspecialchars($data['keterangan'] ?? '-') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">No. SPT</span>
                                <span class="info-value"><?= htmlspecialchars($data['no_spt'] ?? '-') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Tanggal Mulai</span>
                                <span class="info-value"><?= fmt_tgl($data['tanggal_mulai'] ?? null) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Tanggal Selesai</span>
                                <span class="info-value"><?= fmt_tgl($data['tanggal_selesai'] ?? null) ?></span>
                            </div>
                            <?php if (!empty($data['file'])): ?>
                            <div class="info-row">
                                <span class="info-label">File Status</span>
                                <span class="info-value"><?= doc_link($data['file'], 'Lihat File SPT') ?></span>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Link Data Lain -->
                    <div class="card-dark">
                        <div class="card-header-dark">
                            <h6><i class="fas fa-link header-icon"></i> Data Terkait</h6>
                        </div>
                        <div class="card-body-dark">
                            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                <a href="daftar_diklat.php?nip=<?= urlencode($nip) ?>" class="btn-primary-dark">
                                    <i class="fas fa-graduation-cap"></i> Daftar Diklat
                                </a>
                                <a href="daftar_penghargaan.php?nip=<?= urlencode($nip) ?>" class="btn-success-dark">
                                    <i class="fas fa-award"></i> Penghargaan
                                </a>
                                <a href="tna.php?nip=<?= urlencode($nip) ?>" class="btn-info-dark">
                                    <i class="fas fa-chalkboard-teacher"></i> Data TNA
                                </a>
                                <a href="abk.php?nip=<?= urlencode($nip) ?>" class="btn-warning-dark">
                                    <i class="fas fa-briefcase"></i> Data ABK
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Kalender Kedinasan & Rincian Penugasan Kode & SDM 2026 -->
                <?php
                $penListLegacy = [];
                $stmt_pl = mysqli_prepare($koneksi,
                    "SELECT ps.*, k.kode_kegiatan, k.nama_kegiatan, k.warna_hex, k.tim_kerja, k.tempat_lokus
                     FROM penugasan_sdm ps
                     INNER JOIN kegiatan k ON k.id_kegiatan = ps.id_kegiatan
                     WHERE ps.nip = ?
                     ORDER BY ps.tanggal_mulai_tugas ASC, CAST(k.kode_kegiatan AS UNSIGNED) ASC"
                );
                if ($stmt_pl) {
                    mysqli_stmt_bind_param($stmt_pl, 's', $nip);
                    mysqli_stmt_execute($stmt_pl);
                    $res_pl = mysqli_stmt_get_result($stmt_pl);
                    while ($row_pl = mysqli_fetch_assoc($res_pl)) {
                        $penListLegacy[] = $row_pl;
                    }
                    mysqli_stmt_close($stmt_pl);
                }
                $totalKegLegacy  = count($penListLegacy);
                $totalHariLegacy = array_sum(array_map(static fn ($x) => (int) ($x['jumlah_hari'] ?? 0), $penListLegacy));
                ?>
                <div style="display:flex;flex-direction:column;gap:1px;">
                    <div class="card-dark">
                        <div class="card-header-dark">
                            <h6>
                                <i class="fas fa-calendar-alt header-icon"></i> Kalender Kedinasan &amp; Kode SDM 2026
                                <span class="badge-custom badge-primary" style="margin-left:6px;"><?= $totalKegLegacy ?> Kegiatan · <?= $totalHariLegacy ?> Hari</span>
                            </h6>
                        </div>
                        <div class="card-body-dark">
                            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;font-size:11px;color:var(--text-secondary);">
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">
                                    <span style="width:11px;height:11px;border-radius:3px;background:#E5B8B7;border:1px solid rgba(0,0,0,0.3);display:inline-block;"></span> Tim Kerja 1
                                </span>
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">
                                    <span style="width:11px;height:11px;border-radius:3px;background:#FFFF00;border:1px solid rgba(0,0,0,0.3);display:inline-block;"></span> Tim Kerja 2
                                </span>
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">
                                    <span style="width:11px;height:11px;border-radius:3px;background:#92D050;border:1px solid rgba(0,0,0,0.3);display:inline-block;"></span> Tim Kerja 3
                                </span>
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">
                                    <span style="width:11px;height:11px;border-radius:3px;background:#B6DDE8;border:1px solid rgba(0,0,0,0.3);display:inline-block;"></span> Subbag Umum
                                </span>
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:6px;background:var(--bg-secondary);border:1px solid var(--border);">
                                    <span style="width:11px;height:11px;border-radius:3px;background:#f59e0b;display:inline-block;"></span> Cuti
                                </span>
                            </div>
                            <div id="calendar"></div>
                            <div id="calEventDetailBox" style="display:none;margin-top:14px;padding:12px 14px;border-radius:var(--radius-sm);background:var(--bg-secondary);border:1px solid var(--border-accent);font-size:12.5px;"></div>
                        </div>
                    </div>

                    <div class="card-dark">
                        <div class="card-header-dark">
                            <h6><i class="fas fa-tasks header-icon"></i> Rincian Penugasan Kode &amp; SDM 2026</h6>
                            <span style="font-size:11.5px;color:var(--text-muted);">Klik baris untuk lompat ke tanggal di kalender</span>
                        </div>
                        <div class="card-body-dark" style="padding:0;">
                            <?php if (empty($penListLegacy)): ?>
                                <div style="padding:24px;text-align:center;color:var(--text-muted);font-size:13px;">Belum ada penugasan pada Kode dan SDM 2026.</div>
                            <?php else: ?>
                                <div style="max-height:320px;overflow-y:auto;">
                                    <table class="table-dark-custom">
                                        <thead>
                                            <tr>
                                                <th style="width:64px;text-align:center;">Kode</th>
                                                <th>Kegiatan &amp; Lokus</th>
                                                <th>Peran</th>
                                                <th style="text-align:center;">Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($penListLegacy as $ps):
                                                $warna = !empty($ps['warna_hex']) ? $ps['warna_hex'] : '#E5B8B7';
                                                $kab   = $ps['kab_kota_lokus'] ?? '-';
                                                $det   = $ps['detail_lokus'] ?? '-';
                                                $lok   = ($det && $det !== '-' && $det !== $kab) ? "{$kab} — {$det}" : $kab;
                                                $tglM  = $ps['tanggal_mulai_tugas'] ?? '';
                                                $epJson = htmlspecialchars(json_encode([
                                                    'kode'            => (string) ($ps['kode_kegiatan'] ?? '-'),
                                                    'nama_kegiatan'   => $ps['nama_kegiatan'] ?? '-',
                                                    'tim_kerja'       => $ps['tim_kerja'] ?? '-',
                                                    'peran'           => $ps['kategori_peran'] ?? '-',
                                                    'peran_detail'    => $ps['peran_detail'] ?? '',
                                                    'lokus'           => $lok,
                                                    'zona'            => $ps['zona'] ?? 'NON_ZONA',
                                                    'tahap'           => $ps['tahap'] ?? '',
                                                    'tanggal_mulai'   => $ps['tanggal_mulai_tugas'] ?? '-',
                                                    'tanggal_selesai' => $ps['tanggal_selesai_tugas'] ?? '-',
                                                    'jumlah_hari'     => (int) ($ps['jumlah_hari'] ?? 1),
                                                    'warna'           => $warna,
                                                ]), ENT_QUOTES);
                                            ?>
                                            <tr style="cursor:pointer;" onclick="fokusTanggalKalender('<?= htmlspecialchars($tglM, ENT_QUOTES) ?>', <?= $epJson ?>)">
                                                <td style="text-align:center;">
                                                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:26px;padding:0 7px;border-radius:6px;font-weight:800;font-size:12px;color:#0f172a;background:<?= htmlspecialchars($warna) ?>;border:1px solid rgba(0,0,0,0.25);">
                                                        <?= htmlspecialchars($ps['kode_kegiatan']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div style="font-weight:600;color:var(--text-primary);font-size:12.5px;"><?= htmlspecialchars($ps['nama_kegiatan']) ?></div>
                                                    <small style="color:var(--text-muted);"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($lok) ?><?= (!empty($ps['zona']) && $ps['zona'] !== 'NON_ZONA') ? ' · ' . htmlspecialchars($ps['zona']) : '' ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge-custom badge-info"><?= htmlspecialchars($ps['kategori_peran']) ?></span>
                                                    <?php if (!empty($ps['tahap'])): ?>
                                                        <br><small style="color:var(--text-muted);"><?= htmlspecialchars($ps['tahap']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align:center;white-space:nowrap;font-size:11.5px;">
                                                    <span style="color:var(--text-primary);font-weight:600;"><?= htmlspecialchars($ps['tanggal_mulai_tugas'] ?? '-') ?></span><br>
                                                    <small style="color:var(--text-muted);">s.d. <?= htmlspecialchars($ps['tanggal_selesai_tugas'] ?? '-') ?> (<?= (int) ($ps['jumlah_hari'] ?? 1) ?> hr)</small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function escHtmlL(s) {
    return String(s ?? '').replace(/[&<>"']/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
}
function tampilkanDetailKalender(ep) {
    var box = document.getElementById('calEventDetailBox');
    if (!box || !ep) return;
    if (ep.kode) {
        box.innerHTML =
            '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">' +
                '<div>' +
                    '<div style="font-weight:700;color:var(--text-primary);margin-bottom:4px;">' +
                        '<span style="display:inline-flex;align-items:center;justify-content:center;min-width:28px;height:22px;padding:0 6px;border-radius:5px;font-weight:800;font-size:11px;color:#0f172a;background:' + escHtmlL(ep.warna || '#E5B8B7') + ';border:1px solid rgba(0,0,0,0.25);margin-right:8px;">Kode ' + escHtmlL(ep.kode) + '</span>' +
                        escHtmlL(ep.nama_kegiatan) +
                    '</div>' +
                    '<div style="color:var(--text-secondary);font-size:12px;">' +
                        '<strong>Tim Kerja:</strong> ' + escHtmlL(ep.tim_kerja || '-') + ' &nbsp;|&nbsp; ' +
                        '<strong>Peran:</strong> ' + escHtmlL(ep.peran || '-') + (ep.peran_detail && ep.peran_detail !== ep.peran ? ' (' + escHtmlL(ep.peran_detail) + ')' : '') +
                        (ep.tahap ? ' · ' + escHtmlL(ep.tahap) : '') +
                    '</div>' +
                    '<div style="color:var(--text-muted);font-size:12px;margin-top:2px;">' +
                        '<i class="fas fa-map-marker-alt" style="color:var(--accent);"></i> ' + escHtmlL(ep.lokus || '-') +
                        (ep.zona && ep.zona !== 'NON_ZONA' ? ' [' + escHtmlL(ep.zona) + ']' : '') +
                        ' &nbsp;·&nbsp; <i class="fas fa-calendar-day" style="color:var(--accent);"></i> ' +
                        escHtmlL(ep.tanggal_mulai) + ' s.d. ' + escHtmlL(ep.tanggal_selesai) + ' (' + escHtmlL(ep.jumlah_hari) + ' hari)' +
                    '</div>' +
                '</div>' +
                '<button type="button" onclick="document.getElementById(\'calEventDetailBox\').style.display=\'none\'" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:16px;">&times;</button>' +
            '</div>';
    } else {
        box.innerHTML =
            '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                '<div><strong>Status Kedinasan:</strong> ' + escHtmlL(ep.description || '-') + '</div>' +
                '<button type="button" onclick="document.getElementById(\'calEventDetailBox\').style.display=\'none\'" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:16px;">&times;</button>' +
            '</div>';
    }
    box.style.display = 'block';
}
window.onSimpegCalendarEventClick = function(ev) {
    tampilkanDetailKalender(ev.extendedProps || {});
};
function fokusTanggalKalender(tgl, ep) {
    if (tgl && window.simpegCalendar) {
        window.simpegCalendar.gotoDate(tgl);
    }
    if (ep) {
        tampilkanDetailKalender(ep);
    }
}
</script>
<?php include 'include/footer.php'; ?>