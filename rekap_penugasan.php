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
$back_page = match($current_role) {
    'super_admin' => 'index_super_admin.php',
    'admin'       => 'index_admin.php',
    'kepala'      => 'index_pengarah_kepala.php',
    default       => 'index.php',
};

// ── AJAX: Rincian pegawai ───────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'get_rincian') {
    header('Content-Type: application/json; charset=utf-8');
    $nama = $_GET['nama'] ?? '';
    $rincian  = getRincianPenugasanPegawai($koneksi, $nama);
    $ringkas  = getRingkasanKodePegawai($koneksi, $nama);
    echo json_encode(['rincian' => $rincian, 'ringkasan' => $ringkas]);
    exit;
}

// ── Export Excel ────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $rekap = getRekapPenugasan($koneksi);
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="rekap_penugasan_sdm_2026.xls"');
    echo '<html><head><meta charset="utf-8"></head><body>';
    echo '<h2>Rekap Penugasan SDM BGTK Prov. Lampung 2026</h2>';
    echo '<table border="1" cellpadding="4" cellspacing="0" style="font-size:11px;border-collapse:collapse">';
    echo '<tr style="background:#4472C4;color:white;font-weight:bold"><td>No</td><td>Nama Pegawai</td><td>Z1 Tgs</td><td>Z1 Hr</td><td>Z2 Tgs</td><td>Z2 Hr</td><td>Z3 Tgs</td><td>Z3 Hr</td><td>Z4 Tgs</td><td>Z4 Hr</td><td>Pan.Hotel Tgs</td><td>Pan.Hotel Hr</td><td>Pan.UPT Tgs</td><td>Pan.UPT Hr</td><td>Fasil Tgs</td><td>Fasil Hr</td><td>Pes.Hotel Tgs</td><td>Pes.Hotel Hr</td><td>Jml Fasil Tgs</td><td>Jml Fasil Hr</td><td>Jml DL+K38 Tgs</td><td>Jml DL+K38 Hr</td><td>Jml Panitia Tgs</td><td>Jml Panitia Hr</td><td>Jml Peserta Tgs</td><td>Jml Peserta Hr</td><td>Total Keg</td><td>Total Hari</td></tr>';
    $no=0; $sums=[];
    foreach ($rekap as $r) {
        $no++;
        echo '<tr><td>'.$no.'</td><td>'.$r['nama_pegawai'].'</td>';
        $cols = ['z1_tgs','z1_hr','z2_tgs','z2_hr','z3_tgs','z3_hr','z4_tgs','z4_hr',
                 'pan_hotel_tgs','pan_hotel_hr','pan_upt_tgs','pan_upt_hr',
                 'fasil_tgs','fasil_hr','pes_hotel_tgs','pes_hotel_hr',
                 'jml_fasil_tgs','jml_fasil_hr',
                 'jml_dl_k38_tgs','jml_dl_k38_hr','jml_panitia_tgs','jml_panitia_hr',
                 'jml_peserta_tgs','jml_peserta_hr','total_kegiatan','total_hari'];
        foreach ($cols as $c) {
            echo '<td>'.((int)$r[$c]).'</td>';
            $sums[$c] = ($sums[$c] ?? 0) + (int)$r[$c];
        }
        echo '</tr>';
    }
    echo '<tr style="background:#FFC000;font-weight:bold"><td colspan="2">TOTAL</td>';
    foreach ($cols as $c) echo '<td>'.($sums[$c] ?? 0).'</td>';
    echo '</tr></table></body></html>';
    exit;
}

// ── Data ────────────────────────────────────────────────────
$rekap   = getRekapPenugasan($koneksi);
$totals  = getTotalKeseluruhan($koneksi);
$jmlKeg  = (int)(mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM kegiatan"))[0] ?? 0);

include 'include/header.php';
?>
<style>
.nav-tabs{border-bottom:1px solid var(--border)}.nav-tabs .nav-link{color:var(--text-muted);border:none;border-bottom:2px solid transparent;padding:10px 20px;font-size:14px;font-weight:500;background:transparent}.nav-tabs .nav-link:hover{color:var(--text-primary);border-bottom-color:var(--accent)}.nav-tabs .nav-link.active{color:var(--accent-light);background:transparent;border-bottom:2px solid var(--accent)}.tab-content{padding-top:20px}
.table-rekap-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch;max-height:75vh}
.table-rekap{font-size:11px;white-space:nowrap;border-collapse:collapse;width:100%}
.table-rekap thead th{position:sticky;top:0;z-index:10;padding:8px 5px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:var(--text-muted);border-bottom:2px solid var(--border);text-align:center;background:var(--bg-secondary)}
.table-rekap tbody td{padding:5px;text-align:center;border-bottom:1px solid var(--border);color:var(--text-secondary);vertical-align:middle}
.table-rekap tbody td:nth-child(2){text-align:left;font-weight:500;color:var(--text-primary);min-width:180px}
.table-rekap tbody tr:hover{background:rgba(255,255,255,.03)}
.table-rekap tfoot td{padding:8px 5px;font-weight:700;color:var(--accent-light);border-top:2px solid var(--accent);text-align:center;background:rgba(99,102,241,.05);position:sticky;bottom:0}
.val-zero{color:var(--text-muted);opacity:.3}
.th-zona{background:rgba(59,130,246,.08)!important}
.th-pan{background:rgba(245,158,11,.08)!important}
.th-fas{background:rgba(16,185,129,.08)!important}
.th-pes{background:rgba(139,92,246,.08)!important}
.th-agg{background:rgba(99,102,241,.08)!important}
.th-tot{background:rgba(239,68,68,.08)!important}
.detail-link{color:var(--accent-light);cursor:pointer;text-decoration:none;font-weight:500}.detail-link:hover{text-decoration:underline;color:var(--accent)}
.ringkasan-badge{display:inline-block;background:rgba(99,102,241,.12);border:1px solid rgba(99,102,241,.25);color:var(--accent-light);padding:2px 8px;border-radius:12px;font-size:11px;margin:2px}
.filter-group{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px}
.filter-group label{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary);cursor:pointer}
.filter-group input[type=checkbox]{accent-color:var(--accent)}
@media print{#sidebar,#topbar,.btn-back,.btn-logout,.no-print,.nav-tabs{display:none!important}#content-wrapper{margin-left:0!important}body{background:#fff!important;color:#000!important}.table-rekap,.table-rekap th,.table-rekap td{color:#000!important;border:1px solid #ccc!important;font-size:8px!important}.stat-card{border:1px solid #ccc!important;background:#fff!important}.stat-value,.stat-label{color:#000!important}.card-dark{border:1px solid #ccc!important;background:#fff!important}}
</style>
<div id="wrapper">
    <?php include 'include/sidebar.php'; ?>
    <div id="content-wrapper">
        <div id="topbar">
            <div class="topbar-left">
                <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <a href="<?= $back_page ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                <span class="page-title"><i class="fas fa-chart-bar" style="color:var(--accent);margin-right:8px"></i>Rekap Penugasan SDM 2026</span>
            </div>
            <div class="topbar-right no-print">
                <a href="kegiatan_sdm.php" class="btn-info-dark"><i class="fas fa-calendar-alt"></i> Kegiatan</a>
                <a href="?action=export_excel" class="btn-success-dark"><i class="fas fa-file-excel"></i> Excel</a>
                <button onclick="window.print()" class="btn-warning-dark"><i class="fas fa-print"></i> Cetak</button>
                <a href="logout.php" class="btn-logout confirm-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div id="main-content">

<!-- Stats -->
<div class="grid grid-4" style="margin-bottom:24px">
    <div class="stat-card"><div class="stat-icon accent"><i class="fas fa-calendar-check"></i></div><div class="stat-text"><div class="stat-value"><?= $jmlKeg ?></div><div class="stat-label">Total Kegiatan</div></div></div>
    <div class="stat-card"><div class="stat-icon info"><i class="fas fa-tasks"></i></div><div class="stat-text"><div class="stat-value"><?= number_format($totals['total_penugasan'] ?? 0) ?></div><div class="stat-label">Total Penugasan</div></div></div>
    <div class="stat-card"><div class="stat-icon warning"><i class="fas fa-clock"></i></div><div class="stat-text"><div class="stat-value"><?= number_format($totals['total_hari'] ?? 0) ?></div><div class="stat-label">Total Hari Tugas</div></div></div>
    <div class="stat-card"><div class="stat-icon success"><i class="fas fa-users"></i></div><div class="stat-text"><div class="stat-value"><?= $totals['jml_pegawai'] ?? 0 ?></div><div class="stat-label">Jumlah SDM</div></div></div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs no-print" id="rekapTab">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tabRekap"><i class="fas fa-table"></i> Tabel Rekap</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabRincian"><i class="fas fa-list-alt"></i> Rincian Jadwal</a></li>
    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tabFilter"><i class="fas fa-filter"></i> Filter & Cari</a></li>
</ul>

<div class="tab-content">
<!-- ═══ TAB 1: Tabel Rekap ═══ -->
<div class="tab-pane fade show active" id="tabRekap">
<div class="card-dark">
<div class="card-header-dark"><h5><i class="fas fa-table header-icon"></i> Rekapitulasi 54 Pegawai</h5></div>
<div class="table-rekap-wrapper">
<table class="table-rekap" id="tblRekap">
<thead><tr>
    <th>No</th><th>Nama Pegawai</th>
    <th class="th-zona">Z1<br>Tgs</th><th class="th-zona">Z1<br>Hr</th>
    <th class="th-zona">Z2<br>Tgs</th><th class="th-zona">Z2<br>Hr</th>
    <th class="th-zona">Z3<br>Tgs</th><th class="th-zona">Z3<br>Hr</th>
    <th class="th-zona">Z4<br>Tgs</th><th class="th-zona">Z4<br>Hr</th>
    <th class="th-pan">Pan.H<br>Tgs</th><th class="th-pan">Pan.H<br>Hr</th>
    <th class="th-pan">Pan.U<br>Tgs</th><th class="th-pan">Pan.U<br>Hr</th>
    <th class="th-fas">Fas<br>Tgs</th><th class="th-fas">Fas<br>Hr</th>
    <th class="th-pes">PesH<br>Tgs</th><th class="th-pes">PesH<br>Hr</th>
    <th class="th-agg">Jml Fas<br>Tgs</th><th class="th-agg">Jml Fas<br>Hr</th>
    <th class="th-agg">DL+K38<br>Tgs</th><th class="th-agg">DL+K38<br>Hr</th>
    <th class="th-agg">Panitia<br>Tgs</th><th class="th-agg">Panitia<br>Hr</th>
    <th class="th-agg">Peserta<br>Tgs</th><th class="th-agg">Peserta<br>Hr</th>
    <th class="th-tot">Total<br>Keg</th><th class="th-tot">Total<br>Hari</th>
    <th class="no-print">Aksi</th>
</tr></thead>
<tbody>
<?php
$cols = ['z1_tgs','z1_hr','z2_tgs','z2_hr','z3_tgs','z3_hr','z4_tgs','z4_hr',
         'pan_hotel_tgs','pan_hotel_hr','pan_upt_tgs','pan_upt_hr',
         'fasil_tgs','fasil_hr','pes_hotel_tgs','pes_hotel_hr',
         'jml_fasil_tgs','jml_fasil_hr',
         'jml_dl_k38_tgs','jml_dl_k38_hr','jml_panitia_tgs','jml_panitia_hr',
         'jml_peserta_tgs','jml_peserta_hr','total_kegiatan','total_hari'];
$sums = array_fill_keys($cols, 0);
foreach ($rekap as $i => $r):
?>
<tr data-nama="<?= htmlspecialchars(mb_strtolower($r['nama_pegawai'])) ?>">
    <td><?= $i+1 ?></td>
    <td><a class="detail-link" data-nama="<?= htmlspecialchars($r['nama_pegawai']) ?>" href="javascript:void(0)"><?= htmlspecialchars($r['nama_pegawai']) ?></a></td>
    <?php foreach ($cols as $c):
        $v = (int)$r[$c];
        $sums[$c] += $v;
        $cls = $v === 0 ? 'val-zero' : '';
    ?>
    <td class="<?= $cls ?>"><?= $v ?></td>
    <?php endforeach; ?>
    <td class="no-print"><button class="btn-info-dark btn-sm-custom btn-detail-peg" data-nama="<?= htmlspecialchars($r['nama_pegawai']) ?>"><i class="fas fa-search"></i></button></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr>
    <td colspan="2"><strong>TOTAL</strong></td>
    <?php foreach ($cols as $c): ?>
    <td><strong><?= $sums[$c] ?></strong></td>
    <?php endforeach; ?>
    <td class="no-print"></td>
</tr></tfoot>
</table>
</div>
</div>
</div>

<!-- ═══ TAB 2: Rincian Jadwal ═══ -->
<div class="tab-pane fade" id="tabRincian">
<div class="card-dark">
<div class="card-header-dark"><h5><i class="fas fa-list-alt header-icon"></i> Rincian Jadwal Pegawai</h5></div>
<div class="card-body-dark">
    <div class="form-group">
        <label class="form-label-dark">Pilih Pegawai</label>
        <select id="selPegawai" class="form-control-dark">
            <option value="">-- Pilih Pegawai --</option>
            <?php foreach ($rekap as $r): ?>
            <option value="<?= htmlspecialchars($r['nama_pegawai']) ?>"><?= htmlspecialchars($r['nama_pegawai']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="rincianArea" style="margin-top:16px"></div>
</div>
</div>
</div>

<!-- ═══ TAB 3: Filter & Cari ═══ -->
<div class="tab-pane fade" id="tabFilter">
<div class="card-dark">
<div class="card-header-dark"><h5><i class="fas fa-filter header-icon"></i> Filter & Pencarian</h5></div>
<div class="card-body-dark">
    <div class="section-title">Pencarian Nama / Alias</div>
    <div class="form-group">
        <input type="text" id="filterNama" class="form-control-dark" placeholder="Ketik nama atau alias pegawai...">
    </div>

    <div class="section-title" style="margin-top:20px">Filter Zona (tampilkan pegawai yang memiliki tugas di zona terpilih)</div>
    <div class="filter-group">
        <label><input type="checkbox" class="filter-zona" value="z1"> Z1 (Kota Bandar Lampung)</label>
        <label><input type="checkbox" class="filter-zona" value="z2"> Z2 (Lam.Sel, Lam.Teng, Pringsewu, Pesawaran, Metro)</label>
        <label><input type="checkbox" class="filter-zona" value="z3"> Z3 (Lam.Utara, Lam.Timur, Tanggamus, Tuba, Tubaba)</label>
        <label><input type="checkbox" class="filter-zona" value="z4"> Z4 (Way Kanan, Lam.Barat, Pesisir Barat, Mesuji)</label>
    </div>

    <div class="section-title" style="margin-top:20px">Filter Peran (tampilkan pegawai yang memiliki peran terpilih)</div>
    <div class="filter-group">
        <label><input type="checkbox" class="filter-peran" value="dl"> Dinas Luar</label>
        <label><input type="checkbox" class="filter-peran" value="pan_hotel"> Panitia Hotel</label>
        <label><input type="checkbox" class="filter-peran" value="pan_upt"> Panitia UPT</label>
        <label><input type="checkbox" class="filter-peran" value="fasil"> Fasilitator</label>
        <label><input type="checkbox" class="filter-peran" value="pes_hotel"> Peserta Hotel</label>
    </div>

    <div style="margin-top:16px">
        <button id="btnApplyFilter" class="btn-primary-dark"><i class="fas fa-filter"></i> Terapkan Filter</button>
        <button id="btnResetFilter" class="btn-secondary-dark" style="margin-left:8px"><i class="fas fa-undo"></i> Reset</button>
    </div>

    <div class="section-title" style="margin-top:20px">Catatan</div>
    <p style="font-size:13px;color:var(--text-muted)">
        <i class="fas fa-info-circle" style="color:var(--accent)"></i>
        Pencarian nama/alias berlaku langsung saat mengetik. Filter Zona & Peran diterapkan dengan tombol "Terapkan Filter".
        Contoh alias: "linda", "bu wati", "cecil", "orin", "echi".
    </p>
</div>
</div>
</div>
</div><!-- tab-content -->

        </div>
    </div>
</div>

<!-- ═══ MODAL: Detail Pegawai ═══ -->
<div class="modal fade" id="modalDetailPeg" tabindex="-1">
<div class="modal-dialog modal-lg" style="max-width:850px"><div class="modal-content">
    <div class="modal-header bg-info"><h5 class="modal-title"><i class="fas fa-user"></i> <span id="detailPegNama"></span></h5><button class="close" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body" style="max-height:70vh;overflow-y:auto">
        <div id="detailPegContent"><p style="text-align:center;color:var(--text-muted)"><i class="fas fa-spinner fa-spin"></i> Memuat...</p></div>
    </div>
</div></div></div>

<script>
$(function(){
    function esc(s){return $('<span>').text(s||'').html();}

    // Aliases for search
    var aliases = <?= json_encode(getAliasPegawai(), JSON_UNESCAPED_UNICODE) ?>;

    // Filter nama di tabel rekap
    $('#filterNama').on('keyup',function(){
        var q = $(this).val().toLowerCase().trim();
        if(!q){ applyAllFilters(); return; }
        // Check alias
        var resolved = aliases[q] ? aliases[q].toLowerCase() : null;
        $('#tblRekap tbody tr').each(function(){
            var nama = $(this).data('nama');
            var match = nama.indexOf(q)!==-1 || (resolved && nama.indexOf(resolved)!==-1);
            $(this).toggle(match);
        });
    });

    // ── Filter Zona/Peran ──
    // Column index mapping in the table (0-based after No and Nama = col index 2+)
    // z1_tgs=2, z1_hr=3, z2_tgs=4, z2_hr=5, z3_tgs=6, z3_hr=7, z4_tgs=8, z4_hr=9
    // pan_hotel_tgs=10, pan_upt_tgs=12, fasil_tgs=14, pes_hotel_tgs=16
    // jml_fasil_tgs=18, jml_dl_k38_tgs=20, jml_panitia_tgs=22, jml_peserta_tgs=24
    var zonaColMap = { z1: 2, z2: 4, z3: 6, z4: 8 };
    var peranColMap = { dl: 20, pan_hotel: 10, pan_upt: 12, fasil: 14, pes_hotel: 16 };

    function applyAllFilters() {
        var zonas = []; var perans = [];
        $('.filter-zona:checked').each(function(){ zonas.push($(this).val()); });
        $('.filter-peran:checked').each(function(){ perans.push($(this).val()); });
        var nameQ = $('#filterNama').val().toLowerCase().trim();
        var resolved = (nameQ && aliases[nameQ]) ? aliases[nameQ].toLowerCase() : null;

        $('#tblRekap tbody tr').each(function(){
            var $row = $(this);
            var cells = $row.find('td');
            var show = true;

            // Name filter
            if (nameQ) {
                var nama = $row.data('nama');
                if (nama.indexOf(nameQ) === -1 && !(resolved && nama.indexOf(resolved) !== -1)) {
                    show = false;
                }
            }

            // Zona filter: show row only if it has value > 0 in ANY checked zona
            if (show && zonas.length > 0) {
                var hasZona = false;
                $.each(zonas, function(_, z) {
                    var colIdx = zonaColMap[z];
                    if (colIdx !== undefined && parseInt(cells.eq(colIdx).text()) > 0) hasZona = true;
                });
                if (!hasZona) show = false;
            }

            // Peran filter: show row only if it has value > 0 in ANY checked peran
            if (show && perans.length > 0) {
                var hasPeran = false;
                $.each(perans, function(_, p) {
                    var colIdx = peranColMap[p];
                    if (colIdx !== undefined && parseInt(cells.eq(colIdx).text()) > 0) hasPeran = true;
                });
                if (!hasPeran) show = false;
            }

            $row.toggle(show);
        });
    }

    $('#btnApplyFilter').on('click', function(){ applyAllFilters(); });
    $('#btnResetFilter').on('click', function(){
        $('.filter-zona, .filter-peran').prop('checked', false);
        $('#filterNama').val('');
        $('#tblRekap tbody tr').show();
    });

    // Detail pegawai (click nama atau button)
    $(document).on('click','.detail-link,.btn-detail-peg',function(){
        var nama = $(this).data('nama');
        $('#detailPegNama').text(nama);
        loadRincian(nama, '#detailPegContent');
        $('#modalDetailPeg').modal('show');
    });

    // Tab 2 select
    $('#selPegawai').on('change',function(){
        var nama = $(this).val();
        if(!nama){ $('#rincianArea').html(''); return; }
        loadRincian(nama, '#rincianArea');
    });

    function loadRincian(nama, target){
        $(target).html('<p style="text-align:center;color:var(--text-muted)"><i class="fas fa-spinner fa-spin"></i> Memuat...</p>');
        $.getJSON('rekap_penugasan.php?action=get_rincian&nama='+encodeURIComponent(nama), function(data){
            var rincian = data.rincian || [];
            var ringkas = data.ringkasan || {};
            if(!rincian.length){ $(target).html('<p style="color:var(--text-muted)">Tidak ada data.</p>'); return; }

            var totalH = 0;
            var h = '<table class="table-dark-custom" style="font-size:12px"><thead><tr><th>No</th><th>Kode</th><th>Nama Kegiatan</th><th>Peran</th><th>Kab/Kota</th><th>Zona</th><th>Mulai</th><th>Selesai</th><th>Hari</th></tr></thead><tbody>';
            $.each(rincian, function(i,r){
                totalH += parseInt(r.jumlah_hari)||0;
                h+='<tr><td>'+(i+1)+'</td><td><strong>K'+esc(r.kode_kegiatan)+'</strong></td><td>'+esc(r.nama_kegiatan)+'</td><td><span class="badge-custom badge-secondary">'+esc(r.kategori_peran)+'</span></td><td>'+esc(r.kab_kota_lokus||'-')+'</td><td>'+esc(r.zona)+'</td><td>'+esc(r.tanggal_mulai_tugas||'-')+'</td><td>'+esc(r.tanggal_selesai_tugas||'-')+'</td><td><strong>'+r.jumlah_hari+'</strong></td></tr>';
            });
            h+='</tbody></table>';

            h+='<div style="margin-top:16px;padding:12px;background:rgba(99,102,241,.06);border-radius:8px"><strong style="color:var(--accent-light)">Total: '+rincian.length+' kegiatan, '+totalH+' hari tugas</strong></div>';

            if(Object.keys(ringkas).length){
                h+='<div style="margin-top:12px"><div class="section-title">Ringkasan per Kategori</div>';
                $.each(ringkas, function(cat, codes){
                    h+='<div style="margin-bottom:8px"><strong style="color:var(--text-primary);font-size:12px">'+esc(cat)+':</strong> ';
                    var parts = codes.split(', ');
                    $.each(parts, function(j,p){ h+='<span class="ringkasan-badge">'+esc(p)+'</span>'; });
                    h+='</div>';
                });
                h+='</div>';
            }
            $(target).html(h);
        });
    }
});
</script>
<?php include 'include/footer.php'; ?>

