<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ─── AUTH ─────────────────────────────────────────────────────────────────────
$routes->get('/',          'Auth::index');
$routes->get('/login',     'Auth::index');
$routes->post('/login',    'Auth::login');
$routes->get('/logout',    'Auth::logout');

// ─── DASHBOARD ────────────────────────────────────────────────────────────────
$routes->get('/dashboard/super-admin',  'Dashboard::superAdmin',  ['filter' => 'role:super_admin']);
$routes->get('/dashboard/admin',        'Dashboard::admin',       ['filter' => 'role:admin']);
$routes->get('/dashboard/kepala',       'Dashboard::kepala',      ['filter' => 'role:kepala']);

// ─── PEGAWAI ──────────────────────────────────────────────────────────────────
$routes->get('/pegawai/profil',                   'Pegawai::profil',      ['filter' => 'auth']);
$routes->get('/pegawai/profil/(:segment)',        'Pegawai::profil/$1',   ['filter' => 'auth']);
$routes->get('/pegawai/tambah',                   'Pegawai::tambah',      ['filter' => 'role:super_admin']);
$routes->post('/pegawai/tambah',                  'Pegawai::simpan',      ['filter' => 'role:super_admin']);
$routes->get('/pegawai/edit/(:segment)',           'Pegawai::edit/$1',     ['filter' => 'role:super_admin,admin']);
$routes->post('/pegawai/edit/(:segment)',          'Pegawai::update/$1',   ['filter' => 'role:super_admin,admin']);
$routes->get('/pegawai/hapus/(:segment)',          'Pegawai::hapus/$1',    ['filter' => 'role:super_admin,admin']);
$routes->post('/pegawai/upload-sk/(:segment)',     'Pegawai::uploadSk/$1', ['filter' => 'auth']);

// ─── USER ─────────────────────────────────────────────────────────────────────
$routes->get('/user/tambah',                    'User::tambah',             ['filter' => 'role:super_admin']);
$routes->post('/user/tambah',                   'User::simpan',             ['filter' => 'role:super_admin']);
$routes->post('/user/hash-all',                 'User::hashAll',            ['filter' => 'role:super_admin']);
$routes->get('/user/edit-password/(:segment)',  'User::editPassword/$1',    ['filter' => 'auth']);
$routes->post('/user/edit-password/(:segment)', 'User::updatePassword/$1',  ['filter' => 'auth']);

// ─── DIKLAT ───────────────────────────────────────────────────────────────────
$routes->get('/diklat/(:segment)',               'Diklat::index/$1',     ['filter' => 'auth']);
$routes->get('/diklat/tambah/(:segment)',         'Diklat::tambah/$1',    ['filter' => 'auth']);
$routes->post('/diklat/simpan/(:segment)',        'Diklat::simpan/$1',    ['filter' => 'auth']);
$routes->get('/diklat/edit/(:segment)/(:num)',    'Diklat::edit/$1/$2',   ['filter' => 'auth']);
$routes->post('/diklat/update/(:segment)/(:num)', 'Diklat::update/$1/$2', ['filter' => 'auth']);
$routes->get('/diklat/hapus/(:segment)/(:num)',   'Diklat::hapus/$1/$2',  ['filter' => 'auth']);

// ─── PENGHARGAAN ──────────────────────────────────────────────────────────────
$routes->get('/penghargaan/(:segment)',               'Penghargaan::index/$1',     ['filter' => 'auth']);
$routes->get('/penghargaan/tambah/(:segment)',         'Penghargaan::tambah/$1',    ['filter' => 'auth']);
$routes->post('/penghargaan/simpan/(:segment)',        'Penghargaan::simpan/$1',    ['filter' => 'auth']);
$routes->get('/penghargaan/edit/(:segment)/(:num)',    'Penghargaan::edit/$1/$2',   ['filter' => 'auth']);
$routes->post('/penghargaan/update/(:segment)/(:num)', 'Penghargaan::update/$1/$2', ['filter' => 'auth']);
$routes->get('/penghargaan/hapus/(:segment)/(:num)',   'Penghargaan::hapus/$1/$2',  ['filter' => 'auth']);

// ─── ABK ──────────────────────────────────────────────────────────────────────
$routes->get('/abk/(:segment)',         'Abk::index/$1',    ['filter' => 'auth']);
$routes->post('/abk/simpan/(:segment)', 'Abk::simpan/$1',   ['filter' => 'auth']);
$routes->post('/abk/update/(:segment)', 'Abk::update/$1',   ['filter' => 'auth']);
$routes->get('/abk/hapus/(:segment)/(:num)', 'Abk::hapus/$1/$2', ['filter' => 'auth']);

// ─── TNA ──────────────────────────────────────────────────────────────────────
$routes->get('/tna/(:segment)',                      'Tna::index/$1',          ['filter' => 'auth']);
$routes->post('/tna/simpan/(:segment)',               'Tna::simpan/$1',         ['filter' => 'auth']);
$routes->post('/tna/update/(:segment)',               'Tna::update/$1',         ['filter' => 'auth']);
$routes->get('/tna/hapus/(:segment)/(:num)',          'Tna::hapus/$1/$2',       ['filter' => 'auth']);
$routes->post('/tna/persetujuan/(:segment)/(:num)',   'Tna::persetujuan/$1/$2', ['filter' => 'role:super_admin,admin']);

// ─── MANAJEMEN KEGIATAN, JADWAL & REKAP PENUGASAN SDM BGTK 2026 ──────────────
$routes->get('/kegiatan',                         'Penugasan::index',             ['filter' => 'role:super_admin,admin,kepala']);
$routes->get('/kegiatan/rincian/(:num)',          'Penugasan::rincianKegiatan/$1',['filter' => 'role:super_admin,admin,kepala']);
$routes->post('/kegiatan/simpan',                 'Penugasan::simpan',            ['filter' => 'role:super_admin,admin']);
$routes->post('/kegiatan/update',                 'Penugasan::update',            ['filter' => 'role:super_admin,admin']);
$routes->get('/kegiatan/hapus/(:num)',            'Penugasan::hapus/$1',          ['filter' => 'role:super_admin,admin']);

$routes->post('/kegiatan/penugasan/simpan',       'Penugasan::simpanPenugasan',   ['filter' => 'role:super_admin,admin']);
$routes->post('/kegiatan/penugasan/update',       'Penugasan::updatePenugasan',   ['filter' => 'role:super_admin,admin']);
$routes->get('/kegiatan/penugasan/hapus/(:num)',  'Penugasan::hapusPenugasan/$1', ['filter' => 'role:super_admin,admin']);
$routes->post('/kegiatan/penugasan/hapus/(:num)', 'Penugasan::hapusPenugasan/$1', ['filter' => 'role:super_admin,admin']);
$routes->post('/kegiatan/penugasan/bulk',         'Penugasan::plotBulk',          ['filter' => 'role:super_admin,admin']);

$routes->get('/jadwal-penugasan',                    'Penugasan::jadwal',            ['filter' => 'role:super_admin,admin,kepala']);
$routes->get('/jadwal-penugasan/cuti/list',          'Penugasan::listCuti',          ['filter' => 'auth']);
$routes->post('/jadwal-penugasan/cuti/simpan',       'Penugasan::simpanCuti',        ['filter' => 'role:super_admin,admin']);
$routes->post('/jadwal-penugasan/cuti/hapus/(:num)', 'Penugasan::hapusCuti/$1',      ['filter' => 'role:super_admin,admin']);
$routes->get('/rekap-penugasan',                     'Penugasan::rekap',             ['filter' => 'role:super_admin,admin,kepala']);
$routes->get('/rekap-penugasan/detail/(:any)',       'Penugasan::detailPegawai/$1',  ['filter' => 'auth']);
