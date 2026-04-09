<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Pelaporan Kinerja</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        /* Special overrides for user portal if needed */
        .calendar-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .calendar-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;
        }
        .calendar-days {
            display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; gap: 5px;
        }
        .day {
            aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
            border-radius: 8px; cursor: pointer; font-size: 0.9rem; position: relative;
        }
        .day:hover { background: #f1f5f9; }
        .day.active { background: #16a34a; color: white; font-weight: 600; }
        .day.has-task::after {
            content: ''; position: absolute; bottom: 4px; width: 4px; height: 4px; background: #f59e0b; border-radius: 50%;
        }
        .task-list-item {
            padding: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 0.5rem; cursor: pointer; transition: 0.2s;
        }
        .task-list-item:hover { border-color: #16a34a; background: #f0fdf4; }

        .timer { font-size: 3rem; font-weight: 700; margin: 1rem 0; font-variant-numeric: tabular-nums; color: #0f172a; }
        
        .btn-big { width: 100%; height: 56px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.75rem; border: none; cursor: pointer; transition: 0.2s; }
        .btn-start { background: #16a34a; color: white; }
        .btn-start:hover { background: #15803d; }
        .btn-stop { background: #ef4444; color: white; }
        .btn-stop:hover { background: #dc2626; }

        .status-card {
            background: #1e293b; color: white; padding: 2rem; border-radius: 16px; margin-bottom: 1rem; text-align: center;
            position: relative; overflow: hidden;
        }
        .status-card .timer { color: white; } /* Override dark mode timer */

        /* Modal fixes */
        .evidence-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100; display: none; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .evidence-modal.open { display: flex; }
        .modal-box { background: white; width: 90%; max-width: 500px; padding: 2rem; border-radius: 16px; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        /* Submenu Styles */
        .submenu { padding-left: 1rem; display: none; flex-direction: column; gap: 0.25rem; margin-top: 0.25rem; }
        .submenu.open { display: flex; }
        .sub-item { font-size: 0.9rem; padding: 0.75rem 1rem; border-radius: 8px; color: #64748b; text-decoration: none; transition: 0.2s; display: block; }
        .sub-item:hover { background: #f1f5f9; color: #0f172a; }
        .sub-item.active { background: #eff6ff; color: #2563eb; font-weight: 500; }
        .arrow { transition: transform 0.2s; }
        .nav-item.open .arrow { transform: rotate(180deg); }
    </style>
</head>
<body>

    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <div class="layout">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-icon">
                    <i data-lucide="activity" style="width:20px; height:20px;"></i>
                </div>
                <span class="app-name">Sistem Pelaporan Kinerja</span>
                <button class="btn btn-ghost btn-sm btn-icon mobile-close-btn" onclick="toggleSidebar()" style="display:none; margin-left:auto;">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <p class="menu-label">Menu Utama</p>
                <nav class="nav-group">
                    <a href="#" class="nav-item active" id="menu-dashboard" onclick="switchView('dashboard')">
                        <i data-lucide="layout-dashboard"></i> Dashboard & Kerja
                    </a>
                    <a href="#" class="nav-item" id="menu-history" onclick="switchView('history')">
                        <i data-lucide="history"></i> Riwayat Laporan Kerja
                    </a>
                    
                    <!-- ABSENSI WITH SUBMENU -->
                    <div class="nav-item" onclick="toggleSubmenu(this)" style="cursor: pointer; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <i data-lucide="clock"></i> Absensi
                        </div>
                        <i data-lucide="chevron-down" class="arrow" style="width:16px; height:16px;"></i>
                    </div>
                    <div class="submenu">
                        <a href="javascript:void(0)" class="sub-item" id="menu-attendance" onclick="switchView('attendance')">Isi Absensi</a>
                        <a href="javascript:void(0)" class="sub-item" id="menu-attendance-history" onclick="switchView('attendance_history')">Riwayat Absensi</a>
                    </div>
                    <a href="#" class="nav-item" onclick="openSopModal()">
                        <i data-lucide="book-open"></i> Panduan / SOP
                    </a>
                </nav>

                <p class="menu-label" style="margin-top: 1.5rem;">Akun</p>
                <nav class="nav-group">
                    <a href="#" class="nav-item text-red" onclick="logout()">
                        <i data-lucide="log-out"></i> Keluar
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer" style="margin-top:auto; border-top:1px solid #e2e8f0; padding-top:1.5rem;">
                <p style="font-size:0.75rem; color:#64748b;">Logged in as Employee</p>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="main-wrapper">
            
            <header class="top-header">
                <div class="header-left" style="display:flex; align-items:center;">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <i data-lucide="menu"></i>
                    </button>
                    <h2 class="header-title">Tracking Pekerjaan</h2>
                </div>
                
                <div class="header-right">
                    <div class="user-mini">
                        <div class="user-info" style="text-align:right;">
                            <p class="name" id="userName">Loading...</p>
                            <p class="role" id="currentDateDisplay">--</p>
                        </div>
                        <div class="avatar">U</div>
                    </div>
                </div>
            </header>

            <main class="content-body">
                
                <!-- DASHBOARD GRID -->
                <div id="view-dashboard" class="grid-2" style="align-items: start;">
                    
                    <!-- LEFT: Action Card -->
                    <div>
                         <!-- IDLE STATE (Always Visible for Tasks) -->
                        <div id="idleState">
                            <!-- LIST OF ACTIVE SESSIONS -->
                            <div id="activeSessionsList" class="hidden" style="margin-bottom: 2rem;"></div>

                            <!-- Card 1: Scheduled/Selected (Existing) -->
                            <div class="card" style="padding:2rem; text-align:center;">
                                <div style="width:64px; height:64px; background:#f0fdf4; color:#16a34a; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
                                    <i data-lucide="briefcase" style="width:32px; height:32px;"></i>
                                </div>
                                <h2 style="font-size:1.5rem; margin-bottom:0.5rem; color:#0f172a; font-weight:700;">Siap Bekerja?</h2>
                                <p style="color:#64748b; margin-bottom:2rem;">Pilih tugas dari jadwal di kalender atau pilih manual di bawah ini untuk memulai sesi.</p>
                                
                                <div style="text-align:left; margin-bottom:1.5rem;">
                                    <label style="font-weight:600; font-size:0.9rem;">Pilih Tugas</label>
                                    <select id="taskSelect" style="width:100%; height:48px; border-radius:8px; border:1px solid #cbd5e1; padding:0 10px; margin-top:5px; font-size:1rem;">
                                        <option value="">-- Pilih Tugas --</option>
                                    </select>
                                </div>

                                <button class="btn-big btn-start" onclick="startWork(this)">
                                    <i data-lucide="play-circle"></i> Mulai Sesuai Jadwal
                                </button>
                            </div>

                        </div>
                        
                        <!-- Card 2: Conditional Task (Always Visible) -->
                        <div class="card" style="padding:1.5rem; margin-top: 1.5rem;">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:1rem;">
                                <div style="width:40px; height:40px; background:#eff6ff; color:#3b82f6; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                                    <i data-lucide="zap" style="width:20px; height:20px;"></i>
                                </div>
                                <div>
                                    <h3 style="font-size:1.1rem; font-weight:700; color:#0f172a;">Tugas Kondisional</h3>
                                    <p style="font-size:0.8rem; color:#64748b;">Pekerjaan mendadak / insidental</p>
                                </div>
                            </div>
                            
                            <div style="margin-bottom:1rem;">
                                <label style="font-weight:600; font-size:0.9rem;">Nama / Kategori Tugas</label>
                                <input type="text" id="manualTaskInput" placeholder="Contoh: Perbaikan AC Server..." style="width:100%; height:42px; margin-top:5px;">
                            </div>
                            
                            <button class="btn btn-outline" style="width:100%; justify-content:center; color:#3b82f6; border-color:#3b82f6; font-weight:600;" onclick="startConditionalTimer(this)">
                                <i data-lucide="play"></i> Mulai Timer Kondisional
                            </button>
                        </div>
                    </div>

                    <!-- RIGHT: Calendar -->
                    <div>
                        <div class="calendar-card">
                            <div class="calendar-header">
                                <h3 class="card-title">Jadwal Penugasan</h3>
                                <div style="font-size:0.9rem; font-weight:600; color:#16a34a;" id="calMonthYear">Jan 2024</div>
                            </div>
                            <div style="margin-bottom:10px; display:flex; font-size:0.75rem; color:#94a3b8; justify-content:space-between; padding:0 10px;">
                                <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
                            </div>
                            <div class="calendar-days" id="calendarDays">
                                <!-- Days Generated via JS -->
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Daftar Tugas</h3>
                                <p class="card-sub" id="selectedDateLabel">Hari ini</p>
                            </div>
                            <div class="card-body">
                                <div id="taskListContainer">
                                    <p style="color:#94a3b8; font-style:italic;">Memuat jadwal...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VIEW: HISTORY -->
                <div id="view-history" class="hidden">
                    <div class="card">
                        <div class="card-header" style="display:flex; justify-content:space-between; align-items: flex-start;">
                            <div>
                                <h3 class="card-title">Riwayat Absensi & Pekerjaan</h3>
                                <p class="card-sub">Daftar semua sesi pekerjaan yang telah anda lakukan.</p>
                            </div>
                            <div style="display:flex; align-items:center; gap:5px;">
                                <span style="font-size:0.875rem; color:#64748b;">Show</span>
                                <select id="limit-history" onchange="renderHistoryTable()" style="padding: 4px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; width: auto; background-color: white; height: 32px;">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                </select>
                                <span style="font-size:0.875rem; color:#64748b;">entries</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-striped table-hover table-bordered" style="width:100%; font-size: 0.875rem; vertical-align: middle;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="padding:12px;">Tanggal</th>
                                            <th style="padding:12px;">Tugas</th>
                                            <th style="padding:12px;">Evidence & Catatan</th>
                                            <th style="text-align:center; padding:12px;">Waktu</th>
                                            <th style="text-align:center; padding:12px;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyTableBody">
                                        <!-- JS Populated -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div id="historyLoading" style="text-align:center; padding:3rem; color:#94a3b8;">
                                <i data-lucide="loader-2" class="spin" style="margin-bottom:0.5rem"></i>
                                <p>Memuat riwayat...</p>
                            </div>

                            <div id="historyEmpty" class="hidden" style="text-align:center; padding:3rem; color:#64748b;">
                                <div style="margin-bottom:1rem; opacity:0.5;"><i data-lucide="clipboard-list" style="width:48px; height:48px;"></i></div>
                                <p>Belum ada riwayat pekerjaan.</p>
                            </div>
                        </div>
                        <!-- Footer Pagination -->
                        <div class="card-footer d-flex justify-content-between align-items-center" style="padding: 1rem 1.5rem; background: #fff; border-top: 1px solid #e2e8f0; border-radius: 0 0 var(--radius) var(--radius);">
                            <div id="pagination-info-history" style="color: #64748b; font-size: 0.875rem;">
                                Menampilkan 0 - 0 dari 0 data
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination pagination-sm mb-0" id="pagination-controls-history" style="gap: 5px;">
                                    <!-- JS Populated -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- VIEW: ABSENSI (ISI) -->
                <div id="view-attendance" class="hidden">
                    <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
                        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; color: #0f172a;">Absensi Harian</h2>
                        <p style="color: #64748b; margin-bottom: 2rem;">Silahkan lakukan Clock In (Pagi) dan Clock Out (Sore).</p>

                        <!-- STATUS INFO -->
                        <div id="attendance-status-card" class="hidden" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; display: inline-block; text-align: left;">
                            <div style="font-weight: 600;">Status Hari Ini:</div>
                            <div id="att-summary-text" style="font-size: 0.9rem; margin-top: 4px;">-</div>
                        </div>

                        <div style="max-width: 600px; margin: 0 auto 1.5rem; text-align: left;">
                            <label style="font-weight: 600; font-size: 0.9rem; margin-bottom: 5px; display: block;">Tipe Kehadiran</label>
                            <select id="workTypeSelect" style="width: 100%; height: 48px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 1rem;">
                                <option value="WFO">WFO (Work From Office)</option>
                                <option value="WFH">WFH (Work From Home)</option>
                            </select>
                        </div>

                        <div class="grid-2" style="gap: 1.5rem; max-width: 600px; margin: 0 auto; margin-top: 1rem;">
                            <!-- CLOCK IN -->
                            <button id="btn-clock-in" class="btn-big" style="background: #2563eb; color: white; flex-direction: column; height: auto; padding: 1.5rem;" onclick="handleAttendance('in')">
                                <i data-lucide="log-in" style="width: 32px; height: 32px;"></i>
                                <span>CLOCK IN</span>
                                <span style="font-size: 0.8rem; font-weight: 400; opacity: 0.9;">Masuk Kerja</span>
                            </button>

                            <!-- CLOCK OUT -->
                            <button id="btn-clock-out" class="btn-big" style="background: #f97316; color: white; flex-direction: column; height: auto; padding: 1.5rem; opacity: 0.5; cursor: not-allowed;" disabled onclick="handleAttendance('out')">
                                <i data-lucide="log-out" style="width: 32px; height: 32px;"></i>
                                <span>CLOCK OUT</span>
                                <span style="font-size: 0.8rem; font-weight: 400; opacity: 0.9;">Pulang Kerja</span>
                            </button>
                        </div>
                        
                        <p style="margin-top: 2rem; font-size: 0.8rem; color: #94a3b8;">
                            <i data-lucide="map-pin" style="width: 14px; height: 14px; vertical-align: middle;"></i> 
                            Lokasi wajib: SEAMEO BIOTROP (Radius 500m)
                        </p>
                    </div>
                </div>

                <!-- VIEW: ABSENSI HISTORY -->
                <div id="view-attendance-history" class="hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Riwayat Absensi</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-striped table-hover" style="width:100%; font-size: 0.875rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="padding:12px;">Tanggal</th>
                                            <th style="padding:12px;">Jam Masuk</th>
                                            <th style="padding:12px;">Jam Pulang</th>
                                            <th style="padding:12px;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="attHistoryBody">
                                        <!-- JS Populated -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Evidence Modal -->
    <div id="evidenceModal" class="evidence-modal">
        <div class="modal-box">
            <h2 style="margin-bottom: 0.5rem; font-size:1.25rem; font-weight:700;">Laporkan Hasil Kerja</h2>
            <p style="color: #64748b; margin-bottom: 1.5rem;">Unggah bukti pekerjaan anda sebelum menyelesaikan sesi.</p>
            
            <form id="evidenceForm">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Catatan Pekerjaan</label>
                    <textarea id="workNotes" placeholder="Apa yang sudah dikerjakan..." style="width: 100%; padding: 1rem; border-radius: 8px; border: 1px solid #cbd5e1;" required></textarea>
                </div>
                
                <div class="form-group" style="margin-bottom:1.5rem;">
                    <label>Bukti (Foto / PDF)</label>
                    <input type="file" id="evidenceFile" accept="image/*,application/pdf" required style="padding:10px; height:auto;">
                </div>

                <div style="display: flex; gap: 1rem;">
                    <button type="button" class="btn btn-outline" style="flex:1;" onclick="closeStopModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex:2;">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- QR Scanner Modal -->
    <div id="qrScannerModal" class="evidence-modal">
        <div class="modal-box" style="text-align:center;">
            <h2 style="margin-bottom: 0.5rem; font-size:1.25rem; font-weight:700;">Scan QR Absensi</h2>
            <p style="color: #64748b; margin-bottom: 1.5rem;">Arahkan kamera ke QR Code di layar receptionist.</p>
            
            <div id="qr-reader" style="width:100%; border-radius:8px; overflow:hidden;"></div>
            
            <div style="margin-top:1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeQrModal()">Batal</button>
            </div>
        </div>
    </div>

    <!-- SOP Modal -->
    <div id="sopModal" class="evidence-modal">
        <div class="modal-box" style="max-width: 600px; max-height: 80vh; overflow-y: auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="font-size:1.25rem; font-weight:700;">Panduan Penggunaan (SOP)</h2>
                <button class="btn btn-ghost btn-sm btn-icon" onclick="closeSopModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            
            <div style="color: #475569; line-height: 1.6;">
                <h3 style="font-weight:600; color:#0f172a; margin-top:1rem;">1. Memulai Pekerjaan</h3>
                <ul style="list-style:disc; padding-left:1.5rem; margin-bottom:1rem;">
                    <li>Pastikan GPS di HP/Laptop anda <strong>AKTIF</strong>.</li>
                    <li><strong>Tugas Terjadwal:</strong> Cek kalender, klik tanggal, pilih tugas dari daftar, lalu klik "Mulai Sesuai Jadwal".</li>
                    <li><strong>Tugas Manual:</strong> Pilih tugas dari dropdown "Pilih Tugas Manual", lalu klik "Mulai Sesuai Jadwal".</li>
                    <li><strong>Tugas Dadakan:</strong> Isi kolom "Tugas Kondisional" di bagian bawah, lalu klik "Mulai Rekap Pekerjaan".</li>
                </ul>

                <h3 style="font-weight:600; color:#0f172a; margin-top:1rem;">2. Selama Bekerja</h3>
                <ul style="list-style:disc; padding-left:1.5rem; margin-bottom:1rem;">
                    <li>Waktu akan terus berjalan di sistem (Timer).</li>
                    <li>Lokasi anda akan terpantau secara berkala oleh sistem.</li>
                    <li>Jangan menutup tab browser sepenuhnya jika bisa, agar timer tetap akurat di tampilan anda.</li>
                </ul>

                <h3 style="font-weight:600; color:#0f172a; margin-top:1rem;">3. Menyelesaikan Pekerjaan</h3>
                <ul style="list-style:disc; padding-left:1.5rem; margin-bottom:1rem;">
                    <li>Klik tombol merah <strong>"Laporkan Hasil Kerja"</strong>.</li>
                    <li>Isi <strong>Catatan Pekerjaan</strong> dengan detail aktivitas yang telah dilakukan.</li>
                    <li>Wajib mengunggah <strong>Bukti (Foto atau PDF)</strong>.</li>
                    <li>Klik <strong>"Kirim Laporan"</strong> untuk mengakhiri sesi dan menyimpan data.</li>
                </ul>
                
                <div style="background:#f0f9ff; padding:1rem; border-radius:8px; border:1px solid #bae6fd; margin-top:1.5rem; font-size:0.9rem;">
                    <strong>Catatan Penting:</strong> <br>
                    Jika browser meminta izin lokasi (Location Permission), pilih <strong>"Izinkan" (Allow)</strong> agar aplikasi dapat berjalan normal.
                </div>
            </div>
            
            <div style="margin-top: 2rem; text-align: right;">
                <button class="btn btn-primary" onclick="closeSopModal()">Saya Mengerti</button>
            </div>
        </div>
    </div>

<script>
    // --- SIDEBAR TOGGLE (Copied from main app) ---
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        const closeBtn = document.querySelector('.mobile-close-btn');
        
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
        if (sidebar.classList.contains('open')) {
            closeBtn.style.display = 'flex';
        } else {
            closeBtn.style.display = 'none';
        }
    }

    // --- TRACKING CORE ---
    let activeSessions = []; // Array of {id, task_name, startTime, timerInterval}
    let gpsInterval = null;
    let myTasks = [];
    
    // Pagination state
    let allHistoryData = [];
    let currentHistoryPage = 1;

    document.addEventListener('DOMContentLoaded', async () => {
        lucide.createIcons();
        
        // Date Display
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('currentDateDisplay').textContent = now.toLocaleDateString('id-ID', options);

        await checkAuth();
        await loadTasks(); // Manual Tasks
        await loadMySchedule(); // Scheduled Tasks
        await checkActiveSessions(); // UPDATED FUNCTION NAME
        
        renderCalendar(now);
    });

    // --- AUTH & SETUP ---
    async function checkAuth() {
        const res = await fetch('php/auth.php', { method: 'POST', body: JSON.stringify({ action: 'check_session' }) });
        const data = await res.json();
        
        if (data.status !== 'logged_in') {
            window.location.href = 'login.php';
        } else if (data.user.role === 'admin') {
            // If logged in as admin, redirect to admin dashboard
            window.location.href = 'index.php';
        } else {
            document.getElementById('userName').textContent = data.user.name;
        }
    }

    async function loadTasks() {
        const res = await fetch('php/tracking_api.php?action=get_tasks');
        const json = await res.json();
        if (json.status === 'success') {
            const sel = document.getElementById('taskSelect');
            sel.innerHTML = '<option value="">-- Pilih Tugas --</option>';
            json.data.forEach(t => {
                sel.innerHTML += `<option value="${t.task_name}">${t.task_name}</option>`;
            });
        }
    }

    async function loadMySchedule() {
        const res = await fetch('php/tracking_api.php?action=get_my_schedule');
        const json = await res.json();
        if (json.status === 'success') {
            myTasks = json.data;
            renderCalendar(new Date());
        }
    }

    // --- CALENDAR LOGIC ---
    function renderCalendar(date) {
        const year = date.getFullYear();
        const month = date.getMonth();
        const today = new Date();
        
        const monthNames = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
        document.getElementById('calMonthYear').textContent = `${monthNames[month]} ${year}`;

        const firstDay = new Date(year, month, 1).getDay(); // 0 = Sun
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        // Adjust for Monday start (Optional, but common in ID)
        let startDay = firstDay - 1; 
        if (startDay < 0) startDay = 6;

        const container = document.getElementById('calendarDays');
        container.innerHTML = '';

        for (let i = 0; i < startDay; i++) {
            container.innerHTML += `<div></div>`;
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const dStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const hasTask = myTasks.some(t => {
                return dStr >= t.start_date && dStr <= t.end_date;
            });

            const isToday = (d === today.getDate() && month === today.getMonth() && year === today.getFullYear());
            const activeClass = isToday ? 'active' : '';
            const taskClass = hasTask ? 'has-task' : '';

            container.innerHTML += `<div class="day ${activeClass} ${taskClass}" onclick="selectDate('${dStr}')">${d}</div>`;
        }
        
        // Auto select today
        const todayStr = `${year}-${String(month+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;
        selectDate(todayStr); // Initial Select
    }

    window.selectDate = function(dateStr) {
        document.getElementById('selectedDateLabel').textContent = "Tanggal: " + dateStr;
        const list = document.getElementById('taskListContainer');
        const tasksForDate = myTasks.filter(t => dateStr >= t.start_date && dateStr <= t.end_date);
        
        if(tasksForDate.length === 0) {
            list.innerHTML = '<p style="color:#94a3b8; padding:10px; text-align:center;">Tidak ada jadwal di tanggal ini.</p>';
        } else {
            list.innerHTML = '';
            tasksForDate.forEach(t => {
                list.innerHTML += `
                    <div class="task-list-item" onclick="selectScheduledTask('${t.title}')">
                        <div style="font-weight:600; color:#0f172a;">${t.title}</div>
                        <div style="font-size:0.85rem; color:#64748b;">${t.description || '-'}</div>
                        <div style="margin-top:5px; font-size:0.75rem;"><span class="badge bg-yellow" style="background:#fef9c3; color:#a16207; padding:2px 8px; border-radius:10px;">${t.type}</span></div>
                    </div>
                `;
            });
        }
    }

    window.selectScheduledTask = function(title) {
        const sel = document.getElementById('taskSelect');
        let found = false;
        for(let i=0; i<sel.options.length; i++) {
            if(sel.options[i].text === title) {
                sel.selectedIndex = i;
                found = true;
                break;
            }
        }
        
        if(!found) {
            const opt = document.createElement('option');
            opt.value = '999'; 
            opt.text = title;
            sel.add(opt);
            sel.value = '999';
        }
        
        // Visual feedback
        sel.style.borderColor = "#16a34a";
        setTimeout(() => sel.style.borderColor = "#cbd5e1", 1000);
        
        // Scroll to Select
        sel.scrollIntoView({behavior: "smooth", block: "center"});
    }

    // --- TRACKING CORE (UPDATED FOR MULTIPLE SESSIONS) ---
    async function checkActiveSessions() {
        const res = await fetch('php/tracking_api.php?action=check_active_session');
        const json = await res.json();
        
        // Clear local state first
        activeSessions.forEach(s => clearInterval(s.timerInterval));
        activeSessions = [];
        document.getElementById('activeSessionsList').innerHTML = ''; // Clear DOM List
        
        if (json.status === 'active' && json.sessions) {
            // Multiple sessions
            json.sessions.forEach(sess => {
                restoreSession(sess);
            });
            
            toggleActiveView(true);
            startGpsTracking();
        } else {
            toggleActiveView(false);
            stopGpsTracking();
        }
    }

    function restoreSession(sessData) {
        // Calculate start time
        let startTime;
        if (sessData.elapsed_seconds !== undefined) {
             startTime = new Date(Date.now() - (sessData.elapsed_seconds * 1000));
        } else {
             startTime = new Date(sessData.start_time);
        }

        const sessObj = {
            id: sessData.id,
            task_name: sessData.task_name,
            startTime: startTime,
            status: sessData.status,
            manager_note: sessData.manager_note,
            timerInterval: null
        };

        // Render Card
        createSessionCard(sessObj);

        // Start Timer only if active
        if (sessObj.status === 'active') {
            startSessionTimer(sessObj);
        }
        
        activeSessions.push(sessObj);
    }

    function createSessionCard(sessObj) {
        const container = document.getElementById('activeSessionsList');
        const div = document.createElement('div');
        div.className = 'status-card';
        div.id = `session-card-${sessObj.id}`;
        div.style.marginBottom = '1rem';
        
        if (sessObj.status === 'revision') {
            div.style.background = 'linear-gradient(135deg, #7f1d1d, #991b1b)';
            div.innerHTML = `
                <div style="opacity:0.9; margin-bottom:0.5rem; font-size:0.85rem; letter-spacing:0.05em; color:#fca5a5; font-weight:700;">BUTUH REVISI BUKTI</div>
                <h2 style="font-size:1.5rem; margin-bottom:0.5rem; font-weight:700;">${sessObj.task_name}</h2>
                <div style="font-size:0.9rem; margin-bottom:1rem; border:1px solid #fca5a5; background:rgba(252,165,165,0.1); padding:10px; border-radius:8px;">
                    <strong>Catatan Manager:</strong><br>
                    <i>${sessObj.manager_note || 'Harap submit ulang hasil kerja / bukti yang benar'}</i>
                </div>
                <button class="btn-big btn-stop" style="margin-top:1rem;" onclick="openStopModal('${sessObj.id}')">
                    <i data-lucide="upload"></i> Upload Revisi Laporan
                </button>
            `;
        } else {
            div.innerHTML = `
                <div style="opacity:0.8; margin-bottom:0.5rem; font-size:0.85rem; letter-spacing:0.05em;">SEDANG MENGERJAKAN</div>
                <h2 style="font-size:1.5rem; margin-bottom:1rem; font-weight:700;">${sessObj.task_name}</h2>
                <div class="timer" id="timer-${sessObj.id}">00:00:00</div>
                <div style="display:inline-flex; align-items:center; gap:8px; background:rgba(255,255,255,0.1); padding:6px 16px; border-radius:20px; font-size:0.85rem; margin-top:10px;">
                    <span style="width:8px; height:8px; background:#4ade80; border-radius:50%; box-shadow:0 0 10px #4ade80;"></span>
                    GPS Tracking Active
                </div>
                <button class="btn-big btn-stop" style="margin-top:2rem;" onclick="openStopModal('${sessObj.id}')">
                    <i data-lucide="camera"></i> Laporkan Hasil Kerja
                </button>
            `;
        }
        container.appendChild(div);
        if(window.lucide) lucide.createIcons();
    }

    function startSessionTimer(sessObj) {
        if (sessObj.timerInterval) clearInterval(sessObj.timerInterval);
        
        const timerEl = document.getElementById(`timer-${sessObj.id}`);
        
        sessObj.timerInterval = setInterval(() => {
            const now = new Date();
            const diff = now - sessObj.startTime;
            const h = Math.floor(diff / 3600000).toString().padStart(2, '0');
            const m = Math.floor((diff % 3600000) / 60000).toString().padStart(2, '0');
            const s = Math.floor((diff % 60000) / 1000).toString().padStart(2, '0');
            if(timerEl) timerEl.textContent = `${h}:${m}:${s}`;
        }, 1000);
    }

    function toggleActiveView(hasActive) {
        // If hasActive is true, we actully show BOTH lists? 
        // Request says: "Start Scheduled -> Time Running -> Click Start Conditional -> 2 counts".
        // This implies the "Idle/Input" forms must REMAIN VISIBLE or accessible even when working.
        
        // We will keep 'idleState' (Input Forms) always visible now, but maybe styled differently?
        // Or we just prepend active sessions above the inputs.
        
        const activeList = document.getElementById('activeSessionsList');
        // We don't hide 'idleState' anymore because user needs to add more tasks!
        // But maybe we want to visually separate them.
        
        // Just ensure the active list is visible if it has children
        if (activeSessions.length > 0) {
            activeList.classList.remove('hidden');
        } else {
            activeList.classList.add('hidden');
        }
    }

    // --- LOCATION & START WORK ---
    function getPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) reject('GPS not supported');
            navigator.geolocation.getCurrentPosition(resolve, reject, { enableHighAccuracy: true });
        });
    }

    // Start Scheduled
    window.startWork = function(btn) {
        const sel = document.getElementById('taskSelect');
        const taskName = sel.value; 
        if (!taskName) return alert("Pilih tugas terlebih dahulu!");
        
        initiateWorkSession(taskName, btn);
        // Reset selection
        sel.value = "";
    }
    
    // Start Conditional (Timer based now)
    window.startConditionalTimer = function(btn) {
        const input = document.getElementById('manualTaskInput');
        const taskName = input.value.trim();
        if (!taskName) return alert("Please fill out this field (Nama/Kategori Tugas)");
        
        initiateWorkSession(taskName, btn);
        // Reset Input
        input.value = "";
    }

    async function initiateWorkSession(taskName, btn) {
        // UX: Show loading
        const originalText = btn.innerHTML;
        const originalDisabled = btn.disabled;
        
        btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Meminta Lokasi...';
        btn.disabled = true;
        lucide.createIcons();

        try {
            const pos = await getPosition();
            const { latitude, longitude } = pos.coords;

            btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Memulai Sesi...';
            lucide.createIcons();

            const res = await fetch('php/tracking_api.php?action=start_work', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ task_name: taskName, lat: latitude, lng: longitude })
            }); 
            const json = await res.json();

            if (json.status === 'success') {
                const newSess = {
                    id: json.session_id,
                    task_name: taskName,
                    startTime: new Date(),
                    timerInterval: null
                };
                
                // Add to array
                activeSessions.push(newSess);
                
                // Render
                document.getElementById('activeSessionsList').classList.remove('hidden');
                createSessionCard(newSess);
                startSessionTimer(newSess);
                
                // Start GPS if not already
                if (!gpsInterval) startGpsTracking();

            } else {
                alert("Gagal: " + (json.message || "Unknown error"));
            }
        } catch (e) {
            console.error(e);
            alert("Gagal Mengakses Lokasi!\n\nPastikan:\n1. GPS Anda aktif.\n2. Anda mengizinkan browser mengakses lokasi.\n3. Coba refresh halaman jika pop-up tidak muncul.");
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
            lucide.createIcons();
        }
    }

    function startGpsTracking() {
        if (gpsInterval) clearInterval(gpsInterval);
        console.log("Starting GPS Tracking for sessions:", activeSessions.map(s=>s.id));
        
        gpsInterval = setInterval(async () => {
            if (activeSessions.length === 0) {
                stopGpsTracking();
                return;
            }
            
            try {
                const pos = await getPosition();
                
                // Send update for EACH active session
                // Parallel requests
                const promises = activeSessions.map(sess => {
                    return fetch('php/tracking_api.php?action=update_location', {
                        method: 'POST',
                        body: JSON.stringify({
                            session_id: sess.id,
                            lat: pos.coords.latitude,
                            lng: pos.coords.longitude
                        })
                    });
                });
                
                await Promise.all(promises);
                
            } catch (e) { console.error("GPS Sync failed"); }
        }, 60000); 
    }

    function stopGpsTracking() {
        if (gpsInterval) clearInterval(gpsInterval);
        gpsInterval = null;
    }
    
    // STOP MODAL
    let sessionToStop = null;
    window.openStopModal = function(sessionId) {
        sessionToStop = sessionId;
        document.getElementById('evidenceModal').classList.add('open');
        document.querySelector('#evidenceModal h2').textContent = "Laporkan Hasil Kerja";
    }

    window.closeStopModal = function() {
        document.getElementById('evidenceModal').classList.remove('open');
        sessionToStop = null;
    }

    document.getElementById('evidenceForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!sessionToStop) return;

        try {
            const formData = new FormData();
            const fileInput = document.getElementById('evidenceFile');
            const file = fileInput.files[0];
            
            if (file && file.size > 5 * 1024 * 1024) {
                alert("Ukuran file terlalu besar! Maksimal 5MB.");
                return;
            }

            // Normal Stop Flow
            const pos = await getPosition();
            
            formData.append('session_id', sessionToStop);
            formData.append('notes', document.getElementById('workNotes').value);
            formData.append('evidence', file);
            formData.append('lat', pos.coords.latitude);
            formData.append('lng', pos.coords.longitude);

            const res = await fetch('php/tracking_api.php?action=stop_work', { method: 'POST', body: formData });
            const json = await res.json();

            if (json.status === 'success') {
                alert("Laporan berhasil dikirim! Sesi selesai.");
                
                // Remove from local array & DOM
                const idx = activeSessions.findIndex(s => s.id == sessionToStop);
                if (idx !== -1) {
                    clearInterval(activeSessions[idx].timerInterval);
                    activeSessions.splice(idx, 1);
                }
                document.getElementById(`session-card-${sessionToStop}`).remove();
                
                if (activeSessions.length === 0) {
                    document.getElementById('activeSessionsList').classList.add('hidden');
                    stopGpsTracking();
                }

                closeStopModal();
                document.getElementById('evidenceForm').reset();
                
                // Refresh history if open
                if(!document.getElementById('view-history').classList.contains('hidden')) {
                   // renderHistoryTable(); // Not globally exposed? Need to check...
                   // It seems renderHistoryTable is defined as fetchHistoryTable in previous turn but here I don't see handle for it in view scope easily
                   // Just switch view to refresh if needed or let user do it
                }

            } else {
                alert("Gagal stop: " + json.message);
            }
        } catch (e) {
            alert("Error: " + e.message);
        }
    });

    window.logout = async function() {
        if(confirm("Keluar aplikasi?")) {
            await fetch('php/auth.php', { method: 'POST', body: JSON.stringify({ action: 'logout' }) });
            window.location.href = 'login.php';
        }
    }

    // --- VIEW SWITCHING ---
    window.switchView = function(viewName) {
        // 1. Sidebar Active State & Submenu Handling
        document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.sub-item').forEach(el => el.classList.remove('active'));

        // Dashboard
        if (viewName === 'dashboard') {
            document.getElementById('menu-dashboard').classList.add('active');
        } 
        // History (General Work)
        else if (viewName === 'history') {
            document.getElementById('menu-history').classList.add('active');
        }
        // Absensi Submenu
        else if (viewName === 'attendance') {
            const link = document.getElementById('menu-attendance');
            link.classList.add('active');
            // Ensure submenu open
            const sub = link.parentElement;
            if (sub && !sub.classList.contains('open')) {
                toggleSubmenu(sub.previousElementSibling);
            }
        }
        else if (viewName === 'attendance_history') {
            const link = document.getElementById('menu-attendance-history');
            link.classList.add('active');
            const sub = link.parentElement;
            if (sub && !sub.classList.contains('open')) {
                toggleSubmenu(sub.previousElementSibling);
            }
        }

        // 2. Toggle Content Sections
        const views = ['view-dashboard', 'view-history', 'view-attendance', 'view-attendance-history'];
        views.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.classList.add('hidden');
        });

        if (viewName === 'dashboard') document.getElementById('view-dashboard').classList.remove('hidden');
        if (viewName === 'history') {
            document.getElementById('view-history').classList.remove('hidden');
            loadHistory();
        }
        if (viewName === 'attendance') {
            document.getElementById('view-attendance').classList.remove('hidden');
            loadAttendanceStatus();
        }
        if (viewName === 'attendance_history') {
            document.getElementById('view-attendance-history').classList.remove('hidden');
            loadAttendanceHistory();
        }

        // 3. Mobile Sidebar Close
        const sidebar = document.querySelector('.sidebar');
        if (sidebar && sidebar.classList.contains('open')) toggleSidebar();
        
        lucide.createIcons();
    }

    // --- ABSENSI & SUBMENU LOGIC ---
    window.toggleSubmenu = function(el) {
        el.classList.toggle('open');
        const next = el.nextElementSibling;
        if(next && next.classList.contains('submenu')) {
            next.classList.toggle('open');
        }
        // Rotate arrow is handled by CSS .nav-item.open .arrow
    }

    async function loadAttendanceStatus() {
        try {
            const res = await fetch('php/tracking_api.php?action=get_attendance_today');
            const json = await res.json();
            
            const btnIn = document.getElementById('btn-clock-in');
            const btnOut = document.getElementById('btn-clock-out');
            const summary = document.getElementById('att-summary-text');
            const card = document.getElementById('attendance-status-card');

            // Reset Default State
            btnIn.disabled = false; btnIn.style.opacity = 1; btnIn.style.cursor = 'pointer';
            btnIn.innerHTML = `<i data-lucide="log-in" style="width: 32px; height: 32px;"></i><span>CLOCK IN</span><span style="font-size: 0.8rem; font-weight: 400; opacity: 0.9;">Masuk Kerja</span>`;
            
            btnOut.disabled = true; btnOut.style.opacity = 0.5; btnOut.style.cursor = 'not-allowed';
            btnOut.innerHTML = `<i data-lucide="log-out" style="width: 32px; height: 32px;"></i><span>CLOCK OUT</span><span style="font-size: 0.8rem; font-weight: 400; opacity: 0.9;">Pulang Kerja</span>`;
            
            card.classList.add('hidden');

            if (json.status === 'success') {
                const data = json.data;
                const inTime = data.clock_in_time.split(' ')[1].substring(0,5); 
                const outTime = data.clock_out_time ? data.clock_out_time.split(' ')[1].substring(0,5) : null;
                
                // --- IN STATUS ---
                // DB 'status' relies on the 08:15 logic. 
                // Late if > 08:15. OnTime if <= 08:15.
                const inStatusText = data.status === 'late' ? 'Terlambat' : 'Tepat Waktu';
                const inColor = data.status === 'late' ? '#dc2626' : '#16a34a';

                // Has Clocked In
                btnIn.disabled = true; btnIn.style.opacity = 0.5; btnIn.style.cursor = 'not-allowed';
                btnIn.innerHTML = `<i data-lucide="check-circle" style="width:32px; height:32px;"></i><span>SUDAH CLOCK IN</span><span style="font-size:0.8rem;">Pukul ${inTime}</span>`;
                
                if (!outTime) {
                    // --- BELUM CLOCK OUT ---
                    btnOut.disabled = false; btnOut.style.opacity = 1; btnOut.style.cursor = 'pointer';
                    
                    // Overnight session flag (Security Shift 2 — clock-in yesterday, clock-out today)
                    const isOvernight = data.is_overnight == true || data.is_overnight == 1;
                    const overnightBadge = isOvernight 
                        ? `<div style="margin-top:8px; font-size:0.8rem; background:#fef3c7; color:#92400e; border:1px solid #fcd34d; border-radius:8px; padding:5px 12px; display:inline-block;">🌙 Sesi Shift 2 (Semalam) — Silakan Clock Out Pagi Ini</div>`
                        : '';

                    // If overnight, update Clock In button text to reflect yesterday's check-in
                    if (isOvernight) {
                        btnIn.innerHTML = `<i data-lucide="check-circle" style="width:32px; height:32px;"></i><span>SHIFT 2 AKTIF</span><span style="font-size:0.8rem;">Masuk ${inTime} (Semalam)</span>`;
                    }

                    summary.innerHTML = `
                        <div style="font-size:1rem;">Clock In: <b>${inTime}</b> <span style="color:${inColor}; font-weight:700;">(${inStatusText})</span></div>
                        ${overnightBadge}
                    `;
                    card.classList.remove('hidden');
                } else {
                    // --- SUDAH CLOCK OUT ---
                    btnOut.disabled = true; btnOut.style.opacity = 0.5; btnOut.style.cursor = 'not-allowed';
                     btnOut.innerHTML = `<i data-lucide="check-circle" style="width:32px; height:32px;"></i><span>SUDAH CLOCK OUT</span><span style="font-size:0.8rem;">Pukul ${outTime}</span>`;
                     
                    // Logic Out Status: >= target_out_time Pulang, < target_out_time Pulang Cepat
                    const targetOut = data.target_out_time || "16:00";
                    let outStatusText = "Pulang Cepat";
                    let outColor = "#eab308"; // Yellow/Orange
                    if (outTime >= targetOut) {
                        outStatusText = "Pulang";
                        outColor = "#2563eb"; // Blue
                    }

                    summary.innerHTML = `
                        <div style="font-size:0.95rem; margin-bottom:4px;">Clock In: <b>${inTime}</b> <span style="color:${inColor}; font-weight:700;">(${inStatusText})</span></div>
                        <div style="font-size:0.95rem;">Clock Out: <b>${outTime}</b> <span style="color:${outColor}; font-weight:700;">(${outStatusText})</span></div>
                        <div style="font-size:0.8rem; color:#64748b; margin-top:5px;">Jadwal Pulang: ${targetOut}</div>
                    `;
                    card.classList.remove('hidden');
                }
            }
            lucide.createIcons();
        } catch(e) { console.error('Load Attendance Error:', e); }
    }

    // --- ATTENDANCE HANDLER ---
    window.handleAttendance = async function(type) {
        const btn = type === 'in' ? document.getElementById('btn-clock-in') : document.getElementById('btn-clock-out');
        const originalContent = btn.innerHTML;
        
        btn.innerHTML = '<i data-lucide="loader-2" class="spin"></i> Memproses...';
        btn.disabled = true;
        lucide.createIcons();

        try {
            const pos = await getPosition();
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            
            const workType = document.getElementById('workTypeSelect') ? document.getElementById('workTypeSelect').value : 'WFO';
            
            if (workType === 'WFO') {
                // SEAMEO BIOTROP Distance Check
                const targetLat = -6.635;
                const targetLng = 106.825;
                const maxDist = 0.5; // km
                
                const dist = getDistanceFromLatLonInKm(lat, lng, targetLat, targetLng);
                if (dist > maxDist) {
                    alert(`Anda berada di luar jangkauan lokasi WFO!\nJarak: ${dist.toFixed(2)} km.`);
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                    lucide.createIcons();
                    return;
                }
            }

            const action = type === 'in' ? 'clock_in' : 'clock_out';
            const res = await fetch(`php/tracking_api.php?action=${action}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ lat, lng, work_type: workType })
            });
            const json = await res.json();
            
            if (json.status === 'success') {
                if (type === 'in') {
                    // Tampilkan label shift hanya untuk Security
                    const isShiftWorker = json.shift_info && json.shift_info !== 'Regular Shift';
                    const shiftMsg = isShiftWorker ? ` (${json.shift_info})` : '';
                    if (json.status_att === 'late') {
                        alert(`Berhasil Clock In${shiftMsg} - Terlambat.`);
                    } else {
                        alert(`Berhasil Clock In${shiftMsg}!`);
                    }
                } else {
                    // Inform if clock-out was recorded on the next day (Shift 2 overnight)
                    const nextDayNote = json.is_next_day 
                        ? `\n✅ Clock Out tercatat di hari berikutnya (${json.clock_out_date}) sesuai Shift 2.` 
                        : '';
                    alert(`Berhasil Clock Out!${nextDayNote}`);
                }
                loadAttendanceStatus();
            } else {
                alert("Gagal: " + json.message);
                btn.innerHTML = originalContent;
                btn.disabled = false;
                lucide.createIcons();
            }

        } catch(e) {
            console.error(e);
            alert("Gagal mendapatkan lokasi: " + e);
            btn.innerHTML = originalContent;
            btn.disabled = false;
            lucide.createIcons();
        }
    }

    // Helper Calc Distance (Haversine)
    function getDistanceFromLatLonInKm(lat1,lon1,lat2,lon2) {
        var R = 6371; 
        var dLat = deg2rad(lat2-lat1);  
        var dLon = deg2rad(lon2-lon1); 
        var a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
            Math.sin(dLon/2) * Math.sin(dLon/2)
            ; 
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
        var d = R * c; 
        return d;
    }

    function deg2rad(deg) {
        return deg * (Math.PI/180)
    }

    // HISTORY ATTENDANCE
    async function loadAttendanceHistory() {
         const tbody = document.getElementById('attHistoryBody');
         tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:2rem;">Memuat data...</td></tr>';
         
         try {
             const res = await fetch('php/tracking_api.php?action=get_attendance_history');
             const json = await res.json();
             
             tbody.innerHTML = '';
             if (json.status === 'success' && json.data.length > 0) {
                 json.data.forEach(row => {
                     const dateObj = new Date(row.date);
                     const dateStr = dateObj.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
                     const inTime = row.clock_in_time ? row.clock_in_time.split(' ')[1].substring(0,5) : '-';
                     const outTime = row.clock_out_time ? row.clock_out_time.split(' ')[1].substring(0,5) : '-';
                     
                     let label = row.status === 'late' ? 'Terlambat' : 'Tepat Waktu';
                     
                     // Manual styling
                     const style = row.status === 'late' 
                        ? "background:#fee2e2; color:#dc2626; padding:4px 10px; border-radius:12px; font-weight:600; font-size:0.75rem;" 
                        : "background:#dcfce7; color:#16a34a; padding:4px 10px; border-radius:12px; font-weight:600; font-size:0.75rem;";
    
                     const tr = document.createElement('tr');
                     
                     tr.innerHTML = `
                        <td style="padding:12px;">
                            ${dateStr}
                        </td>
                        <td style="padding:12px;">${inTime}</td>
                        <td style="padding:12px;">${outTime}</td>
                        <td style="padding:12px;">
                            <span style="${style}">${label}</span>
                        </td>
                     `;
                     tbody.appendChild(tr);
                 });
             } else {
                 tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#94a3b8; padding:2rem;">Belum ada riwayat absensi.</td></tr>';
             }
         } catch(e) {
             console.error(e);
             tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:red; padding:2rem;">Gagal memuat data.</td></tr>';
         }
    }

    async function loadHistory() {
        const loading = document.getElementById('historyLoading');
        const empty = document.getElementById('historyEmpty');
        const tbody = document.getElementById('historyTableBody');

        tbody.innerHTML = '';
        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        
        lucide.createIcons(); 

        try {
            const res = await fetch('php/tracking_api.php?action=get_user_history');
            const json = await res.json();
            
            loading.classList.add('hidden');

            if (json.status === 'success') {
                allHistoryData = json.data;
                currentHistoryPage = 1; // Reset to page 1 on reload
                renderHistoryTable();
            } else {
                allHistoryData = [];
                renderHistoryTable();
            }
        } catch(e) { 
            console.error(e);
            loading.innerHTML = '<p class="text-red">Gagal memuat data.</p>';
        }
    }

    // Render History with Client-Side Pagination
    window.renderHistoryTable = function() {
        const tbody = document.getElementById('historyTableBody');
        const empty = document.getElementById('historyEmpty');
        const limitEl = document.getElementById('limit-history');
        const limit = limitEl ? parseInt(limitEl.value) : 10;
        
        tbody.innerHTML = '';

        if (allHistoryData.length === 0) {
            empty.classList.remove('hidden');
            updatePaginationControls(0, limit);
            return;
        }
        
        empty.classList.add('hidden');

        // Logic Pagination
        const totalItems = allHistoryData.length;
        const totalPages = Math.ceil(totalItems / limit);
        
        if (currentHistoryPage < 1) currentHistoryPage = 1;
        if (currentHistoryPage > totalPages) currentHistoryPage = totalPages;

        const startIndex = (currentHistoryPage - 1) * limit;
        const endIndex = startIndex + limit;
        const displayData = allHistoryData.slice(startIndex, endIndex);

        displayData.forEach(row => {
            const dateObj = new Date(row.start_time);
            const dateStr = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            const timeStart = dateObj.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' });
            const timeEnd = row.end_time ? new Date(row.end_time).toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit' }) : '-';
            
            let statusColor = '#64748b';
            let statusBg = '#f1f5f9';
            let statusText = row.status;

            if (row.status === 'active') {
                statusColor = '#16a34a'; statusBg = '#dcfce7'; statusText = 'Sedang Jalan';
            } else if (row.status === 'completed') {
                statusColor = '#1d4ed8'; statusBg = '#eff6ff'; statusText = 'Selesai';
            }

            // Evidence Logic
            let evidenceHtml = '-';
            if (row.file_path) {
                evidenceHtml = `<a href="${row.file_path}" target="_blank" style="color:#2563eb; text-decoration:underline;">Lihat Bukti</a>`;
            }
            
            // Add Note below evidence if exists
            if (row.note && row.note !== '-') {
                evidenceHtml += `<div style="font-size:0.75rem; color:#64748b; margin-top:4px;">${row.note}</div>`;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding:12px; color:#334155;">${dateStr}</td>
                <td style="padding:12px;">
                    <div style="color:#0f172a;">${row.task_name}</div>
                </td>
                <td style="padding:12px;">
                    ${evidenceHtml}
                </td>
                <td style="padding:12px; text-align:center; font-size:0.85rem;">
                    <div>${timeStart} - ${timeEnd}</div>
                    <div style="color:#64748b; font-size:0.75rem;">${row.duration_text}</div>
                </td>
                <td style="padding:12px; text-align:center;">
                    <span style="display:inline-block; padding:2px 10px; border-radius:12px; font-size:0.75rem; font-weight:600; background:${statusBg}; color:${statusColor};">
                        ${statusText}
                    </span>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        lucide.createIcons();
        updatePaginationControls(totalItems, limit);
    }

    function updatePaginationControls(totalItems, limit) {
        const infoEl = document.getElementById('pagination-info-history');
        const controlsEl = document.getElementById('pagination-controls-history');
        if(!infoEl || !controlsEl) return;

        if (totalItems === 0) {
            infoEl.textContent = "Menampilkan 0 - 0 dari 0 data";
            controlsEl.innerHTML = '';
            return;
        }

        const startItem = (currentHistoryPage - 1) * limit + 1;
        const endItem = Math.min(currentHistoryPage * limit, totalItems);
        infoEl.textContent = `Menampilkan ${startItem} - ${endItem} dari ${totalItems} data`;

        // Buttons
        const totalPages = Math.ceil(totalItems / limit);
        let html = '';

        // Prev
        const prevDis = currentHistoryPage === 1 ? 'disabled' : '';
        html += `<li class="page-item ${prevDis}"><a class="page-link" href="#" onclick="goToHistPage(${currentHistoryPage-1})">Sebelumnya</a></li>`;

        // Mobile friendly simple pagination: just show Start, Current, End if too many? 
        // Or simple: 1, ... , Current , ... , Last
        // For simplicity:
        for(let i=1; i<=totalPages; i++) {
             // Show only first, last, and around current to save space on mobile
             if (i === 1 || i === totalPages || (i >= currentHistoryPage - 1 && i <= currentHistoryPage + 1)) {
                const active = i === currentHistoryPage ? 'active' : '';
                html += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="goToHistPage(${i})">${i}</a></li>`;
             } else if (i === currentHistoryPage - 2 || i === currentHistoryPage + 2) {
                 html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
             }
        }

        // Next
        const nextDis = currentHistoryPage === totalPages ? 'disabled' : '';
        html += `<li class="page-item ${nextDis}"><a class="page-link" href="#" onclick="goToHistPage(${currentHistoryPage+1})">Selanjutnya</a></li>`;

        controlsEl.innerHTML = html;
    }

    window.goToHistPage = function(p) {
        const limit = parseInt(document.getElementById('limit-history').value);
        const totalPages = Math.ceil(allHistoryData.length / limit);
        
        if (p < 1 || p > totalPages) return;
        currentHistoryPage = p;
        renderHistoryTable();
    }

    // --- SOP MODAL ---
    window.openSopModal = function() {
        document.getElementById('sopModal').classList.add('open');
        // Close sidebar if on mobile
        const sidebar = document.querySelector('.sidebar');
        if (sidebar.classList.contains('open')) {
            toggleSidebar();
        }
    }

    window.closeSopModal = function() {
        document.getElementById('sopModal').classList.remove('open');
    }
</script>
</body>
</html>
