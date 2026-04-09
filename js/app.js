/* js/app.js */

let appData = [];
let historyRawData = [];
let filteredData = [];
let charts = {};
let modalChartInstances = { bar: null, radar: null };
let deleteTargetId = null;

// --- VARIABEL BARU UNTUK MENYIMPAN ID SAAT EDIT ---
let currentEditId = null;

let monitoringData = [];
let currentDetailHistory = []; // Untuk menyimpan data history saat detail dibuka

let currentMasterPage = 1; // Master Pagination
let currentMonitorPage = 1; // Monitor Pagination
let currentHistoryPage = 1; // History Pagination (New)
let currentDetailData = null; // Store data currently displayed in detail modal for PDF printing
// --------------------------------------------------

const DIMENSIONS = [
    { key: "kinerja", label: "Kinerja & Produktivitas", questions: ["Mencapai target kerja (output/kuantitas) secara konsisten", "Kualitas hasil kerja sesuai standar", "Manajemen waktu & prioritas pekerjaan"] },
    { key: "kolaborasi", label: "Kolaborasi & Komunikasi", questions: ["Komunikasi yang jelas dan responsif", "Kontribusi tim berjalan efektif", "Menerima dan memberi umpan balik secara konstruktif"] },
    { key: "integritas", label: "Integritas & Kepatuhan", questions: ["Patuh SOP/kebijakan dan menjaga etika kerja", "Jujur, dapat menjaga kerahasiaan dan keamanan informasi", "Dapat dipercaya (akuntabel) terhadap tugas"] },
    { key: "inisiatif", label: "Inisiatif & Pemecahan Masalah", questions: ["Problem solving yang efektif", "Berfikir kritis dan memiliki ide inovasi", "Adaptif terhadap perubahan dan tantangan"] },
    { key: "kompetensi", label: "Kompetensi Teknis", questions: ["Penguasaan keahlian inti sesuai peran", "Ketelitian dan penerapan best practice", "Kemauan belajar & peningkatan kompetensi"] }
];

// Renamed helper to avoid conflict with jQuery
const qs = (s) => document.querySelector(s);
const formatDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const getPeriodLabel = (dStr) => {
    const d = new Date(dStr);
    return d.getDate() <= 15 ? 'Periode 1' : 'Periode 2';
};

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    loadDashboardStats();
    fetchData();
    fetchHistoryData();
    setupNavigation();
    renderQuestionnaire();

    if (qs('#date')) qs('#date').value = new Date().toISOString().split('T')[0];

    const filterStatus = document.getElementById('filter-status-master');
    if (filterStatus) {
        filterStatus.addEventListener('change', () => {
            currentMasterPage = 1; // Reset page on filter
            renderTable();
        });
    }

    const searchMasterInput = document.getElementById('search-master');
    if (searchMasterInput) {
        searchMasterInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                currentMasterPage = 1; // Reset page on search
                renderTable();
            }
        });
    }

    // Monitoring Filters
    if (qs('#monitorDate')) qs('#monitorDate').addEventListener('change', renderMonitoringTable);
    if (qs('#monitorSearch')) qs('#monitorSearch').addEventListener('input', renderMonitoringTable);
    if (qs('#monitorType')) qs('#monitorType').addEventListener('change', renderMonitoringTable);

    // Master Data Limit
    if (qs('#limit-master')) {
        qs('#limit-master').addEventListener('change', () => {
            currentMasterPage = 1;
            renderTable();
        });
    }

    // Monitor Data Limit
    if (qs('#limit-monitor')) {
        qs('#limit-monitor').addEventListener('change', () => {
            currentMonitorPage = 1;
            renderMonitoringTable();
        });
    }

    // --- History Page Event Listeners ---
    if (qs('#btn-filter-history')) {
        qs('#btn-filter-history').addEventListener('click', () => {
            currentHistoryPage = 1;
            renderHistoryTable();
        });
    }

    const searchHistoryInput = document.getElementById('search-history');
    if (searchHistoryInput) {
        searchHistoryInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                currentHistoryPage = 1;
                renderHistoryTable();
            }
        });
    }

    if (qs('#limit-history')) {
        qs('#limit-history').addEventListener('change', () => {
            currentHistoryPage = 1;
            renderHistoryTable();
        });
    }

    if (qs('#filter-period-header')) {
        qs('#filter-period-header').addEventListener('change', () => {
            currentHistoryPage = 1;
            renderHistoryTable();
        });
    }

    // Role / Jabatan Toggle
    const roleSelect = document.getElementById('role');
    const roleCustom = document.getElementById('role-custom');
    if (roleSelect && roleCustom) {
        roleSelect.addEventListener('change', function () {
            if (this.value === 'Lainnya') {
                roleCustom.style.display = 'block';
                roleCustom.required = true;
                roleCustom.focus();
            } else {
                roleCustom.style.display = 'none';
                roleCustom.required = false;
                roleCustom.value = '';
            }
        });
    }

    // Education Toggle
    const eduSelect = document.getElementById('education');
    const eduCustom = document.getElementById('education-custom');
    if (eduSelect && eduCustom) {
        eduSelect.addEventListener('change', function () {
            if (this.value === 'Lainnya') {
                eduCustom.style.display = 'block';
                eduCustom.required = true;
                eduCustom.focus();
            } else {
                eduCustom.style.display = 'none';
                eduCustom.required = false;
                eduCustom.value = '';
            }
        });
    }

    // ============================================
    // LOGIKA AUTOFILL & DETEKSI DATA LAMA
    // ============================================
    const nameInput = document.getElementById('name');
    if (nameInput) {
        nameInput.addEventListener('input', function (e) {
            const typedName = e.target.value.toLowerCase().trim();

            // Reset ID jika nama dikosongkan atau diubah pendek
            if (typedName.length < 3) {
                currentEditId = null;
                return;
            }

            // Cari data terakhir dari nama yang sama
            const found = appData
                .filter(row => row.profile.name.toLowerCase() === typedName)
                .sort((a, b) => new Date(b.date) - new Date(a.date))[0];

            if (found) {
                console.log("Data lama ditemukan, ID:", found.id);
                currentEditId = found.id;

                // Isi Data Profil
                if (qs('#empId')) qs('#empId').value = found.profile.empId || '';
                if (qs('#dept')) qs('#dept').value = found.profile.dept || '';
                // Handle Role / Jabatan
                const savedRole = found.profile.role || '';
                if (['Staff', 'SPV', 'Manager'].includes(savedRole)) {
                    if (qs('#role')) qs('#role').value = savedRole;
                    if (qs('#role-custom')) { qs('#role-custom').style.display = 'none'; qs('#role-custom').value = ''; }
                } else {
                    if (qs('#role')) qs('#role').value = 'Lainnya';
                    if (qs('#role-custom')) {
                        qs('#role-custom').style.display = 'block';
                        qs('#role-custom').value = savedRole;
                    }
                }
                if (qs('#level')) qs('#level').value = found.profile.level || '';
                if (qs('#position')) qs('#position').value = found.profile.position || '';

                // Handle Education
                const savedEdu = found.profile.education || '';
                if (['SMA/SMK', 'D3', 'S1', 'S2'].includes(savedEdu)) {
                    if (qs('#education')) qs('#education').value = savedEdu;
                    if (qs('#education-custom')) { qs('#education-custom').style.display = 'none'; qs('#education-custom').value = ''; }
                } else {
                    if (qs('#education')) qs('#education').value = 'Lainnya';
                    if (qs('#education-custom')) {
                        qs('#education-custom').style.display = 'block';
                        qs('#education-custom').value = savedEdu;
                    }
                }

                if (qs('#tenure')) qs('#tenure').value = found.profile.tenure || '';
                if (qs('#certificates')) qs('#certificates').value = found.profile.certificates || '';
                if (qs('#type')) qs('#type').value = found.profile.type || 'outsourcing';

                // --- UPDATE BAGIAN AUTOFILL TARGET KERJA ---
                if (found.targets) {
                    if (qs('#target3Months')) qs('#target3Months').value = found.targets.threeMonth || '';
                    if (qs('#target6Months')) qs('#target6Months').value = found.targets.sixMonth || '';
                    if (qs('#target1Year')) qs('#target1Year').value = found.targets.oneYear || '';
                }
                // -------------------------------------------

                // Isi Pilihan Kuesioner (Radio Button)
                if (found.rawAnswers) {
                    for (const [key, val] of Object.entries(found.rawAnswers)) {
                        const radio = document.querySelector(`input[name="${key}"][value="${val}"]`);
                        if (radio) radio.checked = true;
                    }
                }

                // Isi Extra Score
                if (found.extra_score) {
                    const extraRadio = document.querySelector(`input[name="extra_task"][value="${found.extra_score}"]`);
                    if (extraRadio) extraRadio.checked = true;
                }

                // Isi Catatan
                if (qs('#notes')) qs('#notes').value = found.notes || '';

            } else {
                // Jika nama tidak ditemukan, berarti data baru (Reset ID)
                currentEditId = null;
            }
        });
    }
});

// ============================================================
// DASHBOARD STATS (Operational)
// ============================================================

// Track badges already acknowledged — won't reappear until Refresh is clicked
const dismissedBadges = new Set();

async function loadDashboardStats(forceRefresh = false) {
    // If Refresh button clicked explicitly, reset dismissed badges
    if (forceRefresh) dismissedBadges.clear();

    try {
        const res = await fetch('php/tracking_api.php?action=admin_dashboard_stats');
        const json = await res.json();
        if (json.status !== 'success') return;
        const d = json.data;

        // Update stat cards
        const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        setEl('dash-total-emp',       d.total_employees);
        setEl('dash-active-sessions', d.active_sessions);
        setEl('dash-pending',         d.pending_approvals);
        setEl('dash-attendance',      d.attendance_today);

        // Late info on attendance card
        const lateDesc = document.getElementById('dash-late-desc');
        if (lateDesc) {
            lateDesc.textContent = d.late_today > 0
                ? `${d.late_today} Terlambat dari ${d.attendance_today} hadir`
                : 'Semua tepat waktu';
            lateDesc.style.color = d.late_today > 0 ? '#dc2626' : '#16a34a';
        }

        // Quick nav badges — skip dismissed ones (only show on forceRefresh or first load)
        const showBadge = (id, count) => {
            const el = document.getElementById(id);
            if (!el) return;
            if (dismissedBadges.has(id)) {
                el.style.display = 'none'; // Keep hidden if already dismissed
                return;
            }
            if (count > 0) { el.textContent = count; el.style.display = 'inline-block'; }
            else { el.style.display = 'none'; }
        };
        showBadge('qnav-pending', d.pending_approvals); // Monitoring
        showBadge('qnav-tasks',   d.tasks_today);        // Penjadwalan
        showBadge('qnav-att',     d.attendance_today);   // Absensi

        // Timestamp
        const ts = document.getElementById('dash-last-refresh');
        if (ts) {
            const now = new Date();
            ts.textContent = `Diperbarui: ${now.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})}`;
        }

        if (window.lucide) lucide.createIcons();
    } catch(e) {
        console.error('Dashboard stats error:', e);
    }
}

// Navigate programmatically (for Quick Nav cards)
function navigateTo(targetId) {
    // Map targetId → badge element to dismiss permanently until Refresh
    const badgeMap = {
        'monitoring':          'qnav-pending',
        'schedule':            'qnav-tasks',
        'riwayat-absensi-all': 'qnav-att'
    };
    const badgeId = badgeMap[targetId];
    if (badgeId) {
        dismissedBadges.add(badgeId);     // Mark as dismissed
        const badge = document.getElementById(badgeId);
        if (badge) badge.style.display = 'none'; // Hide immediately
    }

    const link = document.querySelector(`a[href="#${targetId}"]`);
    if (link) {
        link.click();
    } else {
        // Fallback: manual navigation
        document.querySelectorAll('.view-section').forEach(sec => sec.classList.add('hidden'));
        const target = document.getElementById(targetId);
        if (target) target.classList.remove('hidden');
        document.querySelectorAll('.nav-item').forEach(l => l.classList.remove('active'));

        if (targetId === 'riwayat-absensi-all') fetchAttAdminData();
        if (window.lucide) lucide.createIcons();
    }
}

// NAVIGATION
function setupNavigation() {
    const links = document.querySelectorAll('.nav-item');
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            if (link.getAttribute('href') === '#') return;
            e.preventDefault();
            links.forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            const targetId = link.getAttribute('href').substring(1);
            document.querySelectorAll('.view-section').forEach(sec => sec.classList.add('hidden'));
            document.getElementById(targetId).classList.remove('hidden');

            if (targetId === 'dashboard') {
                loadDashboardStats();
            } else if (targetId === 'data-master') {
                renderTable();
            } else if (targetId === 'riwayat-penilaian') {
                renderHistoryTable();
            } else if (targetId === 'riwayat-absensi-all') {
                fetchAttAdminData();
            } else if (targetId === 'form-input') {
                // Saat klik tab "Input Penilaian", reset form agar bersih (Mode Input Baru)
                resetFormState();
            }
        });
    });
}

function resetFormState() {
    qs('#kpiForm').reset();
    currentEditId = null;
    toggleProfileInputs(true); // Enable fields for new input

    // Clear visual states
    document.querySelectorAll('.radio-opt').forEach(opt => {
        opt.style.backgroundColor = 'white';
    });

    if (qs('#type')) qs('#type').value = 'outsourcing';

    // Hide custom inputs
    if (qs('#role-custom')) {
        qs('#role-custom').style.display = 'none';
        qs('#role-custom').required = false;
    }
    if (qs('#education-custom')) {
        qs('#education-custom').style.display = 'none';
        qs('#education-custom').required = false;
    }

    // Set Default Date
    if (qs('#date')) qs('#date').value = new Date().toISOString().split('T')[0];
}

function toggleProfileInputs(enable) {
    const fields = ['#name', '#empId', '#dept', '#role', '#role-custom', '#position', '#level', '#education', '#education-custom', '#tenure', '#certificates', '#type'];
    fields.forEach(sel => {
        const el = qs(sel);
        if (el) {
            el.disabled = !enable;
            // Visual feedback
            if (!enable) {
                el.style.backgroundColor = "#f1f5f9"; // Read-only look
                el.style.color = "#64748b";
                el.style.cursor = "not-allowed";
            } else {
                el.style.backgroundColor = "";
                el.style.color = "";
                el.style.cursor = "text";
            }
        }
    });

    // Handle Selects specifically if needed (though disabled works for them too)
    // Custom handling for 'Lainnya' toggles if disabled?
    // If disabled, we probably don't need to worry about the 'Lainnya' event listeners 
    // because user can't change the select value.
}

// --- FUNGSI RESET & RENDER ---
function renderQuestionnaire() {
    const container = qs('#questions-container');
    container.innerHTML = DIMENSIONS.map((dim, i) => `
        <div style="margin-bottom:1.5rem; border-bottom:1px dashed #e2e8f0; padding-bottom:1rem;">
            <h5 style="font-weight:700; color:#334155; margin-bottom:0.75rem;">${dim.label}</h5>
            <div style="display:grid; gap:0.75rem;">
                ${dim.questions.map((q, j) => `
                    <div style="display:flex; flex-direction:column; gap:0.5rem;">
                        <label style="font-weight:400; font-size:0.9rem;">${j + 1}. ${q}</label>
                        <div class="radio-group">
                            ${[1, 2, 3, 4, 5].map(val => `
                                <label class="radio-opt">
                                    <input type="radio" name="${dim.key}_${j}" value="${val}" style="opacity:0; position:absolute;" required>
                                    ${val}
                                </label>
                            `).join('')}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');

    // Attach Event Listeners for NEW Input Styling
    document.querySelectorAll('.radio-group input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function () {
            // Reset Siblings (in this group)
            const group = this.closest('.radio-group');
            if (group) {
                group.querySelectorAll('.radio-opt').forEach(opt => {
                    // Jika elemen ini BUKAN elemen yang diklik
                    if (opt !== this.parentElement) {
                        // Cek apakah ini adalah Original Selection (History)
                        if (opt.classList.contains('original-selection')) {
                            // Biarkan visual "history" tetap ada
                            opt.style.backgroundColor = '#c7e9c0';
                            opt.style.color = '#0f172a';
                        } else {
                            // Reset ke putih biasa
                            opt.style.backgroundColor = 'white';
                            opt.style.color = '#64748b';
                        }
                    }
                });
            }

            // Set Active Style (New/Clicked Value)
            const label = this.parentElement;
            if (label && label.classList.contains('radio-opt')) {
                // Style Active (User Selection)
                label.style.backgroundColor = '#238b45'; // Green New
                label.style.color = 'white';
            }
        });
    });
}

// API CALLS
async function fetchData() {
    try {
        const res = await fetch('php/api.php');
        const text = await res.text();
        try {
            appData = JSON.parse(text);
            populateFilters();
            processDataToDashboard();
        } catch (e) {
            console.error("Invalid JSON:", text);
        }
    } catch (e) { console.error(e); }
}

async function saveData(payload) {
    try {
        // Pastikan ID dikirim jika ada (Mode Edit), atau null jika baru
        const res = await fetch('php/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const text = await res.text();

        try {
            const json = JSON.parse(text);
            if (res.ok && json.status === 'success') {
                return json;
            } else {
                throw new Error(json.message || "Gagal menyimpan");
            }
        } catch (jsonError) {
            console.error("Server Response (Not JSON):", text);
            throw new Error("Server Error: " + text);
        }
    } catch (e) {
        alert("Gagal Menyimpan: " + e.message);
        return { status: "error", message: e.message };
    }
}

async function deleteData(id) {
    try {
        const res = await fetch(`php/api.php?id=${id}`, { method: 'DELETE' });
        return await res.json();
    } catch (e) {
        return { status: "error", message: "Delete failed" };
    }
}

// FILTER & DASHBOARD
function populateFilters() {
    const deptSelect = qs('#filter-dept');
    const roleSelect = qs('#filter-role');

    // Reset options but keep the first one "Semua Departemen"
    deptSelect.innerHTML = '<option value="">Semua Departemen</option>';
    roleSelect.innerHTML = '<option value="">Semua Jabatan</option>';

    if (!appData || appData.length === 0) return;

    // Extract unique values
    const depts = [...new Set(appData.map(item => item.profile.dept))].filter(Boolean).sort();
    const roles = [...new Set(appData.map(item => item.profile.role))].filter(Boolean).sort();

    depts.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d;
        opt.textContent = d;
        deptSelect.appendChild(opt);
    });

    roles.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r;
        opt.textContent = r;
        roleSelect.appendChild(opt);
    });

    deptSelect.onchange = processDataToDashboard;
    roleSelect.onchange = processDataToDashboard;
    if (qs('#search-name')) qs('#search-name').oninput = processDataToDashboard;
}

function processDataToDashboard() {
    const filterDept = qs('#filter-dept').value;
    const filterRole = qs('#filter-role').value;
    const searchTerm = qs('#search-name') ? qs('#search-name').value.toLowerCase() : '';

    filteredData = appData.filter(item => {
        const matchDept = filterDept ? item.profile.dept === filterDept : true;
        const matchRole = filterRole ? item.profile.role === filterRole : true;
        const matchName = searchTerm ? item.profile.name.toLowerCase().includes(searchTerm) : true;
        return matchDept && matchRole && matchName;
    });

    const total = filteredData.length;
    const avgScore = total > 0 ? (filteredData.reduce((a, b) => a + parseFloat(b.total.avg), 0) / total).toFixed(2) : 0;

    qs('#kpi-total').textContent = total;
    qs('#kpi-avg').textContent = avgScore;

    // Update KPI High/Low
    const high = filteredData.filter(x => x.total.avg >= 4).length;
    const low = filteredData.filter(x => x.total.avg < 3).length;
    qs('#kpi-high').textContent = high;
    qs('#kpi-low').textContent = low;

    updateCharts();
}

// FORM SUBMIT (UPDATED)
qs('#kpiForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const name = qs('#name').value;

    // Kumpulkan Skor
    let dimScores = {};
    let rawAnswers = {};
    let totalSum = 0; let totalCount = 0;
    let allFilled = true;

    DIMENSIONS.forEach(dim => {
        let sum = 0;
        dim.questions.forEach((_, i) => {
            const qKey = `${dim.key}_${i}`;
            const el = document.querySelector(`input[name="${qKey}"]:checked`);
            if (!el) {
                allFilled = false;
            } else {
                const val = parseInt(el.value);
                sum += val;
                rawAnswers[qKey] = val;
            }
        });
        const avg = sum / dim.questions.length;
        dimScores[dim.key] = { avg, sum, count: dim.questions.length };
        totalSum += sum;
        totalCount += dim.questions.length;
    });

    if (!allFilled) { alert("Lengkapi semua pertanyaan kuesioner!"); return; }

    const payload = {
        id: currentEditId,
        date: qs('#date').value,
        profile: {
            name, empId: qs('#empId').value, dept: qs('#dept').value,
            role: (qs('#role').value === 'Lainnya' ? qs('#role-custom').value : qs('#role').value),
            position: qs('#position').value,
            level: qs('#level') ? qs('#level').value : '',
            education: (qs('#education').value === 'Lainnya' ? qs('#education-custom').value : qs('#education').value),
            tenure: qs('#tenure').value, certificates: qs('#certificates').value,
            type: qs('#type').value
        },

        // --- BAGIAN INI DIUPDATE ---
        // --- BAGIAN INI DIUPDATE ---
        targets: {
            threeMonth: qs('#target3Months').value,
            sixMonth: qs('#target6Months').value,
            oneYear: qs('#target1Year').value
        },
        // ---------------------------

        extra_score: document.querySelector('input[name="extra_task"]:checked') ? document.querySelector('input[name="extra_task"]:checked').value : 0,

        scores: dimScores,
        rawAnswers: rawAnswers,
        total: { avg: totalSum / totalCount },
        notes: qs('#notes').value
    };

    const res = await saveData(payload);

    if (res.status === 'success') {
        if (res.credentials) {
            alert(`Pegawai Baru Ditambahkan!\n\nAkun Login Otomatis:\nUsername: ${res.credentials.username}\nPassword: ${res.credentials.password}\n\nMohon dicatat atau screenshoot sebelum menutup!`);
        } else {
            alert(res.message);
        }
        qs('#kpiForm').reset();
        currentEditId = null; // Reset ID Edit setelah simpan

        document.querySelectorAll('.radio-opt').forEach(opt => {
            opt.style.backgroundColor = 'white';
            const input = opt.querySelector('input');
            if (input) input.checked = false;
        });
        fetchData();
        document.querySelector('a[href="#dashboard"]').click();
    }
});

// CHARTS
function updateCharts() {
    const labels = DIMENSIONS.map(d => d.label);

    // Data Aggregate is missing breakdown in Main Table logic now.
    // We will show empty charts or skip updating them if data missing.
    // Filtered data rows now lack 'scores' object.

    // Check if we have data to chart
    // We could either disable charts or just show 0
    // Or we could fetch component averages from API as well, but that requires more SQL work.
    // Given the request "Dashboard hanya menampilkan berdasarkan list employee", charts of specific "Scores" might be undefined for aggregated rows.

    // Hitung rata-rata per dimensi dari data yang difilter (filteredData)
    // API sekarang mengirimkan object 'scores' yang berisi rata-rata per dimensi untuk setiap pegawai.

    let dataPoints = [];

    if (!filteredData || filteredData.length === 0) {
        dataPoints = DIMENSIONS.map(() => 0);
    } else {
        // Inisialisasi sum & count
        const sums = {};
        const counts = {};
        DIMENSIONS.forEach(d => {
            sums[d.key] = 0;
            counts[d.key] = 0;
        });

        // Loop Filtered Data
        filteredData.forEach(item => {
            if (item.scores) {
                DIMENSIONS.forEach(d => {
                    const k = d.key;
                    if (item.scores[k] && item.scores[k].avg !== undefined) {
                        sums[k] += parseFloat(item.scores[k].avg);
                        counts[k]++;
                    }
                });
            }
        });

        // Hitung Average Final
        dataPoints = DIMENSIONS.map(d => {
            const k = d.key;
            // Gunakan count yang valid (jika ada data skor di dimensi tsb)
            return counts[k] > 0 ? (sums[k] / counts[k]).toFixed(2) : 0;
        });
    }

    const ctxBar = document.getElementById('barChart');
    if (ctxBar) {
        if (charts.bar) charts.bar.destroy();
        charts.bar = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{ label: 'Skor', data: dataPoints, backgroundColor: '#16a34a', borderRadius: 4 }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 5 } }, plugins: { legend: { display: false } } }
        });
    }

    const ctxRadar = document.getElementById('radarChart');
    if (charts.radar) charts.radar.destroy();
    charts.radar = new Chart(ctxRadar, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{ label: 'Kompetensi', data: dataPoints, backgroundColor: 'rgba(22, 163, 74, 0.2)', borderColor: '#16a34a' }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { r: { min: 0, max: 5 } } }
    });
}

// RENDER TABLE
function renderTable() {
    const tbody = qs('#tableBody');
    tbody.innerHTML = '';

    // Ambil nilai filter & search & view-mode
    const statusFilter = qs('#filter-status-master') ? qs('#filter-status-master').value : '';
    const searchMasterVal = qs('#search-master') ? qs('#search-master').value.toLowerCase().trim() : '';
    const limit = qs('#limit-master') ? parseInt(qs('#limit-master').value) : 10;
    const viewMode = qs('#view-mode') ? qs('#view-mode').value : 'detail';

    // 1. Filter Raw Data First
    let filteredRaw = appData.filter(row => {
        let status = 'Moderate';
        if (row.total.avg >= 4) status = 'High';
        if (row.total.avg < 3) status = 'Low';
        const matchStatus = statusFilter ? status === statusFilter : true;

        // Safety check for profile name
        const name = row.profile && row.profile.name ? row.profile.name.toLowerCase() : '';
        const matchSearch = searchMasterVal ? name.includes(searchMasterVal) : true;

        return matchStatus && matchSearch;
    });

    let dataToRender = [];

    // 2. Handle Modes
    if (viewMode === 'monthly') {
        // Group by EmpID + Month
        const groups = {};
        filteredRaw.forEach(item => {
            const d = new Date(item.date);
            const key = `${item.profile.empId || item.profile.name}_${d.getFullYear()}_${d.getMonth()}`; // Fallback to name if ID missing

            if (!groups[key]) {
                groups[key] = {
                    id: `group|${key}`, // Special ID for grouping
                    date: item.date, // Will use for Month name
                    profile: item.profile,
                    totalSum: 0,
                    count: 0,
                    items: []
                };
            }
            groups[key].items.push(item);
            groups[key].totalSum += parseFloat(item.total.avg);
            groups[key].count++;
        });

        // Convert back to array & Calc Avg
        dataToRender = Object.values(groups).map(g => {
            return {
                ...g,
                total: { avg: g.totalSum / g.count }, // Calculated Monthly Avg
                isGroup: true
            };
        });

    } else {
        // Detail Mode (Normal)
        dataToRender = filteredRaw;
    }

    if (dataToRender.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:2rem; color:#94a3b8;">Tidak ada data ditemukan.</td></tr>';
        return;
    }

    // Sort Descending Date
    dataToRender.sort((a, b) => new Date(b.date) - new Date(a.date));

    // Validasi limit jika NaN
    const safeLimit = isNaN(limit) ? 10 : limit;

    // Pagination Logic
    const totalItems = dataToRender.length;
    const totalPages = Math.ceil(totalItems / safeLimit);

    // Ensure currentPage is valid
    if (currentMasterPage < 1) currentMasterPage = 1;
    if (currentMasterPage > totalPages && totalPages > 0) currentMasterPage = totalPages;

    const startIndex = (currentMasterPage - 1) * safeLimit;
    const endIndex = startIndex + safeLimit;

    // Slice data sesuai pagination
    const displayData = dataToRender.slice(startIndex, endIndex);

    // Render Pagination Controls & Info
    renderPagination(totalItems, safeLimit, currentMasterPage, totalPages, 'pagination-info-master', 'pagination-controls-master', 'goToMasterPage');

    displayData.forEach(row => {
        let badgeClass = 'bg-yellow'; let text = 'Moderate';
        const avg = parseFloat(row.total.avg || 0);
        if (avg >= 4) { badgeClass = 'bg-green'; text = 'High'; }
        if (avg < 3) { badgeClass = 'bg-red'; text = 'Low'; }

        // Logic Security MAC
        const isBound = row.profile.mac && row.profile.mac !== 'unknown';
        const securityHtml = isBound ?
            `<span class="badge bg-green" title="${row.profile.mac}"><i data-lucide="shield-check" style="width:14px; height:14px;"></i> ACTIVE</span>` :
            `<span class="badge bg-yellow"><i data-lucide="shield-alert" style="width:14px; height:14px;"></i> UNBOUND</span>`;

        // Action Buttons
        let actions = `
            <button class="btn btn-outline btn-sm" onclick="editData('${row.id}')" title="Input Penilaian Baru">
                <i data-lucide="pencil" style="width:14px; color:#f59e0b;"></i>
            </button>
            <button class="btn btn-outline btn-sm" onclick="viewDetail('${row.id}', 'master')" title="Lihat History">
                <i data-lucide="eye" style="width:14px; color:#3b82f6;"></i>
            </button>
        `;

        if (isBound) {
            actions += `
            <button class="btn btn-outline btn-sm" onclick="resetMac('${row.profile.name}')" title="Reset Security MAC">
                <i data-lucide="rotate-ccw" style="width:14px; color:#ef4444;"></i>
            </button>`;
        }

        tbody.innerHTML += `
            <tr>
                <td style="font-weight:500;">${row.profile.name}</td>
                <td>${row.profile.dept}</td>
                <td>${row.profile.role}</td>
                <td>${row.profile.type || '-'}</td>
                <td style="font-weight:700;">${avg.toFixed(2)}</td>
                <td><span class="badge ${badgeClass}">${text}</span></td>
                <td>${securityHtml}</td>
                <td style="text-align:right; white-space: nowrap;">
                    ${actions}
                </td>
            </tr>
        `;
    });


    if (window.lucide) lucide.createIcons();
}

// --- FETCH HISTORY DATA (RAW) ---
async function fetchHistoryData() {
    try {
        const res = await fetch('php/api.php?action=get_all_assessments');
        const json = await res.json();
        if (json.status === 'success') {
            historyRawData = json.data;
        } else {
            console.error("Failed to fetch history:", json.message);
        }
    } catch (e) {
        console.error("Error fetching history:", e);
    }
}

// --- RENDER HISTORY TABLE (AGGREGATED) ---
function renderHistoryTable() {
    const tbody = qs('#tableBodyHistory');
    if (!tbody) return;
    tbody.innerHTML = '';

    const startFilter = qs('#history-start') ? qs('#history-start').value : '';
    const endFilter = qs('#history-end') ? qs('#history-end').value : '';
    const searchVal = qs('#search-history') ? qs('#search-history').value.toLowerCase().trim() : '';
    const periodFilter = qs('#filter-period-header') ? qs('#filter-period-header').value : '';
    const limit = qs('#limit-history') ? parseInt(qs('#limit-history').value) : 10;

    // 0. Pre-Calculate Cumulative Averages (Running Average)
    // We must do this on the FULL dataset sorted by Date ASC before filtering
    let allSorted = [...historyRawData].sort((a, b) => new Date(a.assessment_date) - new Date(b.assessment_date));
    const empStats = {}; // { emp_id: { sum: 0, count: 0 } }

    // Create enriched data with cumulative_avg
    const enrichedData = allSorted.map(item => {
        if (!empStats[item.emp_id]) {
            empStats[item.emp_id] = { sum: 0, count: 0 };
        }

        const currentScore = parseFloat(item.total_score || 0);
        empStats[item.emp_id].sum += currentScore;
        empStats[item.emp_id].count += 1;

        const runningAvg = empStats[item.emp_id].sum / empStats[item.emp_id].count;

        return {
            ...item,
            _cumulative_avg: runningAvg
        };
    });

    // 1. Filter Enriched Data
    let filteredItems = enrichedData.filter(item => {
        // Date Logic (Compare YYYY-MM Strings)
        const itemMonth = item.assessment_date.substring(0, 7); // YYYY-MM
        let matchDate = true;

        if (startFilter && itemMonth < startFilter) {
            matchDate = false;
        }
        if (endFilter && itemMonth > endFilter) {
            matchDate = false;
        }

        // Search Logic
        const name = item.full_name ? item.full_name.toLowerCase() : '';
        const matchSearch = searchVal ? name.includes(searchVal) : true;

        // Period Filter
        let matchPeriod = true;
        if (periodFilter) {
            const pLabel = getPeriodLabel(item.assessment_date);
            if (pLabel !== periodFilter) matchPeriod = false;
        }

        return matchDate && matchSearch && matchPeriod;
    });

    if (filteredItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:2rem; color:#94a3b8;">Tidak ada data ditemukan.</td></tr>';
        return;
    }

    // 2. NO GROUPING - Map directly to rows
    let dataToRender = filteredItems.map(item => {
        const d = new Date(item.assessment_date);

        // Format Periode: dd mm yyyy Periode x
        const day = d.getDate();
        const month = d.getMonth() + 1;
        const year = d.getFullYear();
        const pLabel = day <= 15 ? "Periode 1" : "Periode 2";
        const dd = day < 10 ? '0' + day : day;
        const mm = month < 10 ? '0' + month : month;
        const periodDisplay = `${dd} ${mm} ${year} ${pLabel}`;

        // Format Periode Skor: Month: Score (Individual Score)
        const mStr = d.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' });
        const individualScore = parseFloat(item.total_score || 0);
        const periodScoreDisplay = `<span style="white-space:nowrap;">${mStr}: <strong>${individualScore.toFixed(2)}</strong></span>`;

        // Total Score uses the Cumulative Average
        const totalScoreVal = item._cumulative_avg;

        return {
            id: item.emp_id, // Use Employee ID for View Detail Action
            assessmentId: item.id, // ID Asessment Spesifik
            date: d,
            name: item.full_name,
            dept: item.department,
            role: item.role_title,
            filterPeriod: periodDisplay,
            actualPeriod: periodScoreDisplay,
            score: totalScoreVal
        };
    });

    // Sort by Date Descending
    dataToRender.sort((a, b) => b.date - a.date);

    // Validasi limit jika NaN
    const safeLimit = isNaN(limit) ? 10 : limit;

    // Pagination Logic
    const totalItems = dataToRender.length;
    const totalPages = Math.ceil(totalItems / safeLimit);

    if (currentHistoryPage < 1) currentHistoryPage = 1;
    if (currentHistoryPage > totalPages && totalPages > 0) currentHistoryPage = totalPages;

    const startIndex = (currentHistoryPage - 1) * safeLimit;
    const endIndex = startIndex + safeLimit;
    const displayData = dataToRender.slice(startIndex, endIndex);

    renderPagination(totalItems, safeLimit, currentHistoryPage, totalPages, 'pagination-info-history', 'pagination-controls-history', 'goToHistoryPage');

    displayData.forEach(row => {
        let badgeClass = 'bg-yellow'; let text = 'Moderate';
        const avg = parseFloat(row.score);
        if (avg >= 4) { badgeClass = 'bg-green'; text = 'High'; }
        if (avg < 3) { badgeClass = 'bg-red'; text = 'Low'; }

        // Action Buttons
        let actions = `
            <button class="btn btn-outline btn-sm" onclick="viewDetail('${row.id}', 'history', '${row.assessmentId}')" title="Lihat Detail">
                <i data-lucide="eye" style="width:14px; color:#3b82f6;"></i>
            </button>
        `;

        tbody.innerHTML += `
            <tr>
                <td style="font-weight:600;">${formatDate(row.date)}</td>
                <td>${row.name}</td>
                <td>${row.dept}</td>
                <td>${row.role}</td>
                <td style="font-size:0.85rem; color:#475569;">${row.filterPeriod}</td>
                <td style="font-size:0.85rem; color:#0f172a; font-weight:500;">${row.actualPeriod}</td>
                <td style="font-weight:700;">${avg.toFixed(2)}</td>
                <td style="text-align:right; white-space: nowrap;">
                    ${actions}
                </td>
            </tr>
        `;
    });

    if (window.lucide) lucide.createIcons();
}

window.goToHistoryPage = function (p) {
    if (p < 1) return;
    currentHistoryPage = p;
    renderHistoryTable();
}

// --- FUNGSI PAGINATION ---
function renderPagination(totalItems, limit, page, totalPages, infoId, controlsId, clickFnName) {
    const infoEl = document.getElementById(infoId);
    const controlsEl = document.getElementById(controlsId);

    if (!infoEl || !controlsEl) return;

    // 1. Update Info Text
    if (totalItems === 0) {
        infoEl.textContent = "Menampilkan 0 sampai 0 dari 0 entri";
        controlsEl.innerHTML = '';
        return;
    }

    const startItem = (page - 1) * limit + 1;
    const endItem = Math.min(page * limit, totalItems);
    // Format number with dots (e.g. 67.584)
    const fmt = (n) => n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");

    infoEl.textContent = `Menampilkan ${fmt(startItem)} sampai ${fmt(endItem)} dari ${fmt(totalItems)} entri`;

    // 2. Generate Buttons
    let html = '';

    // Button Helper
    const createBtn = (label, targetPage, isActive = false, isDisabled = false, isText = false) => {
        const activeStyle = isActive ? 'background-color: #0d6efd; color: white; border-color: #0d6efd;' : 'color: #0d6efd; cursor:pointer;';
        const disabledStyle = isDisabled ? 'color: #6c757d; pointer-events: none; background-color: #e9ecef;' : '';

        if (isText) return `<li class="page-item disabled"><span class="page-link" style="border:none; background:transparent;">...</span></li>`;

        return `
            <li class="page-item ${isActive ? 'active' : ''} ${isDisabled ? 'disabled' : ''}">
                <a class="page-link" onclick="${clickFnName}(${targetPage})" style="${activeStyle} ${disabledStyle}">
                    ${label}
                </a>
            </li>
        `;
    };

    // "Pertama" & "Sebelumnya"
    html += createBtn('Pertama', 1, false, page === 1);
    html += createBtn('Sebelumnya', page - 1, false, page === 1);

    // Number Buttons Logic (Smart Window)
    html += createBtn('1', 1, 1 === page);

    if (page > 4) {
        html += createBtn('...', 0, false, true, true);
    }

    let startWin = Math.max(2, page - 2);
    let endWin = Math.min(totalPages - 1, page + 2);

    for (let i = startWin; i <= endWin; i++) {
        if (i === 1 || i === totalPages) continue; // Already handled
        html += createBtn(i, i, i === page);
    }

    if (page < totalPages - 3) {
        html += createBtn('...', 0, false, true, true);
    }

    if (totalPages > 1) {
        html += createBtn(totalPages, totalPages, totalPages === page);
    }

    // "Selanjutnya" & "Terakhir"
    html += createBtn('Selanjutnya', page + 1, false, page === totalPages);
    html += createBtn('Terakhir', totalPages, false, page === totalPages);

    controlsEl.innerHTML = html;
}

window.goToMasterPage = function (p) {
    if (p < 1) return;
    currentMasterPage = p;
    renderTable();
}

window.goToMonitorPage = function (p) {
    if (p < 1) return;
    currentMonitorPage = p;
    renderMonitoringTable();
}

async function viewDetail(id, source = 'master', specificId = null) {
    // 1. Fetch History Data via API
    try {
        // 'id' passed here is actually the Employee ID now
        const res = await fetch(`php/api.php?action=get_history&id=${id}`);
        const json = await res.json();

        if (json.status === 'success') {
            currentDetailHistory = json.data;
        } else {
            console.error("Gagal ambil history:", json.message);
            // Fallback to single data from local memory if API fails
            const fallback = appData.find(item => item.id == id); // This fallback might not be ideal for history
            currentDetailHistory = fallback ? [fallback] : [];
        }
    } catch (e) {
        console.error("Network Error:", e);
        return;
    }

    if (currentDetailHistory.length === 0) {
        alert("Belum ada riwayat penilaian untuk pegawai ini.");
        return;
    }

    // Use the first record (latest) for static profile info
    const latestData = currentDetailHistory[0];

    // 2. Setup Modal Structure
    const modalContent = document.getElementById('modalContent');
    modalContent.innerHTML = `
        <div class="detail-layout">
            <div class="left-col">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <div class="section-title" style="margin-bottom:0;">Profil Pegawai</div>
                    <div>
                        <select id="detailPeriodSelect" style="padding:4px 8px; border-radius:4px; font-size:0.8rem; border:1px solid #cbd5e1; background:#f8fafc;">
                            <option value="cumulative">Akumulasi (Semua Periode)</option>
                            ${currentDetailHistory.map((item, idx) => `
                                <option value="${idx}">${formatDate(item.date)} (${getPeriodLabel(item.date)})</option>
                            `).join('')}
                        </select>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom:0.5rem;">
                    <div><div class="detail-label">Nama</div><div class="detail-value">${latestData.profile.name}</div></div>
                    <div><div class="detail-label">NIK</div><div class="detail-value">${latestData.profile.empId || '-'}</div></div>
                </div>
                <div class="form-row" style="margin-bottom:0.5rem;">
                    <div><div class="detail-label">Departemen</div><div class="detail-value">${latestData.profile.dept}</div></div>
                    <div><div class="detail-label">Jabatan</div><div class="detail-value">${latestData.profile.role}</div></div>
                </div>
                <div class="form-row" style="margin-bottom:0.5rem;">
                    <div><div class="detail-label">Posisi / Bidang</div><div class="detail-value">${latestData.profile.position || '-'}</div></div>
                    <div><div class="detail-label">Pendidikan</div><div class="detail-value">${latestData.profile.education || '-'}</div></div>
                </div>
                <div class="form-row" style="margin-bottom:1rem;">
                    <div><div class="detail-label">Lama Kerja</div><div class="detail-value">${latestData.profile.tenure || '-'}</div></div>
                    <div>&nbsp;</div>
                </div>
                <div style="margin-bottom:1.5rem;"><div class="detail-label">Sertifikat</div><div class="detail-value">${latestData.profile.certificates || '-'}</div></div>

                <div class="section-title">Evidence Target Kerja</div>
                <div id="targetContainer"></div> <!-- Dynamic Content -->

                <div class="section-title">Catatan / Feedback</div>
                <div id="noteContainer" style="font-size:0.85rem; color:#334155; font-style:italic; line-height:1.5; margin-bottom: 1rem;"></div>

                <div class="section-title">Nilai Tugas Tambahan</div>
                <div id="extraScoreContainer" style="font-size:1.1rem; font-weight:700; color:#0f172a; margin-bottom: 1rem;"></div>

                <div class="section-title">Informasi Penilai</div>
                <div id="evaluatorContainer" style="font-size:0.85rem; color:#0f172a; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <i data-lucide="user" style="width:14px; vertical-align:middle; color:#64748b;"></i> <span id="evaluatorName">Loading...</span>
                </div>
            </div>

            <div class="right-col">
                <div class="section-title">Peta Kompetensi</div>
                <div style="position: relative; height: 250px; width: 100%; margin-bottom: 1rem;">
                    <canvas id="modalRadarChart"></canvas>
                </div>

                <div class="section-title">Rincian Penilaian</div>
                <div id="scoreDetailContainer" style="background:#f8fafc; padding:10px; border-radius:8px; border:1px solid #e2e8f0;"></div>
            </div>
        </div>
    `;

    document.getElementById('detailModal').classList.add('open');

    // Add Print Button to Footer dynamically or ensure it exists
    const modalFooter = document.querySelector('#detailModal .modal-footer');
    if (modalFooter) {
        modalFooter.innerHTML = `
            <button class="btn btn-outline" onclick="closeModal('detailModal')">Tutup</button>
            <button class="btn btn-primary" onclick="printDetailPDF()">
                <i data-lucide="printer" style="width:16px; margin-right:5px;"></i> Cetak PDF
            </button>
        `;
        lucide.createIcons();
    }

    // 3. Render Initial Content
    // Logic: 
    // - Jika Mode History, cari index dari specificId, set terpilih, dan HIDE dropdown.
    // - Jika Mode Master, default ke 0 (Latest) dan SHOW dropdown.

    let initialIndex = 0;
    let hideDropdown = false;

    if (source === 'history' && specificId) {
        // Cari index record dengan ID assessment ini
        const foundIdx = currentDetailHistory.findIndex(item => item.id == specificId);
        if (foundIdx !== -1) {
            initialIndex = foundIdx;
        }
        hideDropdown = true;
    }

    // Render Dropdown or Static Text
    const periodSelectContainer = document.querySelector('#detailPeriodSelect').parentElement;

    if (hideDropdown) {
        // Mode History: Tampilkan Label Periode Statis
        const selectedItem = currentDetailHistory[initialIndex];
        const labelSafe = selectedItem ? `${formatDate(selectedItem.date)} (${getPeriodLabel(selectedItem.date)})` : '-';

        periodSelectContainer.innerHTML = `
            <div style="font-size:0.9rem; font-weight:700; color:#475569; background:#f1f5f9; padding:6px 12px; border-radius:6px; border:1px solid #e2e8f0;">
                ${labelSafe}
            </div>
        `;
    } else {
        // Mode Master: Dropdown Selection (Sudah ada di HTML awal, tinggal set value)
        document.getElementById('detailPeriodSelect').value = initialIndex;

        // Ensure Event Listener is attached (or re-attached if we wiped it, but here we kept HTML structure previously)
        // Note: innerHTML overwrite above in Step 2 destroys listeners. We need to re-bind.
        // Wait, Step 2 creates the HTML fresh every time viewDetail is called.
        // So the `periodSelectContainer` is actually `div` wrapping the select. 
        // If querySelector finds it, we are good.
    }

    renderDetailContent(initialIndex);

    // 4. Add Event Listener (Only if dropdown exists)
    const dropdown = document.getElementById('detailPeriodSelect');
    if (dropdown) {
        dropdown.addEventListener('change', (e) => {
            renderDetailContent(e.target.value);
        });
    }
}

function renderDetailContent(selectedIndex) {
    let dataToRender;

    if (selectedIndex === 'cumulative') {
        // --- LOGIC CALCULATE CUMULATIVE ---
        // Calculate average of SCORES and EXTRA SCORE.
        // Targets & Notes usually take the latest or concatenate. We will take LATEST for text data.

        const count = currentDetailHistory.length;
        if (count === 0) return;

        let totalSum = 0;
        let extraSum = 0;
        let dimSums = {};

        // Initialize dimSums
        DIMENSIONS.forEach(d => { dimSums[d.key] = { sumAvg: 0 }; });

        currentDetailHistory.forEach(item => {
            totalSum += parseFloat(item.total_db || 0); // Use stored DB total
            extraSum += parseInt(item.extra_score || 0);

            // Re-calculate dimension averages if available in JSON, OR use rawAnswers if needed.
            // But api.php returns standard structure? Wait, api.php get_history returns data_json...
            // appData logic calculated scores on the fly in JS during input.
            // assessments.data_json stores the "scores" object! We should use that.

            if (item.scores) {
                DIMENSIONS.forEach(d => {
                    if (item.scores[d.key]) {
                        dimSums[d.key].sumAvg += item.scores[d.key].avg;
                    }
                });
            }
        });

        // Create a Pseudo-Object for rendering
        const latest = currentDetailHistory[0];
        dataToRender = {
            profile: latest.profile, // Fix: Include profile in cumulative object
            targets: latest.targets,
            notes: "Akumulasi dari " + count + " periode penilaian.",
            extra_score: (extraSum / count).toFixed(1), // Average
            total: { avg: (totalSum / count).toFixed(2) },
            scores: {}
        };

        DIMENSIONS.forEach(d => {
            dataToRender.scores[d.key] = {
                avg: dimSums[d.key].sumAvg / count,
                // We don't have exact sum/count for questions in cumulative efficiently, 
                // so we just mock it for the view or hide detail usage.
                cumulative: true
            };
        });

    } else {
        // --- SINGLE PERIOD ---
        dataToRender = currentDetailHistory[parseInt(selectedIndex)];
    }

    currentDetailData = dataToRender; // Store for PDF Export

    // --- RENDER TEXT DATA ---
    const targetEl = document.getElementById('targetContainer');
    targetEl.innerHTML = `
        <div style="background:#eff6ff; padding:10px; border-radius:6px; border:1px solid #dbeafe; margin-bottom:10px;">
            <div style="font-size:0.75rem; font-weight:700; color:#1e40af; margin-bottom:4px;">PERIODE 3 BULAN</div>
            <div style="font-size:0.85rem; color:#1e3a8a; line-height:1.4; white-space: pre-wrap;">${dataToRender.targets && dataToRender.targets.threeMonth ? dataToRender.targets.threeMonth : '-'}</div>
        </div>
        <div style="background:#eff6ff; padding:10px; border-radius:6px; border:1px solid #dbeafe; margin-bottom:10px;">
            <div style="font-size:0.75rem; font-weight:700; color:#1e40af; margin-bottom:4px;">PERIODE 6 BULAN</div>
            <div style="font-size:0.85rem; color:#1e3a8a; line-height:1.4; white-space: pre-wrap;">${dataToRender.targets && dataToRender.targets.sixMonth ? dataToRender.targets.sixMonth : '-'}</div>
        </div>
        <div style="background:#eff6ff; padding:10px; border-radius:6px; border:1px solid #dbeafe; margin-bottom:1rem;">
            <div style="font-size:0.75rem; font-weight:700; color:#1e40af; margin-bottom:4px;">PERIODE 1 TAHUN</div>
            <div style="font-size:0.85rem; color:#1e3a8a; line-height:1.4; white-space: pre-wrap;">${dataToRender.targets && dataToRender.targets.oneYear ? dataToRender.targets.oneYear : '-'}</div>
        </div>
    `;

    document.getElementById('noteContainer').textContent = `"${dataToRender.notes ? dataToRender.notes : 'Tidak ada catatan.'}"`;
    document.getElementById('extraScoreContainer').textContent = dataToRender.extra_score ? dataToRender.extra_score + (selectedIndex === 'cumulative' ? ' (Rata-rata)' : ' / 5') : '-';

    const evaluatorEl = document.getElementById('evaluatorName');
    if (evaluatorEl) {
        if (selectedIndex === 'cumulative') {
            evaluatorEl.innerHTML = `<span style="color:#64748b; font-style:italic;">Riwayat multi-periode</span>`;
        } else {
            const evName = dataToRender.evaluated_by ? dataToRender.evaluated_by : 'Sistem / Tidak Tercatat';
            evaluatorEl.innerHTML = `<strong>${evName}</strong>`;
        }
    }

    // --- RENDER SCORES ---
    const labels = DIMENSIONS.map(d => d.label);
    const scoreValues = DIMENSIONS.map(d => dataToRender.scores[d.key] ? dataToRender.scores[d.key].avg : 0);

    let scoreTableRows = '';
    DIMENSIONS.forEach(d => {
        const s = dataToRender.scores[d.key];
        if (!s) return;

        let detailText = '';
        if (s.cumulative) {
            detailText = `<span style="color:#94a3b8; font-size:0.75rem;">(Avg)</span>`;
        } else {
            const maxScore = (s.count || 3) * 5; // Default 3 questions if missing count
            const sum = s.sum || (s.avg * (s.count || 3));
            detailText = `<span style="color:#94a3b8; font-size:0.75rem;">(${Math.round(sum)}/${maxScore})</span>`;
        }

        scoreTableRows += `
            <div style="display:flex; justify-content:space-between; border-bottom:1px dashed #e2e8f0; padding:4px 0; font-size:0.85rem;">
                <span style="color:#64748b;">${d.label}</span>
                <span style="font-weight:600; color:#0f172a;">${s.avg.toFixed(2)} ${detailText}</span>
            </div>
        `;
    });

    const scoreContainer = document.getElementById('scoreDetailContainer');
    scoreContainer.innerHTML = `
        ${scoreTableRows}
        <div style="display:flex; justify-content:space-between; margin-top:10px; padding-top:10px; border-top:2px solid #cbd5e1;">
            <span style="font-weight:700;">TOTAL SKOR AKHIR</span>
            <span style="font-weight:800; font-size:1.1rem; color:#16a34a;">${parseFloat(dataToRender.total.avg || dataToRender.total_db).toFixed(2)}</span>
        </div>
    `;

    // --- RENDER CHART ---
    if (modalChartInstances.radar) modalChartInstances.radar.destroy();
    const ctx = document.getElementById('modalRadarChart').getContext('2d');

    // Color Logic: Red for cumulative, Blue for latest
    const color = selectedIndex === 'cumulative' ? '#ef4444' : '#2563eb';
    const bg = selectedIndex === 'cumulative' ? 'rgba(239, 68, 68, 0.2)' : 'rgba(37, 99, 235, 0.2)';

    modalChartInstances.radar = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Skor Pegawai',
                data: scoreValues,
                backgroundColor: bg,
                borderColor: color,
                pointBackgroundColor: color,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { r: { suggestedMin: 0, suggestedMax: 5 } },
            plugins: { legend: { display: false } }
        }
    });
}

// --- FUNGSI EDIT DATA (BARU) ---
// --- FUNGSI INPUT DATA BARU (VIA TOMBOL PENCIL) ---
async function editData(empId) {
    // 1. Cari data Employee basic dulu (untuk fallback profile)
    const basicData = appData.find(item => item.id == empId);
    if (!basicData) return;

    console.log("Edit/Input Data untuk Pegawai:", basicData.profile.name);

    // 2. Fetch History Terakhir untuk mendapatkan Data Penilaian (Raw Answers, Targets, Notes)
    let latestData = null;
    try {
        const res = await fetch(`php/api.php?action=get_history&id=${empId}`);
        const json = await res.json();
        // Ambil data paling baru (index 0)
        if (json.status === 'success' && json.data.length > 0) {
            latestData = json.data[0];
        } else {
            // Jika tidak ada history, mungkin baru data pegawai saja -> latestData null
            console.log("Belum ada history penilaian.");
        }
    } catch (e) {
        console.error("Gagal fetch history untuk edit:", e);
    }

    // 3. Set Global ID ke NULL (karena API PHP menggunakan logika Date/Month/Period untuk Upsert)
    // Namun kita bisa simpan currentEditId jika dibutuhkan logic lain, tapi
    // sesuai logic API: jika user ganti tanggal -> Insert Baru. Jika tanggal sama -> Update.
    // Jadi biarkan user melihat data terakhir.
    currentEditId = null;

    // 4. Pindah ke Tab Form Input
    document.querySelectorAll('.view-section').forEach(sec => sec.classList.add('hidden'));
    document.getElementById('form-input').classList.remove('hidden');

    // Update Active Styling pada Sidebar
    document.querySelectorAll('.nav-item').forEach(l => l.classList.remove('active'));
    const navLink = document.querySelector('a[href="#form-input"]');
    if (navLink) navLink.classList.add('active');

    // 5. LOCK Profile Info (Kita hanya edit nilai/target)
    toggleProfileInputs(false);

    // 6. Isi Form Profil
    // Gunakan profile dari latestData jika ada, atau fallback ke basicData
    const profile = latestData ? latestData.profile : basicData.profile;

    if (qs('#name')) qs('#name').value = profile.name || '';
    if (qs('#empId')) qs('#empId').value = profile.empId || profile.nik || '';
    if (qs('#dept')) qs('#dept').value = profile.dept || '';

    const savedRole = profile.role || '';
    if (['Staff', 'SPV', 'Manager'].includes(savedRole)) {
        if (qs('#role')) qs('#role').value = savedRole;
        if (qs('#role-custom')) { qs('#role-custom').style.display = 'none'; qs('#role-custom').value = ''; }
    } else {
        if (qs('#role')) qs('#role').value = 'Lainnya';
        if (qs('#role-custom')) {
            qs('#role-custom').style.display = 'block';
            qs('#role-custom').value = savedRole;
        }
    }

    if (qs('#level')) qs('#level').value = profile.level || '';
    if (qs('#position')) qs('#position').value = profile.position || '';

    // Pendidikan & Lainnya
    const savedEdu = profile.education || '';
    if (['SMA/SMK', 'D3', 'S1', 'S2'].includes(savedEdu)) {
        if (qs('#education')) qs('#education').value = savedEdu;
        if (qs('#education-custom')) { qs('#education-custom').style.display = 'none'; qs('#education-custom').value = ''; }
    } else {
        if (qs('#education')) qs('#education').value = 'Lainnya';
        if (qs('#education-custom')) {
            qs('#education-custom').style.display = 'block';
            qs('#education-custom').value = savedEdu;
        }
    }
    if (qs('#tenure')) qs('#tenure').value = profile.tenure || '';
    if (qs('#certificates')) qs('#certificates').value = profile.certificates || '';


    // 7. ISI DATA PENILAIAN (TARGET, SKOR, NOTES)
    // Reset dulu agar bersih
    qs('#kpiForm').reset();
    document.querySelectorAll('input[type="radio"]').forEach(el => el.checked = false);
    document.querySelectorAll('.radio-opt').forEach(opt => opt.style.backgroundColor = 'white');

    // Re-fill Profile (karena reset() menghapus profile juga)
    if (qs('#name')) qs('#name').value = profile.name || '';
    if (qs('#empId')) qs('#empId').value = profile.empId || profile.nik || '';
    if (qs('#dept')) qs('#dept').value = profile.dept || '';
    // (Ulangi pengisian field dropdown/custom profile sedikit redundant tapi aman setelah reset)
    if (['Staff', 'SPV', 'Manager'].includes(savedRole)) qs('#role').value = savedRole;
    else { qs('#role').value = 'Lainnya'; qs('#role-custom').value = savedRole; }
    if (qs('#level')) qs('#level').value = profile.level || '';
    if (qs('#position')) qs('#position').value = profile.position || '';
    if (['SMA/SMK', 'D3', 'S1', 'S2'].includes(savedEdu)) qs('#education').value = savedEdu;
    else { qs('#education').value = 'Lainnya'; qs('#education-custom').value = savedEdu; }
    if (qs('#tenure')) qs('#tenure').value = profile.tenure || '';
    if (qs('#certificates')) qs('#certificates').value = profile.certificates || '';


    if (latestData) {
        // A. Set Tanggal (Sesuai data terakhir, user bisa ganti jika ingin buat baru)
        if (qs('#date')) qs('#date').value = latestData.date || new Date().toISOString().split('T')[0];

        // B. Target Kerja
        if (latestData.targets) {
            const setTarget = (id, val) => {
                const el = qs(id);
                if (el) {
                    el.value = val || '';
                    if (val && val.trim() !== '') {
                        el.style.backgroundColor = '#f0fdf4'; // Light green cue
                        el.style.borderColor = '#86efac';
                    } else {
                        el.style.backgroundColor = '';
                        el.style.borderColor = '';
                    }
                }
            };
            setTarget('#target3Months', latestData.targets.threeMonth);
            setTarget('#target6Months', latestData.targets.sixMonth);
            setTarget('#target1Year', latestData.targets.oneYear);
        }

        // C. Radio Buttons (Jawaban Kuesioner)
        if (latestData.rawAnswers) {
            for (const [key, val] of Object.entries(latestData.rawAnswers)) {
                const radio = document.querySelector(`input[name="${key}"][value="${val}"]`);
                if (radio) {
                    radio.checked = true; // Set as default value logicwise

                    // Visual Highlighting untuk Radio terpilih (HISTORY)
                    const label = radio.parentElement;
                    if (label && label.classList.contains('radio-opt')) {
                        // Mark as original
                        label.classList.add('original-selection');

                        // Apply Saved Style
                        label.style.backgroundColor = '#c7e9c0'; // Green Existing
                        label.style.color = '#0f172a';

                        // Note: We don't need to reset siblings here because
                        // editData runs once at start; default state is white.
                    }
                }
            }
        }

        // D. Extra Task
        if (latestData.extra_score) {
            const extraRadio = document.querySelector(`input[name="extra_task"][value="${latestData.extra_score}"]`);
            if (extraRadio) extraRadio.checked = true;
        }

        // E. Notes
        if (qs('#notes')) qs('#notes').value = latestData.notes || '';

    } else {
        // Jika tidak ada history, set Default Date hari ini
        if (qs('#date')) qs('#date').value = new Date().toISOString().split('T')[0];
    }

    // Scroll ke atas
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function confirmDelete(id) {
    deleteTargetId = id;
    document.getElementById('deleteModal').classList.add('open');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async () => {
    if (deleteTargetId) {
        await deleteData(deleteTargetId);
        await fetchData();
        closeModal('deleteModal');
        deleteTargetId = null;
    }
});

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('open');
}

window.onclick = function (event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('open');
    }
}

async function clearAllData() {
    if (!confirm("Reset database?")) return;
    await fetch('php/api.php', { method: 'POST', body: JSON.stringify({ action: 'clear_all' }) });
    fetchData();
}

// --- NEW FEATURES (TRACKING & MOBILE) ---

// Toggle Sidebar Mobile
window.toggleSidebar = function () {
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

// Close sidebar when clicking overlay
const overlay = document.querySelector('.sidebar-overlay');
if (overlay) overlay.addEventListener('click', toggleSidebar);

// Logout Logic
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        if (confirm('Apakah anda yakin ingin keluar system?')) {
            await fetch('php/auth.php', { method: 'POST', body: JSON.stringify({ action: 'logout' }) });
            window.location.href = 'login.php';
        }
    });
}

// Monitoring Data
window.loadMonitoringData = async function () {
    try {
        const res = await fetch('php/tracking_api.php?action=admin_monitor');
        const json = await res.json();

        if (json.status === 'success') {
            monitoringData = json.data;
        } else {
            console.error("Failed to load monitoring data");
            monitoringData = [];
        }
        renderMonitoringTable();
    } catch (e) {
        console.error(e);
        monitoringData = [];
        renderMonitoringTable();
    }
}

function renderMonitoringTable() {
    const tbody = document.getElementById('monitorTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const dateFilter = qs('#monitorDate') ? qs('#monitorDate').value : '';
    const searchFilter = qs('#monitorSearch') ? qs('#monitorSearch').value.toLowerCase().trim() : '';
    const typeFilter = qs('#monitorType') ? qs('#monitorType').value : '';

    // Filter Data
    const filtered = monitoringData.filter(row => {
        // Name Search
        const nameMatch = row.full_name.toLowerCase().includes(searchFilter);

        // Date Match
        let dateMatch = true;
        if (dateFilter) {
            // Compare YYYY-MM-DD
            // row.start_time is "YYYY-MM-DD HH:mm:ss"
            const rowDate = row.start_time.split(' ')[0];
            dateMatch = rowDate === dateFilter;
        }

        // Type Match
        let typeMatch = true;
        if (typeFilter) {
            const rowType = row.task_type || 'Kondisional';
            typeMatch = rowType === typeFilter;
        }

        return nameMatch && dateMatch && typeMatch;
    });

    const limit = qs('#limit-monitor') ? parseInt(qs('#limit-monitor').value) : 10;
    const safeLimit = isNaN(limit) ? 10 : limit;

    const totalItems = filtered.length;
    const totalPages = Math.ceil(totalItems / safeLimit);

    if (currentMonitorPage < 1) currentMonitorPage = 1;
    if (currentMonitorPage > totalPages && totalPages > 0) currentMonitorPage = totalPages;

    const startIndex = (currentMonitorPage - 1) * safeLimit;
    const endIndex = startIndex + safeLimit;
    const displayData = filtered.slice(startIndex, endIndex);

    renderPagination(totalItems, safeLimit, currentMonitorPage, totalPages, 'pagination-info-monitor', 'pagination-controls-monitor', 'goToMonitorPage');

    if (displayData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">Data tidak ditemukan (Cek filter/pencarian).</td></tr>';
        return;
    }

    displayData.forEach(row => {
        const startDate = new Date(row.start_time);
        const endDate = row.end_time ? new Date(row.end_time) : null;

        const dateStr = startDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        const timeStart = startDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        const timeEnd = endDate ? endDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '...';

        // Calculate Duration
        let durationStr = '-';
        if (endDate) {
            const diffMs = endDate - startDate;
            const diffHrs = Math.floor(diffMs / 3600000);
            const diffMins = Math.floor((diffMs % 3600000) / 60000);
            const diffSecs = Math.floor((diffMs % 60000) / 1000);

            const h = diffHrs > 0 ? `${diffHrs}j ` : '';
            const m = diffMins > 0 ? `${diffMins}m ` : '';
            const s = (diffHrs === 0 && diffMins === 0) ? `${diffSecs}d` : ''; // Show seconds if very short
            durationStr = (h + m + s).trim() || '< 1m';
        } else {
            durationStr = '<span class="text-green" style="font-size:0.75rem; font-weight:600;">Berjalan</span>';
        }

        let statusBadge = '';
        if (row.status === 'active') statusBadge = '<span class="badge bg-green">Active</span>';
        else if (row.status === 'pending_approval') statusBadge = '<span class="badge bg-yellow" style="background-color:#fef08a; color:#854d0e;">Menunggu Approval</span>';
        else if (row.status === 'revision') statusBadge = '<span class="badge bg-red" style="background-color:#fca5a5; color:#7f1d1d;">Revisi</span>';
        else statusBadge = '<span class="badge bg-blue" style="background-color:#bfdbfe; color:#1e3a8a;">Selesai (Approved)</span>';

        // Ensure path handling
        const evPath = row.evidence_file ? (row.evidence_file.startsWith('http') ? row.evidence_file : row.evidence_file) : '';

        const evidenceLink = evPath ?
            `<a href="${evPath}" target="_blank" style="color:#2563eb; text-decoration:underline;">Lihat Foto</a>` :
            '-';

        const mapsLink = row.logs.length > 0 ?
            `<a href="https://www.google.com/maps?q=${row.logs[0].latitude},${row.logs[0].longitude}" target="_blank" class="btn btn-outline btn-sm">Map</a>` :
            '-';

        // Badge Type Logic
        const type = row.task_type || 'Kondisional';
        let badgeColor = '#475569'; let badgeBg = '#f8fafc'; // Default/Kondisional (Gray)

        if (type === 'Harian') { badgeColor = '#0284c7'; badgeBg = '#e0f2fe'; } // Sky Blue
        else if (type === 'Mingguan') { badgeColor = '#7c3aed'; badgeBg = '#f5f3ff'; } // Violet
        else if (type === 'Bulanan') { badgeColor = '#ea580c'; badgeBg = '#fff7ed'; } // Orange

        const typeHtml = `<span style="background:${badgeBg}; color:${badgeColor}; padding:2px 8px; border-radius:6px; font-size:0.75rem; font-weight:600;">${type}</span>`;
        
        // Actions
        let actionHtml = '-';
        if (row.status === 'pending_approval') {
            actionHtml = `
                <div style="display:flex; gap:5px; align-items:center;">
                    <button class="btn btn-outline btn-sm" onclick="approveTask('${row.id}')" style="background:#f0fdf4; color:#16a34a; border-color:#86efac;">Approve</button>
                    <button class="btn btn-outline btn-sm" onclick="reviseTask('${row.id}')" style="background:#fef2f2; color:#dc2626; border-color:#fca5a5;">Revisi</button>
                </div>
            `;
        }

        tbody.innerHTML += `
            <tr>
                <td style="font-weight:600;">${row.full_name}</td>
                <td>${row.task_name}</td>
                <td>${typeHtml}</td>
                <td>
                    <div style="font-weight:600; color:#334155;">${dateStr}</div>
                    <div style="color:#64748b; font-size:0.85rem; margin-top:2px;">
                        ${timeStart} - ${timeEnd}
                        <span style="background:#f1f5f9; padding:1px 6px; border-radius:4px; margin-left:6px; font-weight:600; font-size:0.75rem;">${durationStr}</span>
                    </div>
                </td>
                <td>${statusBadge}</td>
                <td>${evidenceLink}<br><span style="font-size:0.75rem; color:#64748b;">${row.evidence_text || ''}</span></td>
                <td>${mapsLink}</td>
                <td>${actionHtml}</td>
            </tr>
        `;
    });

    if (window.lucide) lucide.createIcons();
}

window.approveTask = async function(sessionId) {
    if(!confirm('Anda yakin ingin menyetujui laporan ini?')) return;
    try {
        const res = await fetch('php/tracking_api.php?action=admin_approve_task', {
            method: 'POST',
            body: JSON.stringify({ session_id: sessionId })
        });
        const json = await res.json();
        if(json.status === 'success') {
            loadMonitoringData();
        } else {
            alert("Gagal approve: " + json.message);
        }
    } catch(e) {
        console.error(e);
    }
}

let currentReviseSessionId = null;
window.reviseTask = function(sessionId) {
    currentReviseSessionId = sessionId;
    document.getElementById('reviseNote').value = '';
    document.getElementById('reviseModal').classList.add('open');
}

document.addEventListener('DOMContentLoaded', () => {
    const confirmReviseBtn = document.getElementById('confirmReviseBtn');
    if(confirmReviseBtn) {
        confirmReviseBtn.addEventListener('click', async () => {
            if(!currentReviseSessionId) return;
            const note = document.getElementById('reviseNote').value.trim();
            if(!note) return alert("Harap isi catatan revisi");

            try {
                const res = await fetch('php/tracking_api.php?action=admin_revise_task', {
                    method: 'POST',
                    body: JSON.stringify({ session_id: currentReviseSessionId, note: note })
                });
                const json = await res.json();
                if(json.status === 'success') {
                    document.getElementById('reviseModal').classList.remove('open');
                    currentReviseSessionId = null;
                    loadMonitoringData();
                } else {
                    alert("Gagal revise: " + json.message);
                }
            } catch(e) {
                console.error(e);
            }
        });
    }
});

// Auto load monitoring if hash is #monitoring
if (window.location.hash === '#monitoring') {
    loadMonitoringData();
}

// Hook into existing navigation to trigger load
document.querySelectorAll('a[href="#monitoring"]').forEach(el => {
    el.addEventListener('click', loadMonitoringData);
});

// --- SCHEDULING FEATURE ---
const schedForm = document.getElementById('scheduleForm');
if (schedForm) {
    // 1. Load Users for Dropdown
    async function loadUsersForSchedule() {
        // Hancurkan instance Select2 lama jika ada agar tidak duplikat
        if (jQuery('#schedUser').hasClass("select2-hidden-accessible")) {
            jQuery('#schedUser').select2('destroy');
        }

        const res = await fetch('php/tracking_api.php?action=get_users_list');
        const json = await res.json();
        const sel = document.getElementById('schedUser');
        sel.innerHTML = '<option value="">-- Pilih Pegawai --</option>';
        if (json.status === 'success') {
            json.data.forEach(u => {
                sel.innerHTML += `<option value="${u.id}">${u.full_name}</option>`;
            });
        }

        // Inisialisasi Select2
        jQuery('#schedUser').select2({
            placeholder: "-- Pilih Pegawai --",
            allowClear: true,
            width: '100%'
        });
    }

    // 2. Load Assignment History
    async function loadScheduleHistory() {
        const res = await fetch('php/tracking_api.php?action=get_all_assignments');
        const json = await res.json();
        const tbody = document.getElementById('scheduleTableBody');
        tbody.innerHTML = '';
        if (json.status === 'success') {
            json.data.forEach(row => {
                tbody.innerHTML += `
                    <tr>
                        <td>${row.full_name}</td>
                        <td style="font-weight:500;">${row.title}</td>
                        <td><span class="badge bg-yellow">${row.type}</span></td>
                        <td style="font-size:0.8rem;">${row.start_date}<br>s/d ${row.end_date}</td>
                    </tr>
                `;
            });
        }
    }

    // 3. Handle Submit
    schedForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = {
            user_id: document.getElementById('schedUser').value,
            title: document.getElementById('schedTitle').value,
            description: document.getElementById('schedDesc').value,
            type: document.getElementById('schedType').value,
            start_date: document.getElementById('schedStart').value,
            end_date: document.getElementById('schedEnd').value
        };

        const res = await fetch('php/tracking_api.php?action=assign_task', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.status === 'success') {
            alert(json.message);
            schedForm.reset();
            // Reset Select2 value
            jQuery('#schedUser').val(null).trigger('change');

            loadScheduleHistory();
            document.getElementById('schedStart').valueAsDate = new Date();
            document.getElementById('schedEnd').valueAsDate = new Date();
        } else {
            alert("Error: " + json.message);
        }
    });

    // Initial Load when clicking tab
    document.querySelector('a[href="#schedule"]').addEventListener('click', () => {
        loadUsersForSchedule();
        loadScheduleHistory();
    });

    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('schedStart').value = today;
    document.getElementById('schedEnd').value = today;
}

// --- EXPORT PDF FEATURE ---
window.exportToPDF = function () {
    // Ensure jsPDF is available
    if (!window.jspdf) {
        alert("Library PDF belum siap. Silakan refresh halaman.");
        return;
    }
    const { jsPDF } = window.jspdf;

    if (!filteredData || filteredData.length === 0) {
        alert("Tidak ada data untuk diexport (Cek filter).");
        return;
    }

    const doc = new jsPDF();
    const pageWidth = doc.internal.pageSize.getWidth();

    // --- HEADER ---
    doc.setFontSize(16);
    doc.setFont("helvetica", "bold");
    doc.text("LAPORAN REKAPITULASI KARYAWAN", pageWidth / 2, 15, { align: "center" });

    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");
    doc.text(`Dicetak pada: ${new Date().toLocaleDateString('id-ID')}`, pageWidth / 2, 22, { align: "center" });

    doc.setLineWidth(0.5);
    doc.line(14, 26, pageWidth - 14, 26);

    // --- TABLE BODY ---
    const tableBody = filteredData.map((row, index) => {
        const avg = parseFloat(row.total.avg || 0);
        let status = 'Moderate';
        if (avg >= 4) status = 'High';
        if (avg < 3) status = 'Low';

        return [
            index + 1,
            row.profile.name,
            row.profile.dept,
            row.profile.role,
            formatDate(row.date),
            avg.toFixed(2),
            status
        ];
    });

    doc.autoTable({
        head: [['No', 'Nama Pegawai', 'Divisi', 'Jabatan', 'Tgl Penilaian', 'Skor', 'Status']],
        body: tableBody,
        startY: 30,
        theme: 'striped',
        styles: { fontSize: 9 },
        headStyles: { fillColor: [15, 23, 42], textColor: 255, fontStyle: 'bold' },
        columnStyles: {
            0: { cellWidth: 10, halign: 'center' },
            5: { halign: 'center', fontStyle: 'bold' },
            6: { halign: 'center' }
        },
        didParseCell: function (data) {
            // Colorize Status Column
            if (data.section === 'body' && data.column.index === 6) {
                const text = data.cell.raw;
                if (text === 'High') data.cell.styles.textColor = [22, 163, 74];
                if (text === 'Low') data.cell.styles.textColor = [220, 38, 38];
                if (text === 'Moderate') data.cell.styles.textColor = [202, 138, 4];
            }
        }
    });

    try {
        doc.save(`Rekap_Karyawan_${new Date().toISOString().slice(0, 10)}.pdf`);
    } catch (e) {
        alert("Gagal download PDF: " + e.message);
    }
};

window.exportData = function () {
    if (!filteredData || filteredData.length === 0) {
        alert("Tidak ada data untuk diexport!");
        return;
    }

    if (typeof XLSX === 'undefined') {
        alert("Library Excel belum siap. Pastikan koneksi internet aktif lalu refresh halaman.");
        return;
    }

    // Baris header
    const wsData = [
        ['No', 'Tanggal Penilaian', 'Nama Pegawai', 'Divisi', 'Jabatan', 'Tipe', 'Total Skor', 'Status']
    ];

    // Baris data
    filteredData.forEach((row, index) => {
        const avg = parseFloat(row.total.avg || 0);
        let status = 'Moderate';
        if (avg >= 4) status = 'High';
        if (avg < 3) status = 'Low';

        wsData.push([
            index + 1,
            formatDate(row.date),
            row.profile.name,
            row.profile.dept,
            row.profile.role,
            row.profile.type || '-',
            avg.toFixed(2),
            status
        ]);
    });

    // Buat workbook & sheet
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(wsData);

    // Lebar kolom otomatis
    ws['!cols'] = [
        { wch: 5 },  // No
        { wch: 18 },  // Tanggal
        { wch: 28 },  // Nama Pegawai
        { wch: 15 },  // Divisi
        { wch: 15 },  // Jabatan
        { wch: 15 },  // Tipe
        { wch: 12 },  // Total Skor
        { wch: 12 },  // Status
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Rekap KPI');

    const filename = `Rekap_KPI_${new Date().toISOString().slice(0, 10)}.xlsx`;
    XLSX.writeFile(wb, filename);
};

window.exportMasterExcel = function () {
    const searchVal = qs('#search-master').value.toLowerCase().trim();
    const statusFilter = qs('#filter-status-master').value;

    // Filter data yang akan diexport (sesuai filter di UI)
    const exportData = appData.filter(row => {
        let status = 'Moderate';
        if (row.total.avg >= 4) status = 'High';
        if (row.total.avg < 3) status = 'Low';
        const matchStatus = statusFilter ? status === statusFilter : true;
        const name = row.profile.name.toLowerCase();
        const matchSearch = searchVal ? name.includes(searchVal) : true;
        return matchStatus && matchSearch;
    });

    if (exportData.length === 0) {
        alert("Tidak ada data untuk diexport!");
        return;
    }

    if (typeof XLSX === 'undefined') {
        alert("Library Excel belum siap!");
        return;
    }

    // Header (Bisa disesuaikan jika user ingin HANYA kolom tertentu)
    const wsData = [
        ['No', 'Nama Pegawai', 'NIK/NIP', 'Divisi', 'Jabatan', 'Tipe', 'Pendidikan', 'Posisi', 'Lama Kerja']
    ];

    exportData.forEach((row, index) => {
        wsData.push([
            index + 1,
            row.profile.name,
            row.profile.empId || '-',
            row.profile.dept,
            row.profile.role,
            row.profile.type || '-',
            row.profile.education || '-',
            row.profile.position || '-',
            row.profile.tenure || '-'
        ]);
    });

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(wsData);

    ws['!cols'] = [
        { wch: 5 }, { wch: 30 }, { wch: 15 }, { wch: 15 }, { wch: 15 }, { wch: 15 }, { wch: 15 }, { wch: 20 }, { wch: 20 }
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Data Karyawan');
    XLSX.writeFile(wb, `Data_Karyawan_${new Date().toISOString().slice(0, 10)}.xlsx`);
};

window.printDetailPDF = function () {
    if (!currentDetailData) return;

    // Ensure jsPDF is available
    if (!window.jspdf) {
        alert("Library PDF belum siap. Silakan refresh halaman.");
        return;
    }
    const { jsPDF } = window.jspdf;

    const doc = new jsPDF();
    const pageWidth = doc.internal.pageSize.getWidth();

    const isCumulative = qs('#detailPeriodSelect') && qs('#detailPeriodSelect').value === 'cumulative';

    // Helper Function to Draw a Single Page of Report
    const drawReportPage = (data, titleSuffix = "") => {
        let currentY = 15;

        // --- HEADER ---
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.text(`LAPORAN DETAIL KARYAWAN ${titleSuffix}`, pageWidth / 2, currentY, { align: "center" });

        doc.setFontSize(9);
        doc.setFont("helvetica", "normal");

        let subTitle = "";
        if (titleSuffix.includes("PERIODE")) {
            subTitle = `Periode Penilaian: ${formatDate(data.date)}`;
        } else {
            const lbl = qs('#detailPeriodSelect') ? qs('#detailPeriodSelect').options[qs('#detailPeriodSelect').selectedIndex].text : '';
            subTitle = `Periode: ${lbl}`;
        }

        doc.text(subTitle, pageWidth / 2, currentY + 5, { align: "center" });
        doc.text(`Dicetak pada: ${new Date().toLocaleDateString('id-ID')}`, pageWidth / 2, currentY + 10, { align: "center" });

        doc.setLineWidth(0.5);
        doc.line(10, currentY + 13, pageWidth - 10, currentY + 13);

        currentY += 20;

        // --- PROFILE SECTION ---
        // Photo Placeholder
        const photoW = 30;
        const photoH = 40;
        const photoX = pageWidth - 10 - photoW;
        const photoY = currentY - 4;

        doc.setDrawColor(0);
        doc.rect(photoX, photoY, photoW, photoH);
        doc.setFontSize(8);
        doc.text("FOTO", photoX + (photoW / 2), photoY + (photoH / 2), { align: "center" });
        doc.text("3x4", photoX + (photoW / 2), photoY + (photoH / 2) + 4, { align: "center" });

        doc.setFontSize(11);
        doc.setFont("helvetica", "bold");
        doc.text("INFORMASI PEGAWAI", 10, currentY);
        currentY += 5;

        // Profile Data Fields
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");

        const leftColX = 10;
        const rightColX = 100;
        const rowHeight = 6;

        const safeProfile = data.profile || {}; // Safety check

        doc.text("Nama Lengkap", leftColX, currentY + 4);
        doc.text(`: ${safeProfile.name || '-'}`, leftColX + 35, currentY + 4);

        doc.text("Pendidikan", rightColX, currentY + 4);
        doc.text(`: ${safeProfile.education || '-'}`, rightColX + 25, currentY + 4);

        doc.text("NIK / NIP", leftColX, currentY + 4 + rowHeight);
        doc.text(`: ${safeProfile.empId || '-'}`, leftColX + 35, currentY + 4 + rowHeight);

        doc.text("Lama Kerja", rightColX, currentY + 4 + rowHeight);
        doc.text(`: ${safeProfile.tenure || '-'}`, rightColX + 25, currentY + 4 + rowHeight);

        doc.text("Departemen", leftColX, currentY + 4 + (rowHeight * 2));
        doc.text(`: ${safeProfile.dept || '-'}`, leftColX + 35, currentY + 4 + (rowHeight * 2));

        doc.text("Sertifikat", rightColX, currentY + 4 + (rowHeight * 2));
        doc.text(`: ${safeProfile.certificates || '-'}`, rightColX + 25, currentY + 4 + (rowHeight * 2));

        doc.text("Jabatan", leftColX, currentY + 4 + (rowHeight * 3));
        doc.text(`: ${safeProfile.role || '-'}`, leftColX + 35, currentY + 4 + (rowHeight * 3));

        currentY = Math.max(currentY + (rowHeight * 4), photoY + photoH) + 5;

        // --- EVIDENCE SECTION ---
        doc.setFontSize(11);
        doc.setFont("helvetica", "bold");
        doc.setFillColor(240, 248, 255);
        doc.rect(10, currentY, pageWidth - 20, 7, "F");
        doc.text("EVIDENCE TARGET KERJA", 12, currentY + 5);
        currentY += 10;

        doc.setFontSize(9);
        const addTarget = (label, text) => {
            doc.setFont("helvetica", "bold");
            doc.text(label, 12, currentY);
            doc.setFont("helvetica", "normal");
            const splitText = doc.splitTextToSize(text || '-', pageWidth - 60);
            doc.text(splitText, 50, currentY);
            const blockHeight = Math.max(5, splitText.length * 4);
            currentY += blockHeight + 2;
        };

        const t = data.targets || {};
        addTarget("Target 3 Bulan", `: ${t.threeMonth || '-'}`);
        addTarget("Target 6 Bulan", `: ${t.sixMonth || '-'}`);
        addTarget("Target 1 Tahun", `: ${t.oneYear || '-'}`);

        currentY += 3;

        // --- SCORES TABLE ---
        const tableBody = [];
        DIMENSIONS.forEach(d => {
            const s = data.scores[d.key];
            if (!s) return;

            let scoreText = s.avg.toFixed(2);
            if (s.cumulative) {
                scoreText += " (Avg)";
            } else {
                const maxScore = (s.count || 3) * 5;
                const sum = s.sum || (s.avg * (s.count || 3));
                scoreText += ` (${Math.round(sum)}/${maxScore})`;
            }

            tableBody.push([
                { content: d.label.toUpperCase(), styles: { fontStyle: 'bold', fillColor: [229, 231, 235] } },
                { content: scoreText, styles: { fontStyle: 'bold', halign: 'center', fillColor: [229, 231, 235] } }
            ]);

            // Questions (Only if available, and if NOT cumulative summary page, OR if we want to force questions logic?)
            // If it's a cumulative page (Page 1), s.cumulative is true -> questions skipped.
            // If it's a detail page (Page 2+), s.cumulative is usually undefined or false -> questions shown.
            if (d.questions && !s.cumulative) {
                d.questions.forEach((q, i) => {
                    const qKey = `${d.key}_${i}`;
                    let rawVal = '-';
                    if (data.rawAnswers && data.rawAnswers[qKey] !== undefined && data.rawAnswers[qKey] !== null) {
                        rawVal = String(data.rawAnswers[qKey]);
                    }
                    tableBody.push([`   ${q}`, { content: rawVal, styles: { halign: 'center' } }]);
                });
            }
        });

        // Total
        const totalVal = data.total.avg || data.total_db || 0;
        tableBody.push([
            { content: "TOTAL SKOR AKHIR", styles: { fontStyle: 'bold', fillColor: [22, 163, 74], textColor: 255 } },
            { content: parseFloat(totalVal).toFixed(2), styles: { fontStyle: 'bold', halign: 'center', fillColor: [22, 163, 74], textColor: 255 } }
        ]);

        doc.autoTable({
            head: [['DIMENSI KOMPETENSI', 'SKOR']],
            body: tableBody,
            startY: currentY,
            theme: 'grid',
            styles: { fontSize: 8, cellPadding: 1.5, overflow: 'linebreak' },
            headStyles: { fillColor: [15, 23, 42], textColor: 255, fontStyle: 'bold', fontSize: 9 },
            margin: { left: 10, right: 10 },
            columnStyles: { 0: { cellWidth: 'auto' }, 1: { cellWidth: 30, halign: 'center' } }
        });

        currentY = doc.lastAutoTable.finalY + 8;

        // --- NOTES SECTION ---
        doc.setFontSize(11);
        doc.setFont("helvetica", "bold");
        doc.text("CATATAN / FEEDBACK", 10, currentY);
        currentY += 5;

        doc.setFontSize(9);
        doc.setFont("helvetica", "italic");
        const notes = data.notes || "Tidak ada catatan tambahan.";
        const splitNotes = doc.splitTextToSize(notes, pageWidth - 20);
        doc.text(splitNotes, 10, currentY);

        currentY += (splitNotes.length * 5) + 10;

        // --- EVALUATOR SECTION ---
        doc.setFontSize(11);
        doc.setFont("helvetica", "bold");
        doc.text("INFORMASI PENILAI", 10, currentY);
        currentY += 5;

        doc.setFontSize(9);
        doc.setFont("helvetica", "normal");
        let evName = data.evaluated_by ? data.evaluated_by : "Sistem / Tidak Tercatat";

        // Cek apakah ini mode akumulasi (cumulative obj created dynamically)
        let isCumData = false;
        if (data.scores) {
            const firstKey = Object.keys(data.scores)[0];
            if (firstKey && data.scores[firstKey].cumulative) isCumData = true;
        }

        if (isCumData) {
            evName = "(Riwayat multi-periode)";
            doc.setFont("helvetica", "italic");
        }

        doc.text(`Dinilai Oleh: ${evName}`, 10, currentY);
    };

    // --- MAIN REPORT LOGIC ---

    if (isCumulative && currentDetailHistory.length > 0) {
        // Page 1: Summary (Already in currentDetailData, but ensure it has profile!)
        // Note: We patched profile in renderDetailContent, but effectively `currentDetailData` is that object.
        drawReportPage(currentDetailData, " (RATA-RATA AKUMULASI)");

        // Page 2+: Individual Details
        currentDetailHistory.forEach(histItem => {
            doc.addPage();
            drawReportPage(histItem, ` (PERIODE ${getPeriodLabel(histItem.date)})`);
        });

    } else {
        // Single Page Mode (Latest or Specific Period)
        drawReportPage(currentDetailData);
    }

    // Save
    try {
        const fname = (currentDetailData.profile && currentDetailData.profile.name) ?
            currentDetailData.profile.name.replace(/[^a-z0-9]/gi, '_').toLowerCase() : 'employee';
        doc.save(`Laporan_${fname}_${new Date().toISOString().slice(0, 10)}.pdf`);
    } catch (e) {
        alert("Gagal download PDF: " + e.message);
    }
}

// --- ADMIN ATTENDANCE LOGIC ---
let attAdminData = [];
let currentAttAdminPage = 1;

// Event Listeners for Attendance Admin
const filterAttType = document.getElementById('filter-att-type');
if (filterAttType) {
    filterAttType.addEventListener('change', function () {
        updateAttDateInput(this.value);
        fetchAttAdminData();
    });
}

const limitAttAdmin = document.getElementById('limit-att-admin');
if (limitAttAdmin) {
    limitAttAdmin.addEventListener('change', () => {
        currentAttAdminPage = 1;
        renderAttAdminTable();
    });
}

const searchAttAdmin = document.getElementById('search-att-admin');
if (searchAttAdmin) {
    searchAttAdmin.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') fetchAttAdminData();
    });
}

function updateAttDateInput(type) {
    const container = document.getElementById('att-date-container');
    if (!container) return;

    let html = '';
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');

    if (type === 'daily') {
        html = `<input type="date" id="filter-att-date" class="form-control" style="padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; height: 38px;" value="${yyyy}-${mm}-${dd}">`;
    } else if (type === 'weekly') {
        html = `<input type="week" id="filter-att-date" class="form-control" style="padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; height: 38px;">`;
    } else if (type === 'monthly') {
        html = `<input type="month" id="filter-att-date" class="form-control" style="padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; outline: none; font-size: 0.875rem; height: 38px;" value="${yyyy}-${mm}">`;
    }

    container.innerHTML = html;

    // Re-attach listener
    const newInp = document.getElementById('filter-att-date');
    if (newInp) {
        newInp.addEventListener('change', fetchAttAdminData);
    }
}

async function fetchAttAdminData() {
    const type = document.getElementById('filter-att-type').value;
    const dateInput = document.getElementById('filter-att-date');
    const dateVal = dateInput ? dateInput.value : '';
    const search = document.getElementById('search-att-admin').value;

    let start = '', end = '';

    if (dateVal) {
        if (type === 'daily') {
            start = dateVal;
            end = dateVal;
        } else if (type === 'weekly') {
            if (dateVal.includes('W')) {
                const parts = dateVal.split('-W');
                const yr = parseInt(parts[0]);
                const wk = parseInt(parts[1]);

                // Get Monday
                const d = new Date(yr, 0, 1 + (wk - 1) * 7);
                const dayOfWeek = d.getDay();
                const ISOweekStart = d;
                if (dayOfWeek <= 4) d.setDate(d.getDate() - d.getDay() + 1);
                else d.setDate(d.getDate() + 8 - d.getDay());

                start = d.toISOString().split('T')[0];
                const endDate = new Date(d);
                endDate.setDate(d.getDate() + 6);
                end = endDate.toISOString().split('T')[0];
            }
        } else if (type === 'monthly') {
            start = dateVal + '-01';
            const [y, m] = dateVal.split('-');
            const lastDay = new Date(y, m, 0).getDate();
            end = `${y}-${m}-${lastDay}`;
        }
    }

    try {
        const res = await fetch(`php/tracking_api.php?action=get_all_attendance_history&start_date=${start}&end_date=${end}&search=${encodeURIComponent(search)}`);
        const json = await res.json();

        if (json.status === 'success') {
            attAdminData = json.data;
            currentAttAdminPage = 1;
            renderAttAdminTable();
        } else {
            console.error('Fetch error:', json);
            attAdminData = [];
            renderAttAdminTable();
        }
    } catch (e) {
        console.error(e);
        attAdminData = [];
        renderAttAdminTable();
    }
}

function renderAttAdminTable() {
    const tbody = document.getElementById('attAdminTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!attAdminData || attAdminData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:2rem; color:#94a3b8;">Tidak ada data absensi.</td></tr>';
        updatePaginationControls(0, 10); // Helper func
        // Need to manually handle pagination info/controls clearing if updatePaginationControls relies on ID prefixes not passed
        document.getElementById('pagination-info-att-admin').textContent = "Menampilkan 0 sampai 0 dari 0 entri";
        document.getElementById('pagination-controls-att-admin').innerHTML = "";
        return;
    }

    const limit = parseInt(document.getElementById('limit-att-admin').value) || 10;
    const totalItems = attAdminData.length;
    const totalPages = Math.ceil(totalItems / limit);

    if (currentAttAdminPage < 1) currentAttAdminPage = 1;
    if (currentAttAdminPage > totalPages) currentAttAdminPage = totalPages;

    const start = (currentAttAdminPage - 1) * limit;
    const end = start + limit;
    const displayData = attAdminData.slice(start, end);

    displayData.forEach(row => {
        const d = new Date(row.date);
        const dateStr = d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });

        const inTime = row.clock_in_time ? row.clock_in_time.split(' ')[1].substring(0, 5) : '-';
        const outTime = row.clock_out_time ? row.clock_out_time.split(' ')[1].substring(0, 5) : '-';

        let statusBadge = '';
        if (row.status === 'late') {
            statusBadge = `<span class="badge bg-red" style="background:#fee2e2; color:#dc2626;">Terlambat</span>`;
        } else {
            statusBadge = `<span class="badge bg-green" style="background:#dcfce7; color:#16a34a;">Tepat Waktu</span>`;
        }

        let lateText = '-';
        if (row.status === 'late' && row.clock_in_time) {
            const inFullTimeStr = row.clock_in_time.split(' ')[1];
            const [h, m] = inFullTimeStr.split(':').map(Number);
            const timeInMin = h * 60 + m;

            const pos = row.user_position ? row.user_position.toLowerCase() : '';
            // Ambil dari row jika ada data 'type'
            // Di API belum join type, kita anggap pramubakti jika position ngandung 'pramubakti'

            const isSecurity = (pos.includes('security') || pos.includes('satpam')) && !pos.includes('chief');
            const isPramubakti = pos.includes('pramubakti'); // asumsi pramubakti outsourcing (sesuai tracking_api)

            let benchmarkMin = 8 * 60; // 08:00 default

            if (isSecurity) {
                // Tentukan shift mana saat clock-in berdasarkan target jam
                // Di record, Shift 1 telat jika > 07:00 (batas 05:00-16:59)
                // Shift 2 telat jika > 19:00 (batas 17:00-04:59)
                if (h >= 5 && h < 17) {
                    benchmarkMin = 7 * 60; // 07:00
                } else {
                    benchmarkMin = 19 * 60; // 19:00
                }
            } else if (isPramubakti) {
                benchmarkMin = 7 * 60; // 07:00
            }

            // Jika Shift 2 malam (misal dia absen jam 01:00 pagi), hitung mundur dari benchmark shift 2 kemarin (19:00) -> logic ini bisa rumit karena lewat tengah malam.
            // Solusi aman: Cukup selisih murni antara waktu absen dengan benchmark
            let lateMin = timeInMin - benchmarkMin;

            // Penyesuaian shift malam jika telat lewat tengah malam (misal shift 19:00, dia masuk 01:00 pagi = 1 * 60 = 60 menit vs 19 * 60 = 1140 menit)
            // 60 - 1140 = -1080 (seharusnya telat 6 jam = 360 menit)
            if (lateMin < 0 && isSecurity && benchmarkMin === 19 * 60) {
                lateMin = (timeInMin + 24 * 60) - benchmarkMin;
            }

            if (lateMin > 0) lateText = `${lateMin} Menit`;
        }

        const position = row.user_position || '-';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${dateStr}</td>
            <td style="font-weight:600;">${row.user_name || 'User ' + row.user_id}</td>
            <td style="color:#64748b; font-size:0.85rem;">${position}</td>
            <td>${inTime}</td>
            <td>${outTime}</td>
            <td>${statusBadge}</td>
            <td style="color:#ef4444;">${lateText}</td>
            <td><a href="https://www.google.com/maps?q=${row.clock_in_lat},${row.clock_in_lng}" target="_blank" style="color:#2563eb; text-decoration:underline; font-size:0.85rem;"><i data-lucide="map-pin" style="width:14px;"></i> Lihat</a></td>
        `;
        tbody.appendChild(tr);
    });

    renderPagination(totalItems, limit, currentAttAdminPage, totalPages, 'pagination-info-att-admin', 'pagination-controls-att-admin', 'goToAttAdminPage');
    if (window.lucide) lucide.createIcons();
}

window.goToAttAdminPage = function (p) {
    currentAttAdminPage = p;
    renderAttAdminTable();
}

window.exportAttToExcel = function () {
    if (!attAdminData || attAdminData.length === 0) {
        alert("Tidak ada data untuk diexport!");
        return;
    }

    // Cek apakah library SheetJS (XLSX) tersedia
    if (typeof XLSX === 'undefined') {
        alert("Library Excel belum siap. Pastikan SheetJS sudah dimuat.");
        return;
    }

    // 1. Filename berdasarkan filter
    const type = document.getElementById('filter-att-type').value;
    const dateVal = document.getElementById('filter-att-date').value || 'All';
    const filename = `Laporan_Absensi_${type}_${dateVal}.xlsx`;

    // 2. Buat array data untuk sheet
    // Baris header
    const wsData = [
        ['No', 'Tanggal', 'Nama Pegawai', 'Posisi', 'Jam Masuk', 'Jam Pulang', 'Status', 'Waktu Telat (Menit)', 'Koordinat Masuk']
    ];

    // Baris data
    attAdminData.forEach((row, index) => {
        const d = new Date(row.date);
        const dateStr = d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        const name = row.user_name || ('User ' + row.user_id);
        const position = row.user_position || '-';

        // Format: YYYY-MM-DD HH:MM (tanggal + jam lengkap)
        const inFull = row.clock_in_time ? row.clock_in_time.substring(0, 16) : '-';
        const outFull = row.clock_out_time ? row.clock_out_time.substring(0, 16) : '-';

        // Ambil jam:menit saja untuk kalkulasi keterlambatan
        const inTimeOnly = row.clock_in_time ? row.clock_in_time.split(' ')[1].substring(0, 5) : '00:00';

        let status = row.status === 'late' ? 'Terlambat' : 'Tepat Waktu';

        // Hitung menit telat
        let lateMin = 0;
        if (row.status === 'late' && row.clock_in_time) {
            const inFullTimeStr = row.clock_in_time.split(' ')[1];
            const [h, m] = inFullTimeStr.split(':').map(Number);
            const timeInMin = h * 60 + m;

            const pos = row.user_position ? row.user_position.toLowerCase() : '';
            const isSecurity = (pos.includes('security') || pos.includes('satpam')) && !pos.includes('chief');
            const isPramubakti = pos.includes('pramubakti');

            let benchmarkMin = 8 * 60; // 08:00 default

            if (isSecurity) {
                if (h >= 5 && h < 17) {
                    benchmarkMin = 7 * 60;
                } else {
                    benchmarkMin = 19 * 60;
                }
            } else if (isPramubakti) {
                benchmarkMin = 7 * 60;
            }

            lateMin = timeInMin - benchmarkMin;

            // Penyesuaian Shift Malam (lewat jam 12 malam)
            if (lateMin < 0 && isSecurity && benchmarkMin === 19 * 60) {
                lateMin = (timeInMin + 24 * 60) - benchmarkMin;
            }

            if (lateMin < 0) lateMin = 0;
        }

        const coords = `${row.clock_in_lat}, ${row.clock_in_lng}`;

        wsData.push([
            index + 1,
            dateStr,
            name,
            position,
            inFull,
            outFull,
            status,
            lateMin,
            coords
        ]);
    });

    // 3. Buat Workbook & Sheet
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(wsData);

    // 4. Atur lebar kolom agar rapi
    ws['!cols'] = [
        { wch: 5 },  // No
        { wch: 35 },  // Tanggal
        { wch: 25 },  // Nama Pegawai
        { wch: 20 },  // Posisi
        { wch: 18 },  // Jam Masuk
        { wch: 18 },  // Jam Pulang
        { wch: 14 },  // Status
        { wch: 22 },  // Waktu Telat
        { wch: 28 },  // Koordinat
    ];

    // 5. Style header (tebal)
    const headerRange = XLSX.utils.decode_range(ws['!ref']);
    for (let C = headerRange.s.c; C <= headerRange.e.c; C++) {
        const cellAddr = XLSX.utils.encode_cell({ r: 0, c: C });
        if (ws[cellAddr]) {
            ws[cellAddr].s = {
                font: { bold: true },
                fill: { fgColor: { rgb: '0F172A' } },
                alignment: { horizontal: 'center' }
            };
        }
    }

    XLSX.utils.book_append_sheet(wb, ws, 'Rekap Absensi');

    // 6. Trigger download
    XLSX.writeFile(wb, filename);
}

// Init
updateAttDateInput('daily');

async function resetMac(name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus Security MAC untuk ${name}?\nIni akan memungkinkan user login dari perangkat baru.`)) return;

    try {
        const res = await fetch('php/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reset_mac', name: name })
        });
        const data = await res.json();
        if (data.status === 'success') {
            alert(data.message);
            fetchData();
        } else {
            alert("Error: " + data.message);
        }
    } catch (e) {
        alert("Terjadi kesalahan koneksi");
    }
}