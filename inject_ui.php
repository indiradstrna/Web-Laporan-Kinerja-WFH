<?php
$file = 'superadmin.php';
$content = file_get_contents($file);

// 1. Insert Menu Khusus
$menuHtml = <<<HTML
                <p class="menu-label" style="margin-top: 1.5rem;">Menu Khusus</p>
                <nav class="nav-group">
                    <div class="nav-dropdown">
                        <a href="#" class="nav-item" onclick="document.getElementById('submenu-database').classList.toggle('hidden'); event.preventDefault();">
                            <i data-lucide="database"></i> Kelola Database <i data-lucide="chevron-down" style="margin-left:auto; width:16px;"></i>
                        </a>
                        <div id="submenu-database" class="hidden" style="padding-left: 1.5rem; display: flex; flex-direction: column; gap: 5px; margin-top: 5px;">
                            <a href="#crud-employee" class="nav-item" onclick="loadKelolaDatabaseTab()"><i data-lucide="users" style="width:14px;"></i> Data Karyawan</a>
                            <a href="#crud-attendance" class="nav-item" onclick="loadKelolaDatabaseTab()"><i data-lucide="clock" style="width:14px;"></i> Data Absensi</a>
                            <a href="#crud-worksession" class="nav-item" onclick="loadKelolaDatabaseTab()"><i data-lucide="briefcase" style="width:14px;"></i> Sesi Kerja</a>
                        </div>
                    </div>
                </nav>

                <p class="menu-label" style="margin-top: 1.5rem;">Lainnya</p>
HTML;

$content = str_replace('<p class="menu-label" style="margin-top: 1.5rem;">Lainnya</p>', $menuHtml, $content);

// 2. Insert View Sections
$viewsHtml = <<<HTML
                <!-- CRUD EMPLOYEES -->
                <div id="crud-employee" class="view-section hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Kelola Data Karyawan</h3>
                            <p class="card-sub">Edit profil dan info jabatan karyawan secara langsung.</p>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-striped">
                                    <thead><tr><th>Nama Lengkap</th><th>NIK/NIP</th><th>Departemen</th><th>Jabatan</th><th>Aksi</th></tr></thead>
                                    <tbody id="crud-employee-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CRUD ATTENDANCE -->
                <div id="crud-attendance" class="view-section hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Kelola Data Absensi</h3>
                            <p class="card-sub">Perbaiki waktu clock-in/out karyawan.</p>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-striped">
                                    <thead><tr><th>Nama</th><th>Tanggal</th><th>Clock In</th><th>Clock Out</th><th>Status</th><th>Aksi</th></tr></thead>
                                    <tbody id="crud-attendance-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CRUD WORK SESSIONS -->
                <div id="crud-worksession" class="view-section hidden">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Kelola Sesi Kerja</h3>
                            <p class="card-sub">Perbaiki waktu mulai/selesai atau status tugas.</p>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table table-striped">
                                    <thead><tr><th>Nama</th><th>Tugas</th><th>Waktu Mulai</th><th>Waktu Selesai</th><th>Status</th><th>Aksi</th></tr></thead>
                                    <tbody id="crud-worksession-tbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODALS -->
                <!-- Employee Modal -->
                <div id="modal-crud-employee" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
                    <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
                        <h3>Edit Data Karyawan</h3>
                        <form onsubmit="saveCRUDEmployee(event)">
                            <input type="hidden" id="crud-emp-id">
                            <label>Nama Lengkap</label><input type="text" id="crud-emp-name" class="form-input" style="width:100%; margin-bottom:10px; padding:8px;" required>
                            <label>NIK/NIP</label><input type="text" id="crud-emp-nik" class="form-input" style="width:100%; margin-bottom:10px; padding:8px;">
                            <label>Departemen</label><input type="text" id="crud-emp-dept" class="form-input" style="width:100%; margin-bottom:10px; padding:8px;">
                            <label>Jabatan</label><input type="text" id="crud-emp-role" class="form-input" style="width:100%; margin-bottom:10px; padding:8px;">
                            <label>Posisi</label><input type="text" id="crud-emp-pos" class="form-input" style="width:100%; margin-bottom:15px; padding:8px;">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-outline" onclick="closeCRUDModal('modal-crud-employee')">Batal</button>
                        </form>
                    </div>
                </div>
                <!-- Attendance Modal -->
                <div id="modal-crud-attendance" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
                    <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
                        <h3>Edit Absensi</h3>
                        <form onsubmit="saveCRUDAttendance(event)">
                            <input type="hidden" id="crud-att-id">
                            <label>Tanggal</label><input type="date" id="crud-att-date" class="form-input" style="width:100%; margin-bottom:10px; padding:8px;" required>
                            <label>Clock In (YYYY-MM-DD HH:MM:SS)</label><input type="text" id="crud-att-in" class="form-input" style="width:100%; margin-bottom:10px; padding:8px;" required>
                            <label>Clock Out</label><input type="text" id="crud-att-out" class="form-input" style="width:100%; margin-bottom:10px; padding:8px;">
                            <label>Status</label>
                            <select id="crud-att-status" class="form-input" style="width:100%; margin-bottom:15px; padding:8px;">
                                <option value="ontime">Ontime</option>
                                <option value="late">Late</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-outline" onclick="closeCRUDModal('modal-crud-attendance')">Batal</button>
                        </form>
                    </div>
                </div>
                <!-- Work Session Modal -->
                <div id="modal-crud-worksession" class="modal" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
                    <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; width:500px; border-radius:8px;">
                        <h3>Edit Sesi Kerja</h3>
                        <form onsubmit="saveCRUDWorkSession(event)">
                            <input type="hidden" id="crud-ws-id">
                            <label>Tugas</label><input type="text" id="crud-ws-task" class="form-input" style="width:100%; margin-bottom:10px; padding:8px;" required>
                            <label>Waktu Mulai</label><input type="text" id="crud-ws-start" class="form-input" style="width:100%; margin-bottom:10px; padding:8px;" required>
                            <label>Waktu Selesai</label><input type="text" id="crud-ws-end" class="form-input" style="width:100%; margin-bottom:10px; padding:8px;">
                            <label>Status</label>
                            <select id="crud-ws-status" class="form-input" style="width:100%; margin-bottom:15px; padding:8px;">
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="pending_approval">Pending Approval</option>
                                <option value="revise">Revise</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <button type="button" class="btn btn-outline" onclick="closeCRUDModal('modal-crud-worksession')">Batal</button>
                        </form>
                    </div>
                </div>

            </main>
HTML;

$content = str_replace('</main>', $viewsHtml, $content);

// 3. Insert JS Script
$jsHtml = <<<HTML
    <script src="js/admin_crud.js?v=<?= time() ?>"></script>
    <script src="js/app_pdf_export.js?v=<?= time() ?>"></script>
HTML;

$content = str_replace('<script src="js/app_pdf_export.js?v=<?= time() ?>"></script>', $jsHtml, $content);

file_put_contents($file, $content);
echo "Injected CRUD UI into superadmin.php";
?>
