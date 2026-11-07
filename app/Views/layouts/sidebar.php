<?php
/**
 * Sidebar Partial - diinclude via view() dari setiap dashboard view
 * BUKAN layout (tidak extend apapun)
 */
$role = session()->get('role');
$nip  = session()->get('nip');
$cur  = service('uri')->getSegments();
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
            <div class="nav-item">
                <a href="<?= base_url('dashboard/super-admin') ?>" class="nav-link-custom <?= (implode('/', $cur) === 'dashboard/super-admin') ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="fas fa-th-large"></i></span> Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= base_url('pegawai/tambah') ?>" class="nav-link-custom <?= (implode('/', $cur) === 'pegawai/tambah') ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="fas fa-user-plus"></i></span> Tambah Pegawai
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= base_url('user/tambah') ?>" class="nav-link-custom <?= (implode('/', $cur) === 'user/tambah') ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="fas fa-user-shield"></i></span> Tambah User
                </a>
            </div>
        <?php elseif ($role === 'admin'): ?>
            <div class="nav-item">
                <a href="<?= base_url('dashboard/admin') ?>" class="nav-link-custom <?= (implode('/', $cur) === 'dashboard/admin') ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="fas fa-th-large"></i></span> Dashboard
                </a>
            </div>
        <?php elseif ($role === 'kepala'): ?>
            <div class="nav-item">
                <a href="<?= base_url('dashboard/kepala') ?>" class="nav-link-custom <?= (implode('/', $cur) === 'dashboard/kepala') ? 'active' : '' ?>">
                    <span class="nav-icon"><i class="fas fa-th-large"></i></span> Dashboard
                </a>
            </div>
        <?php elseif ($role === 'pegawai'): ?>
            <div class="nav-item">
                <a href="<?= base_url('pegawai/profil/' . $nip) ?>" class="nav-link-custom">
                    <span class="nav-icon"><i class="fas fa-id-card"></i></span> Profil Saya
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= base_url('diklat/' . $nip) ?>" class="nav-link-custom">
                    <span class="nav-icon"><i class="fas fa-graduation-cap"></i></span> Diklat Saya
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= base_url('penghargaan/' . $nip) ?>" class="nav-link-custom">
                    <span class="nav-icon"><i class="fas fa-award"></i></span> Penghargaan
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= base_url('abk/' . $nip) ?>" class="nav-link-custom">
                    <span class="nav-icon"><i class="fas fa-briefcase"></i></span> ABK Saya
                </a>
            </div>
            <div class="nav-item">
                <a href="<?= base_url('tna/' . $nip) ?>" class="nav-link-custom">
                    <span class="nav-icon"><i class="fas fa-brain"></i></span> TNA Saya
                </a>
            </div>
        <?php endif; ?>

        <!-- MENU MONITORING 2026 -->
        <div class="nav-label" style="margin-top: 15px;">Monitoring 2026</div>
        <div class="nav-item">
            <a href="<?= base_url('kegiatan') ?>" class="nav-link-custom <?= (in_array('kegiatan', $cur)) ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-calendar-alt"></i></span> Daftar Kegiatan
            </a>
        </div>
        <div class="nav-item">
            <a href="<?= base_url('rekap-penugasan') ?>" class="nav-link-custom <?= (in_array('rekap-penugasan', $cur)) ? 'active' : '' ?>">
                <span class="nav-icon"><i class="fas fa-chart-bar"></i></span> Rekap Penugasan
            </a>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar"><?= strtoupper(mb_substr(session()->get('nama') ?? 'U', 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= esc(session()->get('nama')) ?></div>
                <div class="user-role"><?= match($role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', 'kepala' => 'Pimpinan', default => 'Pegawai' } ?></div>
            </div>
        </div>
    </div>
</nav>
