<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super admin') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penilaian Kinerja Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</head>
<body>

    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <div class="layout">
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-icon">
                    <i data-lucide="command" style="width:20px; height:20px;"></i>
                </div>
                <span class="app-name">Sistem Penilaian Kinerja</span>
                <button class="btn btn-ghost btn-sm btn-icon mobile-close-btn" onclick="toggleSidebar()" style="display:none; margin-left:auto;">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <p class="menu-label">Menu Utama</p>
                <nav class="nav-group">
                    <a href="#dashboard" class="nav-item active">
                        <i data-lucide="layout-dashboard"></i> Dashboard
                    </a>
                    <a href="#form-input" class="nav-item">
                        <i data-lucide="file-plus-2"></i> Input Penilaian
                    </a>
                    <a href="#data-master" class="nav-item">
                        <i data-lucide="users"></i> Data Karyawan
                    </a>
                    <a href="#riwayat-penilaian" class="nav-item">
                        <i data-lucide="history"></i> Riwayat Penilaian
                    </a>
                    <a href="#monitoring" class="nav-item">
                        <i data-lucide="map-pin"></i> Monitoring Kerja
                    </a>
                    <a href="#schedule" class="nav-item">
                        <i data-lucide="calendar"></i> Penjadwalan
                    </a>
                    <a href="#riwayat-absensi-all" class="nav-item">
                        <i data-lucide="clock"></i> Riwayat Absensi
                    </a>
                </nav>

                <p class="menu-label" style="margin-top: 1.5rem;">Lainnya</p>
                <nav class="nav-group">
                    <a href="#settings" class="nav-item">
                        <i data-lucide="settings"></i> Pengaturan
                    </a>
                    <a href="#" class="nav-item text-red" id="logoutBtn">
                        <i data-lucide="log-out"></i> Keluar
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer" style="margin-top:auto; border-top:1px solid #e2e8f0; padding-top:1.5rem;">
                <p style="font-size:0.75rem; color:#64748b;">&copy; <?php echo date('Y'); ?> HR Dept v2.2</p>
            </div>
        </aside>

        <div class="main-wrapper">
            
            <header class="top-header">
                <div class="header-left" style="display:flex; align-items:center;">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <i data-lucide="menu"></i>
                    </button>
                    <h2 class="header-title" id="page-title-display">Dashboard</h2>
                </div>
                
                <div class="header-right">
                    <div class="user-mini">
                        <div class="user-info" style="text-align:right;">
                            <p class="name">Admin HR</p>
                            <p class="role">Administrator</p>
                        </div>
                        <div class="avatar">AD</div>
                    </div>
                </div>
            </header>

            <main class="content-body">
                
                <div id="dashboard" class="view-section">
                    
                    <div class="dashboard-filters">
                        <div class="filter-item" style="flex-grow: 1; min-width: 200px;">
                            <label>Cari Pegawai</label>
                            <div style="position: relative;">
                                <i data-lucide="search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; color: #94a3b8;"></i>
                                <input type="text" id="search-name" placeholder="Ketik nama..." 
                                    style="width: 100%; padding: 8px 8px 8px 35px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none;">
                            </div>
                        </div>
                        <div class="filter-item">
                            <label>Filter Departemen</label>
                            <select id="filter-dept">
                                <option value="">Semua Departemen</option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label>Filter Jabatan</label>
                            <select id="filter-role">
                                <option value="">Semua Jabatan</option>
                            </select>
                        </div>
                        <div style="padding-bottom: 2px;">
                            <button id="btn-apply-filter" class="btn btn-outline">
                                <i data-lucide="filter" style="width:16px;"></i> Terapkan
                            </button>
                            <button class="btn btn-primary" onclick="document.querySelector('a[href=\'#form-input\']').click()">
                                <i data-lucide="plus" style="width:16px;"></i> Input Baru
                            </button>
                        </div>
                    </div>

                    <div class="grid-4">
                        <div class="card stat-card">
                            <div class="stat-header">
                                <span class="stat-title">Total Penilaian</span>
                                <i data-lucide="users" class="stat-icon"></i>
                            </div>
                            <div class="stat-value" id="kpi-total">0</div>
                            <div class="stat-desc text-green">Data Terfilter</div>
                        </div>
                        <div class="card stat-card">
                            <div class="stat-header">
                                <span class="stat-title">Rata-rata Skor</span>
                                <i data-lucide="bar-chart-2" class="stat-icon"></i>
                            </div>
                            <div class="stat-value" id="kpi-avg">0.0</div>
                            <div class="stat-desc">Target KPI: 4.0</div>
                        </div>
                        <div class="card stat-card">
                            <div class="stat-header">
                                <span class="stat-title">High Performer</span>
                                <i data-lucide="award" class="stat-icon"></i>
                            </div>
                            <div class="stat-value" id="kpi-high">0</div>
                            <div class="stat-desc">Skor diatas 4.0</div>
                        </div>
                        <div class="card stat-card">
                            <div class="stat-header">
                                <span class="stat-title">Butuh Review</span>
                                <i data-lucide="alert-circle" class="stat-icon"></i>
                            </div>
                            <div class="stat-value" id="kpi-low">0</div>
                            <div class="stat-desc text-red">Skor dibawah 3.0</div>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Panduan Operasional (SOP)</h3>
                                <p class="card-sub">Panduan lengkap penggunaan seluruh fitur sistem admin.</p>
                            </div>
                            <div class="card-body" style="max-height: 280px; overflow-y: auto; padding-right: 10px;">
                                <div style="font-size: 0.9rem; color: #334155; display: flex; flex-direction: column; gap: 15px;">
                                    
                                    <!-- 1. Input Penilaian -->
                                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                                        <div style="background: #eff6ff; color: #2563eb; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 0.75rem; flex-shrink: 0;">1</div>
                                        <div>
                                            <strong style="color: #0f172a;">Prosedur Input Penilaian KPI</strong>
                                            <ul style="margin: 4px 0 0 15px; padding: 0; list-style-type: disc;">
                                                <li><strong>Mulai:</strong> Akses menu <strong>Input Penilaian</strong> di sidebar kiri.</li>
                                                <li><strong>Autofill:</strong> Ketik nama pegawai — jika sudah pernah dinilai, data profil lama terisi otomatis.</li>
                                                <li><strong>Jabatan/Pendidikan:</strong> Pilih <strong>"Lainnya"</strong> jika tidak tersedia di list, lalu ketik manual di kolom yang muncul.</li>
                                                <li><strong>Evaluasi:</strong> Berikan skor (1–5) untuk setiap poin pada 5 dimensi kompetensi.</li>
                                                <li><strong>Target Kerja:</strong> Isi rencana pencapaian &amp; evidence untuk jangka 3 Bulan, 6 Bulan, dan 1 Tahun.</li>
                                                <li><strong>Selesai:</strong> Klik <strong>"Simpan Data"</strong>. Jika pegawai baru, sistem otomatis membuat akun login.</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- 2. Kelola Data -->
                                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                                        <div style="background: #eff6ff; color: #2563eb; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 0.75rem; flex-shrink: 0;">2</div>
                                        <div>
                                            <strong style="color: #0f172a;">Kelola Data Karyawan</strong>
                                            <p style="margin: 0; margin-top: 2px;">Pada menu <strong>Data Karyawan</strong>:</p>
                                            <ul style="margin: 4px 0 0 15px; padding: 0; list-style-type: disc;">
                                                <li><strong>Filter &amp; Search:</strong> Gunakan dropdown status (High/Moderate) atau kolom pencarian nama/NIK.</li>
                                                <li style="margin-top:4px;"><strong>Detail (<i data-lucide="eye" style="width:12px; color:#3b82f6; vertical-align:middle;"></i>):</strong> Pop-up Rapor Lengkap — Radar Chart kompetensi, skor per-indikator, rincian target &amp; evidence.</li>
                                                <li style="margin-top:4px;"><strong>Update (<i data-lucide="pencil" style="width:12px; color:#f59e0b; vertical-align:middle;"></i>):</strong> Form terbuka dengan data lama terisi otomatis. Ubah skor/profil lalu "Simpan Data".</li>
                                                <li style="margin-top:4px;"><strong>Security MAC:</strong> Status <span class="badge bg-green" style="font-size:0.7rem;">ACTIVE</span> = perangkat terikat. Klik <i data-lucide="rotate-ccw" style="width:12px; color:#ef4444; vertical-align:middle;"></i> untuk reset dan izinkan perangkat baru.</li>
                                                <li style="margin-top:4px;"><strong>Export Excel:</strong> Klik tombol Excel di sudut kanan atas untuk unduh seluruh data tabel.</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- 3. Riwayat Penilaian -->
                                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                                        <div style="background: #eff6ff; color: #2563eb; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 0.75rem; flex-shrink: 0;">3</div>
                                        <div>
                                            <strong style="color: #0f172a;">Riwayat &amp; Laporan Penilaian</strong>
                                            <ul style="margin: 4px 0 0 15px; padding: 0; list-style-type: disc;">
                                                <li><strong>Filter Periode:</strong> Saring berdasarkan rentang bulan dan Periode 1/2 (1–15 = P1, 16–31 = P2).</li>
                                                <li><strong>Cari Pegawai:</strong> Ketik nama di kolom pencarian untuk fokus ke satu orang.</li>
                                                <li><strong>Export PDF:</strong> Cetak laporan akumulasi kinerja sesuai filter periode aktif.</li>
                                                <li><strong>Export PDF Detail:</strong> Dari Dashboard → filter → klik <strong>Export PDF</strong> untuk laporan lengkap per pegawai (skor + evidence).</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- 4. Penjadwalan -->
                                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                                        <div style="background: #eff6ff; color: #2563eb; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 0.75rem; flex-shrink: 0;">4</div>
                                        <div>
                                            <strong style="color: #0f172a;">Penjadwalan Tugas Karyawan</strong>
                                            <p style="margin: 0; margin-top: 2px;">Masuk ke menu <strong>Penjadwalan</strong>:</p>
                                            <ol style="margin: 4px 0 0 15px; padding: 0; list-style-type: decimal;">
                                                <li>Pilih nama pegawai dari dropdown.</li>
                                                <li>Isi <strong>Judul Tugas</strong> dan deskripsi detail pekerjaan.</li>
                                                <li>Pilih <strong>Tipe Jadwal</strong>: <em>Harian, Mingguan, Bulanan,</em> atau <strong>Tugas Khusus</strong> (untuk penugasan non-rutin/ad-hoc).</li>
                                                <li>Set tanggal <strong>Mulai</strong> dan <strong>Sampai</strong>. Jadwal <u>otomatis hilang</u> dari tampilan pegawai setelah tanggal berakhir.</li>
                                                <li>Klik <strong>Simpan Jadwal</strong>. Riwayat tugas muncul di tabel samping.</li>
                                            </ol>
                                        </div>
                                    </div>

                                    <!-- 5. Monitoring & Approval -->
                                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                                        <div style="background: #eff6ff; color: #2563eb; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 0.75rem; flex-shrink: 0;">5</div>
                                        <div>
                                            <strong style="color: #0f172a;">Monitoring &amp; Approval Laporan Kerja</strong>
                                            <p style="margin: 0; margin-top: 2px;">Masuk ke menu <strong>Monitoring Kerja</strong>:</p>
                                            <ul style="margin: 4px 0 0 15px; padding: 0; list-style-type: disc;">
                                                <li><strong>Filter:</strong> Saring berdasarkan tanggal, tipe jadwal (termasuk <strong>Tugas Khusus</strong>), atau nama pegawai.</li>
                                                <li><strong>Evidence:</strong> Lihat foto bukti dan catatan laporan yang dikumpulkan pegawai secara langsung.</li>
                                                <li><strong>Setujui:</strong> Klik untuk menyetujui laporan berstatus <em>Menunggu Approval</em>.</li>
                                                <li><strong>Revisi:</strong> Isi catatan revisi dan kirim kembali ke pegawai. Pegawai diblokir dari tugas lain hingga revisi selesai.</li>
                                                <li><strong>GPS Tracking:</strong> Klik ikon lokasi untuk melihat jalur pergerakan pegawai selama sesi kerja.</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- 6. Riwayat Absensi -->
                                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                                        <div style="background: #eff6ff; color: #2563eb; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 0.75rem; flex-shrink: 0;">6</div>
                                        <div>
                                            <strong style="color: #0f172a;">Riwayat Absensi Seluruh Karyawan</strong>
                                            <p style="margin: 0; margin-top: 2px;">Masuk ke menu <strong>Riwayat Absensi</strong>:</p>
                                            <ul style="margin: 4px 0 0 15px; padding: 0; list-style-type: disc;">
                                                <li><strong>Tipe Laporan:</strong> Pilih <em>Harian, Mingguan,</em> atau <em>Bulanan</em> — input tanggal menyesuaikan otomatis.</li>
                                                <li><strong>Data Tampil:</strong> Tanggal, Jam Masuk &amp; Pulang, Status (Ontime/Terlambat), Waktu Telat, dan Lokasi Clock-In.</li>
                                                <li><strong>Cari Pegawai:</strong> Ketik nama untuk melihat rekap absensi satu orang spesifik.</li>
                                                <li><strong>Export Excel:</strong> Unduh rekap absensi sesuai filter aktif dalam format .xlsx.</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- 7. Dashboard Analytics -->
                                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                                        <div style="background: #eff6ff; color: #2563eb; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; font-size: 0.75rem; flex-shrink: 0;">7</div>
                                        <div>
                                            <strong style="color: #0f172a;">Analisis Cepat Dashboard</strong>
                                            <ul style="margin: 4px 0 0 15px; padding: 0; list-style-type: disc;">
                                                <li><strong>Analisis Tim:</strong> Filter Departemen &amp; Jabatan — Radar Chart dan Kartu Statistik diperbarui otomatis.</li>
                                                <li><strong>Analisis Perorangan:</strong> Ketik nama di <strong>Cari Pegawai</strong> untuk fokus ke satu individu.</li>
                                                <li><strong>Kartu Statistik:</strong> <em>Total Penilaian</em>, <em>Rata-rata Skor</em>, <em>High Performer</em> (&ge;4.0), dan <em>Butuh Review</em> (&lt;3.0).</li>
                                                <li><strong>Export:</strong> Gunakan tombol <strong>Export Excel</strong> atau <strong>Export PDF</strong> di bawah chart untuk cetak laporan sesuai filter aktif.</li>
                                            </ul>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Peta Kompetensi</h3>
                                <p class="card-sub">Radar chart kekuatan tim (Sesuai Filter).</p>
                            </div>
                            <div class="card-body">
                                <div style="height: 250px; width:100%;">
                                    <canvas id="radarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="export-area">
                        <button class="btn btn-outline" onclick="exportData()">
                            <i data-lucide="file-spreadsheet" style="width:16px;"></i> Export Excel
                        </button>
                        <button class="btn btn-outline" onclick="exportToPDF()">
                            <i data-lucide="file-text" style="width:16px;"></i> Export PDF
                        </button>
                    </div>
                </div>

                <div id="form-input" class="view-section hidden">
                    <form id="kpiForm">
                        
                        <div class="card"> 
                            <div class="card-header">
                                <h3 class="card-title">Informasi Umum Pegawai</h3>
                                <p class="card-sub">Data diri karyawan yang dinilai.</p>
                            </div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Nama Lengkap</label>
                                        <input type="text" id="name" placeholder="Nama karyawan..." required>
                                    </div>
                                    <div class="form-group">
                                        <label>NIK/NIP</label>
                                        <input type="text" id="empId" placeholder="Nomor Induk...">
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Unit / Divisi / Departemen</label>
                                        <select id="dept" required>
                                            <option value="">Pilih Departemen...</option>
                                            <option value="BOD">BOD</option>
                                            <option value="FAD">FAD</option>
                                            <option value="FMD">FMD</option>
                                            <option value="HCID">HCID</option>
                                            <option value="HRAD">HRAD</option>
                                            <option value="KPCD">KPCD</option>
                                            <option value="PCMD">PCMD</option>
                                            <option value="SITD">SITD</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Jabatan</label>
                                        <select id="role" required>
                                            <option value="">Pilih Jabatan...</option>
                                            <option value="Staff">Staff</option>
                                            <option value="SPV">SPV</option>
                                            <option value="Manager">Manager</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                        <input type="text" id="role-custom" placeholder="Sebutkan Jabatan Lainnya..." style="display:none; margin-top:5px;">
                                    </div>
                                    <div class="form-group">
                                        <label>Kategori Pekerjaan</label>
                                        <select id="type" required>
                                            <option value="outsourcing">Outsourcing</option>
                                            <option value="PPPK">PPPK</option>
                                            <option value="PNS">PNS</option>
                                        </select>
                                    </div>
                                </div>
                                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                                <div class="card-header" style="padding-left: 0; padding-bottom: 15px;">
                                    <h3 class="card-title" style="font-size: 1.1em;">Informasi Khusus Pegawai</h3>
                                    <p class="card-sub">Value tambahan dari diri karyawan.</p>
                                </div> 

                                 <div class="form-row">
                                     <div class="form-group">
                                         <label>Pendidikan Terakhir</label>
                                         <select id="education">
                                             <option value="">Pilih Pendidikan...</option>
                                             <option value="SMA/SMK">SMA / SMK</option>
                                             <option value="D3">Diploma (D3)</option>
                                             <option value="S1">Sarjana (S1)</option>
                                             <option value="S2">Magister (S2)</option>
                                             <option value="Lainnya">Lainnya</option>
                                         </select>
                                         <input type="text" id="education-custom" placeholder="Sebutkan Pendidikan Lainnya..." style="display:none; margin-top:5px;">
                                     </div>
                                     <div class="form-group">
                                         <label>Posisi / Bidang Pekerjaan</label>
                                         <input type="text" id="position" placeholder="Contoh: Programmer / Accounting / Driver">
                                     </div>
                                 </div>

                                 <div class="form-row">
                                     <div class="form-group">
                                         <label>Lama Bekerja</label>
                                         <input type="text" id="tenure" placeholder="Contoh: 2 Tahun 5 Bulan">
                                     </div>
                                     <div class="form-group">
                                         <label>Sertifikat Keahlian</label>
                                         <input type="text" id="certificates" placeholder="Contoh: BNSP, TOEFL, Brevet A/B, Cisco CCNA...">
                                     </div>
                                 </div>

                                 <div class="form-row">
                                     <div class="form-group" style="width: 100%;"> 
                                         <label>Tanggal Penilaian</label>
                                         <input type="date" id="date" required>
                                     </div>
                                 </div>
                                 </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Kuesioner Evaluasi</h3>
                                <p class="card-sub" style="margin-bottom:0.8rem;">Penilaian kinerja berdasarkan KPI.</p>
                                <div style="background:#f1f5f9; padding:12px; border-radius:8px; font-size:0.85rem; color:#475569; border:1px solid #e2e8f0; line-height: 1.6;">
                                    <strong>Keterangan Nilai:</strong><br>
                                    1 = Gagal Memenuhi Ekspektasi<br>
                                    2 = Belum Memenuhi Ekspektasi<br>
                                    3 = Memenuhi Ekspektasi<br>
                                    4 = Melampaui Ekspektasi<br>
                                    5 = Jauh Melampaui Ekspektasi
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="questions-container"></div>
                                <div class="card" style="margin-top: 20px; border: 1px solid #e2e8f0;">
                                    <div class="card-header" style="background-color: #f8fafc;">
                                        <h3 class="card-title" style="font-size: 1rem;">Evidence Target Kerja</h3>
                                        <p class="card-sub">Rencana pencapaian dan bukti target kerja kedepan.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label style="font-weight:600; color:#475569; margin-bottom:5px; display:block;">Target Jangka Pendek (3 Bulan)</label>
                                            <textarea id="target3Months" placeholder="Deskripsi target & evidence 3 bulan..." style="min-height: 80px; width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></textarea>
                                        </div>

                                        <div class="form-group" style="margin-top:15px;">
                                            <label style="font-weight:600; color:#475569; margin-bottom:5px; display:block;">Target Jangka Menengah (6 Bulan)</label>
                                            <textarea id="target6Months" placeholder="Deskripsi target & evidence 6 bulan..." style="min-height: 80px; width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></textarea>
                                        </div>

                                        <div class="form-group" style="margin-top:15px;">
                                            <label style="font-weight:600; color:#475569; margin-bottom:5px; display:block;">Target Jangka Panjang (1 Tahun)</label>
                                            <textarea id="target1Year" placeholder="Deskripsi target & evidence 1 tahun..." style="min-height: 80px; width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="card" style="margin-top: 20px; border: 1px solid #e2e8f0; padding:1.5rem;">
                                    <h4 style="font-weight:700; color:#334155; margin-bottom:1rem; font-size:1rem;">Penilaian Tugas Tambahan</h4>
                                    <div class="radio-group">
                                        <label class="radio-opt"><input type="radio" name="extra_task" value="1" style="opacity:0; position:absolute;"> 1</label>
                                        <label class="radio-opt"><input type="radio" name="extra_task" value="2" style="opacity:0; position:absolute;"> 2</label>
                                        <label class="radio-opt"><input type="radio" name="extra_task" value="3" style="opacity:0; position:absolute;"> 3</label>
                                        <label class="radio-opt"><input type="radio" name="extra_task" value="4" style="opacity:0; position:absolute;"> 4</label>
                                        <label class="radio-opt"><input type="radio" name="extra_task" value="5" style="opacity:0; position:absolute;"> 5</label>
                                    </div>
                                </div>
                                <div class="form-group mt-4">
                                    <label>Catatan Tambahan</label>
                                    <textarea id="notes" placeholder="Berikan feedback kualitatif..."></textarea>
                                </div>

                                <div class="form-actions">
                                    <button type="button" class="btn btn-outline" onclick="document.getElementById('kpiForm').reset()">Reset</button>
                                    <button type="submit" class="btn btn-primary block-btn">Simpan Data</button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                <div id="data-master" class="view-section hidden">
                    <div class="card">
                        <div class="card-header flex-between">
                            <div>
                                <h3 class="card-title">Data Karyawan</h3>
                                <p class="card-sub">Daftar seluruh data karyawan beserta dengan nilai performanya.</p>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <span style="font-size:0.875rem; color:#64748b;">Tampilkan</span>
                                    <select id="limit-master" style="padding: 6px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; width: auto; background-color: white; height: 38px;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <span style="font-size:0.875rem; color:#64748b;">entry</span>
                                </div>
                                <div style="position: relative;">
                                    <i data-lucide="search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; color: #94a3b8;"></i>
                                    <input type="text" id="search-master" placeholder="Cari nama/NIK..." 
                                           style="padding: 6px 10px 6px 32px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; width: 200px; height: 38px;">
                                </div>
                                <select id="filter-status-master" style="padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; width: auto; height: 38px; background-color: white;">
                                    <option value="">Semua Status</option>
                                    <option value="High">High</option>
                                    <option value="Moderate">Moderate</option>
                                </select>
                                <button class="btn btn-outline" onclick="exportMasterExcel()" title="Export Excel Data Karyawan" style="height: 38px; display: flex; align-items: center; gap: 5px;">
                                    <i data-lucide="file-spreadsheet" style="width:16px;"></i> Excel
                                </button>
                            </div>
                            
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nama Pegawai</th>
                                            <th>Divisi</th>
                                            <th>Jabatan</th>
                                            <th>Tipe</th>
                                            <th>Total Skor</th>
                                            <th>Status</th>
                                            <th>Security</th>
                                            <th class="text-right" style="width: 1%; white-space: nowrap;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBody"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center" style="padding: 1rem 1.5rem; background: #fff; border-top: 1px solid #e2e8f0;">
                            <div id="pagination-info-master" style="color: #64748b; font-size: 0.875rem;">
                                Menampilkan 0 sampai 0 dari 0 entri
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0" id="pagination-controls-master" style="gap: 5px;">
                                    <!-- Populated by JS -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <div id="riwayat-penilaian" class="view-section hidden">
                    <div class="card">
                        <div class="card-header flex-between">
                            <div>
                                <h3 class="card-title">Riwayat Penilaian</h3>
                                <p class="card-sub">Laporan akumulasi kinerja periode tertentu.</p>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <span style="font-size:0.875rem; color:#64748b; font-weight:500;">Periode Bulan:</span>
                                    <input type="month" id="history-start" style="padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; height: 38px;">
                                    <span style="font-size:0.875rem; color:#64748b;">s/d</span>
                                    <input type="month" id="history-end" style="padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; height: 38px;">
                                    <button class="btn btn-outline btn-sm" id="btn-filter-history" style="height:38px;">
                                        <i data-lucide="filter" style="width:14px;"></i>
                                    </button>
                                    <button class="btn btn-outline btn-sm" onclick="exportHistoryPDF()" title="Export PDF" style="height:38px; margin-left:5px;">
                                        <i data-lucide="file-text" style="width:14px;"></i> PDF
                                    </button>
                                </div>
                                <div style="position: relative;">
                                    <i data-lucide="search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; color: #94a3b8;"></i>
                                    <input type="text" id="search-history" placeholder="Cari Nama Pegawai..." 
                                           style="padding: 6px 10px 6px 32px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; width: 200px; height: 38px;">
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tanggal Penilaian</th>
                                            <th>Nama</th>
                                            <th>Divisi</th>
                                            <th>Jabatan</th>
                                            <th style="min-width: 130px;">
                                                <div style="display:flex; align-items:center; justify-content: space-between;">
                                                    <span>Periode</span>
                                                    <select id="filter-period-header" style="border:none; background:transparent; font-weight:bold; cursor:pointer; outline:none; font-size: 0.9rem; color: #475569; margin-left: 5px;">
                                                        <option value="">All</option>
                                                        <option value="Periode 1">1</option>
                                                        <option value="Periode 2">2</option>
                                                    </select>
                                                </div>
                                            </th>
                                            <th>Periode Skor</th>
                                            <th>Total Skor</th>
                                            <th class="text-right" style="width: 1%; white-space: nowrap;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableBodyHistory"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center" style="padding: 1rem 1.5rem; background: #fff; border-top: 1px solid #e2e8f0;">
                            <div id="pagination-info-history" style="color: #64748b; font-size: 0.875rem;">
                                Menampilkan 0 sampai 0 dari 0 entri
                            </div>
                            <!-- Added limit selector here for layout balance if needed, or keeping it hidden/default 10 -->
                             <nav aria-label="Page navigation" style="display:flex; align-items:center; gap:10px;">
                                <select id="limit-history" style="padding: 4px; border: 1px solid #e2e8f0; border-radius: 4px; outline: none; font-size: 0.8rem;">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                                <ul class="pagination pagination-sm mb-0" id="pagination-controls-history" style="gap: 5px;">
                                    <!-- Populated by JS -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <div id="settings" class="view-section hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Pengaturan Sistem</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert-box">
                                <i data-lucide="info"></i>
                                <span>Fitur konfigurasi akun sedang dalam pengembangan.</span>
                            </div>
                        </div>
                    </div>
                </div>


                <div id="monitoring" class="view-section hidden">
                    <div class="card">
                        <div class="card-header flex-between">
                            <h3 class="card-title">Monitoring Karyawan</h3>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <span style="font-size:0.875rem; color:#64748b;">Tampilkan</span>
                                    <select id="limit-monitor" style="padding: 6px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; width: auto; background-color: white; height: 38px;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <span style="font-size:0.875rem; color:#64748b;">entry</span>
                                </div>
                                <div style="position: relative;">
                                    <i data-lucide="calendar" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; color: #94a3b8;"></i>
                                    <input type="date" id="monitorDate" style="padding-left: 32px; width: auto; font-size: 0.875rem; height: 38px;">
                                </div>
                                <div style="position: relative;">
                                    <select id="monitorType" style="padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; color: #475569; width: auto; height: 38px; background-color: white;">
                                        <option value="">Semua Tipe</option>
                                        <option value="Harian">Harian</option>
                                        <option value="Mingguan">Mingguan</option>
                                        <option value="Bulanan">Bulanan</option>
                                        <option value="Kondisional">Kondisional</option>
                                    </select>
                                </div>
                                <div style="position: relative;">
                                    <i data-lucide="search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; color: #94a3b8;"></i>
                                    <input type="text" id="monitorSearch" placeholder="Cari Nama Pegawai..." style="padding-left: 32px; width: 200px; font-size: 0.875rem; height: 38px;">
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Pegawai</th>
                                            <th>Tugas</th>
                                            <th>Tipe</th>
                                            <th style="min-width: 150px;">Waktu & Durasi</th>
                                            <th>Status</th>
                                            <th>Evidence & Catatan</th>
                                            <th>Lokasi</th>
                                            <th style="min-width:180px;">Aksi Manager</th>
                                        </tr>
                                    </thead>
                                    <tbody id="monitorTableBody">
                                        <!-- Data Loaded via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center" style="padding: 1rem 1.5rem; background: #fff; border-top: 1px solid #e2e8f0;">
                            <div id="pagination-info-monitor" style="color: #64748b; font-size: 0.875rem;">
                                Menampilkan 0 sampai 0 dari 0 entri
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0" id="pagination-controls-monitor" style="gap: 5px;">
                                    <!-- Populated by JS -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <div id="riwayat-absensi-all" class="view-section hidden">
                    <div class="card">
                        <div class="card-header flex-between">
                            <div>
                                <h3 class="card-title">Riwayat Absensi Keseluruhan</h3>
                                <p class="card-sub">Laporan kehadiran seluruh karyawan.</p>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <span style="font-size:0.875rem; color:#64748b;">Tampilkan</span>
                                    <select id="limit-att-admin" style="padding: 6px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; width: auto; background-color: white; height: 38px;">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <span style="font-size:0.875rem; color:#64748b;">entry</span>
                                </div>
                                
                                <select id="filter-att-type" style="padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; height: 38px; background-color: white;">
                                    <option value="daily">Harian</option>
                                    <option value="weekly">Mingguan</option>
                                    <option value="monthly">Bulanan</option>
                                </select>

                                <!-- Dynamic Date Input -->
                                <div id="att-date-container" style="position: relative;">
                                    <!-- Default: Date Input -->
                                    <input type="date" id="filter-att-date" style="padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; height: 38px;">
                                </div>

                                <div style="position: relative;">
                                    <i data-lucide="search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; color: #94a3b8;"></i>
                                    <input type="text" id="search-att-admin" placeholder="Cari Nama Pegawai..." 
                                           style="padding: 6px 10px 6px 32px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; width: 200px; height: 38px;">
                                </div>
                                <button class="btn btn-outline" onclick="exportAttToExcel()" title="Export ke Excel" style="height:38px; display:flex; align-items:center; gap:5px;">
                                    <i data-lucide="file-spreadsheet" style="width:16px;"></i> Excel
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Nama Pegawai</th>
                                            <th>Posisi</th>
                                            <th>Jam Masuk</th>
                                            <th>Jam Pulang</th>
                                            <th>Status</th>
                                            <th>Waktu Telat</th>
                                            <th>Lokasi Masuk</th>
                                        </tr>
                                    </thead>
                                    <tbody id="attAdminTableBody">
                                        <!-- JS Loaded -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center" style="padding: 1rem 1.5rem; background: #fff; border-top: 1px solid #e2e8f0;">
                            <div id="pagination-info-att-admin" style="color: #64748b; font-size: 0.875rem;">
                                Menampilkan 0 sampai 0 dari 0 entri
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0" id="pagination-controls-att-admin" style="gap: 5px;">
                                    <!-- Populated by JS -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <div id="schedule" class="view-section hidden">
                    
                    <div class="grid-2">
                        <!-- Form Tambah Jadwal -->
                         <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Buat Jadwal Baru</h3>
                                <p class="card-sub">Atur target pekerjaan untuk karyawan.</p>
                            </div>
                            <div class="card-body">
                                <form id="scheduleForm">
                                    <div class="form-group">
                                        <label>Pilih Pegawai</label>
                                        <select id="schedUser" required>
                                            <option value="">-- Loading Users --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Judul Tugas</label>
                                        <input type="text" id="schedTitle" placeholder="Contoh: Maintenance Rutin" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Deskripsi Detail</label>
                                        <textarea id="schedDesc" placeholder="Jelaskan detail pekerjaan..."></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Tipe Jadwal</label>
                                        <select id="schedType">
                                            <option value="Harian">Harian</option>
                                            <option value="Mingguan">Mingguan</option>
                                            <option value="Bulanan">Bulanan</option>
                                        </select>
                                    </div>
                                    <div class="form-row" style="margin-bottom:0;">
                                        <div class="form-group">
                                            <label>Mulai Tanggal</label>
                                            <input type="date" id="schedStart" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Sampai Tanggal</label>
                                            <input type="date" id="schedEnd" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;">
                                        <i data-lucide="plus-circle"></i> Simpan Jadwal
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Info/List Terakhir -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Riwayat Penugasan</h3>
                                <p class="card-sub">Daftar jadwal yang baru dibuat.</p>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-container" style="max-height: 500px; overflow-y:auto;">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Pegawai</th>
                                                <th>Tugas</th>
                                                <th>Tipe</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody id="scheduleTableBody">
                                            <!-- JS Load -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </main>
        </div>
    </div>

    <div id="detailModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="card-title">Detail Penilaian</h3>
                <button class="btn btn-ghost btn-sm btn-icon" onclick="closeModal('detailModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body" id="modalContent"></div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('detailModal')">Tutup</button>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="card-title">Konfirmasi Hapus</h3>
                <button class="btn btn-ghost btn-sm btn-icon" onclick="closeModal('deleteModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p style="color:#64748b;">Apakah Anda yakin ingin menghapus data penilaian ini? Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('deleteModal')">Batal</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Ya, Hapus</button>
            </div>
        </div>
    </div>

    <!-- Modal Revise Task -->
    <div id="reviseModal" class="modal-overlay">
        <div class="modal" style="max-width: 450px; padding:0; overflow:hidden; border-radius:12px;">
            <div style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); padding: 20px 24px; border-bottom: 1px solid #fecaca; display: flex; align-items: center; justify-content: space-between;">
                <div style="display:flex; align-items:center; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: #fca5a5; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #7f1d1d; flex-shrink: 0;">
                        <i data-lucide="edit-3"></i>
                    </div>
                    <div>
                        <h3 style="font-size:1.15rem; font-weight:700; color:#7f1d1d; margin:0; line-height: 1.2;">Revisi Laporan</h3>
                        <p style="font-size:0.8rem; color:#991b1b; margin:0; margin-top:2px;">Kirim tugas kembali ke pegawai</p>
                    </div>
                </div>
                <button class="btn btn-ghost btn-sm btn-icon" style="color:#7f1d1d; align-self: flex-start;" onclick="closeModal('reviseModal')">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.5rem; color:#334155;">Catatan Revisi <span style="color:#ef4444;">*</span></label>
                <textarea id="reviseNote" class="form-input" style="min-height: 100px; width:100%; padding:12px; border-radius:8px; border:1px solid #cbd5e1; font-size:0.9rem; resize:vertical; background:#f8fafc;" placeholder="Tuliskan secara jelas apa yang harus diperbaiki oleh pegawai..."></textarea>
                <p style="font-size:0.75rem; color:#64748b; margin-top:8px; display:flex; align-items:center; gap: 6px;">
                    <i data-lucide="info" style="width:14px; color:#3b82f6;"></i>
                    Pegawai dilarang memulai tugas lain sebelum revisi diselesaikan.
                </p>
            </div>
            <div class="modal-footer" style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button class="btn btn-outline" style="border-radius:6px; padding:8px 16px; font-weight:600;" onclick="closeModal('reviseModal')">Batal</button>
                <button class="btn btn-primary" id="confirmReviseBtn" style="background:#dc2626; border-color:#dc2626; border-radius:6px; padding:8px 20px; font-weight:600; display:flex; align-items:center; gap:6px; color: white;">
                    <i data-lucide="send" style="width:16px;"></i> Kirim Revisi
                </button>
            </div>
        </div>
    </div>

    <!-- SheetJS Library untuk Export Excel (.xlsx) -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <script src="js/app.js?v=<?= time() ?>"></script>
    <script src="js/app_pdf_export.js?v=<?= time() ?>"></script>
</body>
</html>