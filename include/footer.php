<?php
/* footer.php
   Berisi: jQuery → Bootstrap JS → DataTables JS → FullCalendar → inisialisasi global
   PENTING: file ini diinclude SETELAH semua modal HTML, sehingga jQuery sudah tersedia
            untuk script yang memanggil $('#...').modal('show')
*/
$cal_nip    = $_GET['nip'] ?? ($_SESSION['nip'] ?? null);
$cal_events = [];
if ($cal_nip && isset($koneksi)) {
    // 1. Ambil dari riwayat_status (Cuti / DL)
    $stmt_cal = mysqli_prepare($koneksi,
        "SELECT r.status, r.keterangan, r.tanggal_mulai, r.tanggal_selesai, u.nama AS set_nama
         FROM riwayat_status r
         LEFT JOIN user u ON r.set_by = u.nip
         WHERE r.nip = ? AND r.status IN ('Cuti','DL')"
    );
    if ($stmt_cal) {
        mysqli_stmt_bind_param($stmt_cal, 's', $cal_nip);
        mysqli_stmt_execute($stmt_cal);
        $cal_res = mysqli_stmt_get_result($stmt_cal);
        while ($cr = mysqli_fetch_assoc($cal_res)) {
            if (!$cr['tanggal_mulai'] || !$cr['tanggal_selesai']) continue;
            $cal_events[] = [
                'title'       => $cr['status'] . ($cr['keterangan'] ? ' (' . $cr['keterangan'] . ')' : ''),
                'start'       => $cr['tanggal_mulai'],
                'end'         => date('Y-m-d', strtotime($cr['tanggal_selesai'] . ' +1 day')),
                'color'       => $cr['status'] === 'Cuti' ? '#f59e0b' : '#6366f1',
                'textColor'   => '#ffffff',
                'description' => 'Status: ' . $cr['status'] . ($cr['keterangan'] ? ' — ' . $cr['keterangan'] : '') . ' (Oleh: ' . ($cr['set_nama'] ?? '-') . ')',
                'source'      => 'riwayat_status',
            ];
        }
        mysqli_stmt_close($stmt_cal);
    }

    // 2. Ambil jadwal penugasan dari Kode dan SDM 2026 (penugasan_sdm + kegiatan)
    $stmt_ps = mysqli_prepare($koneksi,
        "SELECT ps.*, k.kode_kegiatan, k.nama_kegiatan, k.warna_hex, k.tim_kerja, k.tempat_lokus
         FROM penugasan_sdm ps
         INNER JOIN kegiatan k ON k.id_kegiatan = ps.id_kegiatan
         WHERE ps.nip = ? AND ps.tanggal_mulai_tugas IS NOT NULL AND ps.tanggal_selesai_tugas IS NOT NULL
         ORDER BY ps.tanggal_mulai_tugas ASC, CAST(k.kode_kegiatan AS UNSIGNED) ASC"
    );
    if ($stmt_ps) {
        mysqli_stmt_bind_param($stmt_ps, 's', $cal_nip);
        mysqli_stmt_execute($stmt_ps);
        $ps_res = mysqli_stmt_get_result($stmt_ps);
        while ($ps = mysqli_fetch_assoc($ps_res)) {
            $kode   = $ps['kode_kegiatan'] ?? '-';
            $namaK  = $ps['nama_kegiatan'] ?? '-';
            $peran  = $ps['kategori_peran'] ?? 'Penugasan';
            $peranD = !empty($ps['peran_detail']) ? $ps['peran_detail'] : $peran;
            $kab    = $ps['kab_kota_lokus'] ?? '-';
            $detLok = $ps['detail_lokus'] ?? '-';
            $lokus  = ($detLok && $detLok !== '-' && $detLok !== $kab) ? "{$kab} ({$detLok})" : $kab;
            $warna  = !empty($ps['warna_hex']) ? $ps['warna_hex'] : '#E5B8B7';
            $hari   = (int) ($ps['jumlah_hari'] ?? 1);
            $tahap  = !empty($ps['tahap']) ? ' · ' . $ps['tahap'] : '';
            $zona   = (!empty($ps['zona']) && $ps['zona'] !== 'NON_ZONA') ? ' [' . $ps['zona'] . ']' : '';

            $cal_events[] = [
                'title'           => "[Kode {$kode}] {$namaK} ({$peran}{$zona})",
                'start'           => $ps['tanggal_mulai_tugas'],
                'end'             => date('Y-m-d', strtotime($ps['tanggal_selesai_tugas'] . ' +1 day')),
                'backgroundColor' => $warna,
                'borderColor'     => 'rgba(15,23,42,0.35)',
                'textColor'       => '#0f172a',
                'source'          => 'kode_sdm',
                'kode'            => (string) $kode,
                'nama_kegiatan'   => $namaK,
                'tim_kerja'       => $ps['tim_kerja'] ?? '-',
                'peran'           => $peran,
                'peran_detail'    => $peranD,
                'lokus'           => $lokus,
                'zona'            => $ps['zona'] ?? 'NON_ZONA',
                'tahap'           => $ps['tahap'] ?? '',
                'tanggal_mulai'   => $ps['tanggal_mulai_tugas'],
                'tanggal_selesai' => $ps['tanggal_selesai_tugas'],
                'jumlah_hari'     => $hari,
                'warna'           => $warna,
                'description'     => "[Kode {$kode}] {$namaK} | {$peranD}{$tahap}{$zona} | Lokus: {$lokus} | {$ps['tanggal_mulai_tugas']} s.d. {$ps['tanggal_selesai_tugas']} ({$hari} hari)",
            ];
        }
        mysqli_stmt_close($stmt_ps);
    }
}
?>

    <!-- ============================================================
         JAVASCRIPT — urutan wajib dipertahankan:
         1. jQuery  2. Bootstrap (perlu jQuery)  3. DataTables (perlu jQuery)
         4. FullCalendar  5. Inisialisasi global
    ============================================================ -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

    <script>
    // ================================================================
    // SIDEBAR TOGGLE (Mobile)
    // ================================================================
    function openSidebar() {
        var s = document.getElementById('sidebar');
        var o = document.getElementById('sidebar-overlay');
        if (s) s.classList.add('sidebar-open');
        if (o) o.classList.add('show');
    }
    function closeSidebar() {
        var s = document.getElementById('sidebar');
        var o = document.getElementById('sidebar-overlay');
        if (s) s.classList.remove('sidebar-open');
        if (o) o.classList.remove('show');
    }
    var _sidebarBtn = document.getElementById('sidebar-toggle');
    if (_sidebarBtn) _sidebarBtn.addEventListener('click', openSidebar);

    // ================================================================
    // THEME TOGGLE (Light / Dark Mode)
    // ================================================================
    function syncLegacyThemeBtn() {
        var cur = document.documentElement.getAttribute('data-theme') || 'dark';
        var ico = document.getElementById('theme-toggle-icon');
        var txt = document.getElementById('theme-toggle-text');
        if (ico) ico.className = cur === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        if (txt) txt.textContent = cur === 'light' ? 'Dark Mode' : 'Light Mode';
    }
    window.toggleTheme = function () {
        var cur  = document.documentElement.getAttribute('data-theme') || 'dark';
        var next = cur === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', next);
        try { localStorage.setItem('simpeg_theme', next); } catch (e) {}
        syncLegacyThemeBtn();
    };
    (function () {
        var tr = document.querySelector('#topbar .topbar-right');
        if (tr && !document.getElementById('theme-toggle-btn')) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.id = 'theme-toggle-btn';
            btn.className = 'btn-theme-toggle';
            btn.title = 'Ganti Mode Terang / Gelap';
            btn.onclick = window.toggleTheme;
            btn.innerHTML = '<i class="fas fa-sun" id="theme-toggle-icon"></i><span id="theme-toggle-text">Light Mode</span>';
            tr.insertBefore(btn, tr.firstChild);
        }
        syncLegacyThemeBtn();
    })();

    $(document).ready(function () {

        // ================================================================
        // DATATABLES (auto-init semua tabel dengan class auto-datatable)
        // ================================================================
        $('table.auto-datatable').each(function () {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Indonesian.json'
                    },
                    columnDefs: [{ targets: -1, orderable: false }],
                    dom: '<"dt-top"lf>rt<"dt-bottom"ip>',
                    pageLength: 10,
                });
            }
        });

        // ================================================================
        // STATUS FIELD TOGGLE (untuk form kedinasan edit / input)
        // ================================================================
        var statusSelect = document.getElementById('statusSelect');
        var extraFields  = document.getElementById('extraFields');
        if (statusSelect && extraFields) {
            function _toggleStatus() {
                var v = statusSelect.value;
                extraFields.style.display = (v === 'DL' || v === 'Cuti') ? 'block' : 'none';
            }
            statusSelect.addEventListener('change', _toggleStatus);
            _toggleStatus();
        }

        // ================================================================
        // CONFIRM DELETE
        // ================================================================
        $(document).on('click', '.confirm-delete', function (e) {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) e.preventDefault();
        });

        // ================================================================
        // FULLCALENDAR (Terintegrasi dengan Kode dan SDM 2026)
        // ================================================================
        var calEl = document.getElementById('calendar');
        if (calEl) {
            var events = <?= json_encode($cal_events, JSON_UNESCAPED_UNICODE) ?>;
            window.simpegCalendar = new FullCalendar.Calendar(calEl, {
                initialView : 'dayGridMonth',
                height      : 460,
                events      : events,
                headerToolbar: {
                    left  : 'prev,next today',
                    center: 'title',
                    right : 'dayGridMonth,listYear'
                },
                buttonText: { today: 'Hari Ini', month: 'Bulan', list: 'Daftar 2026' },
                eventDidMount: function (info) {
                    var ep = info.event.extendedProps || {};
                    if (ep.description) {
                        info.el.title = ep.description;
                    }
                    if (ep.source === 'kode_sdm') {
                        info.el.style.fontWeight = '600';
                        info.el.style.fontSize = '11px';
                        info.el.style.cursor = 'pointer';
                        var mainEl = info.el.querySelector('.fc-event-main');
                        if (mainEl) mainEl.style.color = '#0f172a';
                    }
                },
                eventClick: function (info) {
                    if (typeof window.onSimpegCalendarEventClick === 'function') {
                        window.onSimpegCalendarEventClick(info.event);
                    }
                }
            });
            window.simpegCalendar.render();
        }

    }); // end document.ready
    </script>
</body>
</html>