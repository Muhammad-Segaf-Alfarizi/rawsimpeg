<?php
/**
 * Topbar Partial - diinclude via view() dari setiap dashboard view
 */
?>
<div id="topbar">
    <div class="topbar-left">
        <button id="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <?php if (isset($back_url)): ?>
        <a href="<?= $back_url ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
        <?php endif; ?>
        <span class="page-title">
            <i class="<?= $title_icon ?? 'fas fa-circle' ?>" style="color:var(--accent);margin-right:8px;"></i>
            <?= $page_title ?? 'SIMPEG' ?>
        </span>
    </div>
    <div class="topbar-right">
        <a href="<?= base_url('logout') ?>" class="btn-logout confirm-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

