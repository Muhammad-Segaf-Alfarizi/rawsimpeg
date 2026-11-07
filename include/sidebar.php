<?php
// sidebar.php — Navigasi adaptif berdasarkan role
$role        = $_SESSION['role']  ?? '';
$nip_session = $_SESSION['nip']   ?? '';

$sidebar_nama = 'Pengguna';
if (isset($koneksi) && $nip_session) {
    $stmt_sn = mysqli_prepare($koneksi, "SELECT nama FROM user WHERE nip = ? LIMIT 1");
    if ($stmt_sn) {
        mysqli_stmt_bind_param($stmt_sn, 's', $nip_session);
        mysqli_stmt_execute($stmt_sn);
        $res_sn = mysqli_stmt_get_result($stmt_sn);
        if ($row_sn = mysqli_fetch_assoc($res_sn)) $sidebar_nama = $row_sn['nama'];
        mysqli_stmt_close($stmt_sn);
    }
}

$role_label = [
    'super_admin' => 'Super Admin',
    'admin'       => 'Admin',
    'kepala'      => 'Pimpinan',
    'pegawai'     => 'Pegawai',
][$role] ?? ucfirst($role);

$initial    = strtoupper(mb_substr($sidebar_nama, 0, 1));
$cur_page   = basename($_SERVER['PHP_SELF']);

function nav_link($href, $icon, $label, $cur) {
    $active = ($cur === $href) ? 'active' : '';
    return "<div class='nav-item'><a href='{$href}' class='nav-link-custom {$active}'>"
         . "<span class='nav-icon'><i class='fas {$icon}'></i></span> {$label}</a></div>";
}
?>
<nav id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo"><i class="fas fa-users-cog"></i></div>
        <div class="brand-text">
            <div class="brand-title">SIMPEG</div>
            <div class="brand-sub">BGTK</div>
        </div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-label">Navigasi</div>

        <?php if ($role === 'super_admin'): ?>
            <?= nav_link('index_super_admin.php',    'fa-th-large',    'Dashboard',       $cur_page) ?>
            <?= nav_link('input_super_admin.php',    'fa-user-plus',   'Tambah Pegawai',  $cur_page) ?>
            <?= nav_link('tambah_user_super_admin.php','fa-user-shield','Tambah User',    $cur_page) ?>
            <div class="nav-label" style="margin-top:12px;">Penugasan SDM</div>
            <?= nav_link('kegiatan_sdm.php',         'fa-calendar-alt','Kegiatan & SDM',  $cur_page) ?>
            <?= nav_link('rekap_penugasan.php',      'fa-chart-bar',   'Rekap Penugasan', $cur_page) ?>

        <?php elseif ($role === 'admin'): ?>
            <?= nav_link('index_admin.php',          'fa-th-large',    'Dashboard',       $cur_page) ?>
            <div class="nav-label" style="margin-top:12px;">Penugasan SDM</div>
            <?= nav_link('kegiatan_sdm.php',         'fa-calendar-alt','Kegiatan & SDM',  $cur_page) ?>
            <?= nav_link('rekap_penugasan.php',      'fa-chart-bar',   'Rekap Penugasan', $cur_page) ?>

        <?php elseif ($role === 'kepala'): ?>
            <?= nav_link('index_pengarah_kepala.php','fa-th-large',    'Dashboard',       $cur_page) ?>
            <div class="nav-label" style="margin-top:12px;">Penugasan SDM</div>
            <?= nav_link('kegiatan_sdm.php',         'fa-calendar-alt','Kegiatan & SDM',  $cur_page) ?>
            <?= nav_link('rekap_penugasan.php',      'fa-chart-bar',   'Rekap Penugasan', $cur_page) ?>

        <?php elseif ($role === 'pegawai'): ?>
            <?= nav_link('index.php',                'fa-id-card',     'Profil Saya',     $cur_page) ?>
            <div class="nav-item">
                <a href="daftar_diklat.php?nip=<?= htmlspecialchars($nip_session) ?>"
                   class="nav-link-custom">
                    <span class="nav-icon"><i class="fas fa-graduation-cap"></i></span> Diklat Saya
                </a>
            </div>
            <div class="nav-item">
                <a href="daftar_penghargaan.php?nip=<?= htmlspecialchars($nip_session) ?>"
                   class="nav-link-custom">
                    <span class="nav-icon"><i class="fas fa-award"></i></span> Penghargaan
                </a>
            </div>
            <div class="nav-label" style="margin-top:12px;">Penugasan SDM</div>
            <?= nav_link('kegiatan_sdm.php',         'fa-calendar-alt','Kegiatan & SDM',  $cur_page) ?>
            <?= nav_link('rekap_penugasan.php',      'fa-chart-bar',   'Rekap Penugasan', $cur_page) ?>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar"><?= htmlspecialchars($initial) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($sidebar_nama) ?></div>
                <div class="user-role"><?= htmlspecialchars($role_label) ?></div>
            </div>
        </div>
    </div>
</nav>