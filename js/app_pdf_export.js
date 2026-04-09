
// js/app_pdf_export.js

window.exportHistoryPDF = function () {
    // Check dependencies
    if (typeof window.jspdf === 'undefined') {
        alert("Library PDF belum dimuat. Silakan refresh halaman.");
        return;
    }

    // Access global historyRawData
    if (typeof historyRawData === 'undefined' || !Array.isArray(historyRawData)) {
        alert("Data riwayat belum siap. Silakan tunggu sebentar atau refresh.");
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Local Helper to avoid ReferenceError
    const getPeriodLabelSafe = (dStr) => {
        const d = new Date(dStr);
        return d.getDate() <= 15 ? 'Periode 1' : 'Periode 2';
    };

    // 1. Re-calculate Enriched Data (Cumulative Avg) Logic
    let allSorted = [...historyRawData].sort((a, b) => new Date(a.assessment_date) - new Date(b.assessment_date));
    const empStats = {};

    const enrichedData = allSorted.map(item => {
        if (!empStats[item.emp_id]) {
            empStats[item.emp_id] = { sum: 0, count: 0 };
        }
        const currentScore = parseFloat(item.total_score || 0);
        empStats[item.emp_id].sum += currentScore;
        empStats[item.emp_id].count += 1;
        const runningAvg = empStats[item.emp_id].sum / empStats[item.emp_id].count;

        return { ...item, _cumulative_avg: runningAvg };
    });

    // 2. Filter Logic (Read from DOM)
    const startFilter = document.getElementById('history-start') ? document.getElementById('history-start').value : '';
    const endFilter = document.getElementById('history-end') ? document.getElementById('history-end').value : '';
    const searchVal = document.getElementById('search-history') ? document.getElementById('search-history').value.toLowerCase().trim() : '';
    const periodFilter = document.getElementById('filter-period-header') ? document.getElementById('filter-period-header').value : '';

    let filteredItems = enrichedData.filter(item => {
        const itemMonth = item.assessment_date.substring(0, 7); // YYYY-MM
        let matchDate = true;
        if (startFilter && itemMonth < startFilter) matchDate = false;
        if (endFilter && itemMonth > endFilter) matchDate = false;

        const name = item.full_name ? item.full_name.toLowerCase() : '';
        const matchSearch = searchVal ? name.includes(searchVal) : true;

        let matchPeriod = true;
        if (periodFilter) {
            const pLabel = getPeriodLabelSafe(item.assessment_date);
            if (pLabel !== periodFilter) matchPeriod = false;
        }

        return matchDate && matchSearch && matchPeriod;
    });

    if (filteredItems.length === 0) {
        alert("Tidak ada data untuk diexport (sesuai filter saat ini).");
        return;
    }

    // Sort Descending for PDF (Latest first)
    filteredItems.sort((a, b) => new Date(b.assessment_date) - new Date(a.assessment_date));

    // --- LOGIC PERCABANGAN EXPORT ---
    // Jika ada search nama -> Cetak DETAIL LAPORAN (Accumulation + Periods)
    if (searchVal && searchVal.length > 0) {
        // User requesting detailed report for specific employee(s)
        exportDetailedEmployeePDF(filteredItems, doc, getPeriodLabelSafe, searchVal);
    } else {
        // Jika tidak ada search -> Cetak TABLE SUMMARY + DETAIL AKUMULASI per karyawan
        (async () => {
            await exportSummaryTablePDF(filteredItems, doc, startFilter, endFilter, periodFilter);
        })();
    }
};

// --- FUNGSI 1: SUMMARY TABLE + DETAIL AKUMULASI PER KARYAWAN ---
async function exportSummaryTablePDF(items, doc, startFilter, endFilter, periodFilter) {
    // ---- PAGE 1: TABEL REKAP ----
    const tableBody = items.map(item => {
        const d = new Date(item.assessment_date);
        const dateStr = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        const pLabel = d.getDate() <= 15 ? 'Periode 1' : 'Periode 2';
        const mStr = d.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' });
        const indScore = parseFloat(item.total_score || 0).toFixed(2);
        const periodScore = `${mStr}: ${indScore}`;
        const cumScore = item._cumulative_avg.toFixed(2);

        return [
            dateStr,
            item.full_name,
            item.department,
            item.role_title,
            pLabel,
            periodScore,
            cumScore
        ];
    });

    const pageWidth = doc.internal.pageSize.getWidth();

    doc.setFontSize(16);
    doc.text("Laporan Riwayat Penilaian Kinerja", 14, 15);

    doc.setFontSize(10);
    let subtitle = "Periode: Semua";
    if (startFilter || endFilter) {
        subtitle = `Periode: ${startFilter || "Awal"} s/d ${endFilter || "Akhir"}`;
    }
    doc.text(subtitle, 14, 22);

    if (periodFilter) {
        doc.text(`Filter Periode: ${periodFilter}`, 14, 27);
    }

    doc.text(`Dicetak: ${new Date().toLocaleDateString('id-ID')}`, pageWidth - 14, 22, { align: 'right' });

    doc.autoTable({
        startY: periodFilter ? 32 : 28,
        head: [['Tanggal', 'Nama', 'Divisi', 'Jabatan', 'Tipe', 'Periode', 'Skor Periode', 'Total Skor (Avg)']],
        body: items.map(item => {
            const d = new Date(item.assessment_date);
            const dateStr = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
            const pLabel = d.getDate() <= 15 ? 'Periode 1' : 'Periode 2';
            const mStr = d.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' });
            const indScore = parseFloat(item.total_score || 0).toFixed(2);
            const periodScore = `${mStr}: ${indScore}`;
            const cumScore = item._cumulative_avg.toFixed(2);

            return [
                dateStr,
                item.full_name,
                item.department,
                item.role_title,
                item.type || '-',
                pLabel,
                periodScore,
                cumScore
            ];
        }),
        theme: 'grid',
        headStyles: { fillColor: [22, 163, 74] },
        styles: { fontSize: 8 },
    });

    // ---- PAGE 2+: DETAIL RATA-RATA AKUMULASI PER KARYAWAN ----
    const dimKeys = ['kinerja', 'kolaborasi', 'integritas', 'inisiatif', 'kompetensi'];
    const dimLabels = {
        'kinerja': 'Kinerja & Produktivitas',
        'kolaborasi': 'Kolaborasi & Komunikasi',
        'integritas': 'Integritas & Kepatuhan',
        'inisiatif': 'Inisiatif & Pemecahan Masalah',
        'kompetensi': 'Kompetensi Teknis'
    };

    // Kumpulkan emp_id unik dari filtered items (sorted by name)
    const uniqueEmployees = [];
    const seenIds = new Set();
    [...items].sort((a, b) => (a.full_name || '').localeCompare(b.full_name || '')).forEach(item => {
        if (!seenIds.has(item.emp_id)) {
            seenIds.add(item.emp_id);
            uniqueEmployees.push({ emp_id: item.emp_id, full_name: item.full_name });
        }
    });

    // Buat periode label buat judul
    let periodRangeLabel = 'Semua Periode';
    if (startFilter || endFilter) {
        periodRangeLabel = `${startFilter || 'Awal'} s/d ${endFilter || 'Akhir'}`;
    }
    if (periodFilter) periodRangeLabel += ` (${periodFilter})`;

    try {
        for (const emp of uniqueEmployees) {
            // Ambil full history dari server
            const res = await fetch(`php/api.php?action=get_history&id=${emp.emp_id}`);
            const json = await res.json();

            if (json.status !== 'success' || !json.data || json.data.length === 0) continue;

            const fullHistory = json.data;

            // Filter hanya periode yang sesuai filter DOM
            const filteredHistory = fullHistory.filter(item => {
                const itemMonth = item.date.substring(0, 7);
                let match = true;
                if (startFilter && itemMonth < startFilter) match = false;
                if (endFilter && itemMonth > endFilter) match = false;
                if (periodFilter) {
                    const d = new Date(item.date);
                    const pLabel = d.getDate() <= 15 ? 'Periode 1' : 'Periode 2';
                    if (pLabel !== periodFilter) match = false;
                }
                return match;
            });

            if (filteredHistory.length === 0) continue;

            // Hitung rata-rata akumulasi HANYA dari periode yang difilter
            const count = filteredHistory.length;
            let totalSum = 0;
            let extraSum = 0;
            const dimSums = {};
            dimKeys.forEach(k => dimSums[k] = 0);

            filteredHistory.forEach(h => {
                totalSum += parseFloat(h.total_db || 0);
                extraSum += parseFloat(h.extra_score || 0);
                if (h.scores) {
                    dimKeys.forEach(k => {
                        if (h.scores[k]) dimSums[k] += (parseFloat(h.scores[k].avg) || 0);
                    });
                }
            });

            // Susun data cumulativeData
            const latestRecord = filteredHistory.sort((a, b) => new Date(b.date) - new Date(a.date))[0];
            const cumulativeData = {
                profile: latestRecord.profile,
                targets: latestRecord.targets,
                notes: `Rata-rata dari ${count} penilaian pada periode: ${periodRangeLabel}.`,
                extra_score: (extraSum / count),
                total: { avg: (totalSum / count) },
                scores: {},
                periodLabel: `RATA-RATA AKUMULASI — ${emp.full_name} (${periodRangeLabel})`
            };

            dimKeys.forEach(k => {
                cumulativeData.scores[k] = {
                    avg: (dimSums[k] / count),
                    cumulative: true
                };
            });

            doc.addPage();
            drawPDFPage(doc, cumulativeData, dimKeys, dimLabels);
        }
    } catch (e) {
        console.error('PDF detail error:', e);
    }

    doc.save("Laporan_Riwayat_Penilaian.pdf");
}


// --- FUNGSI 2: DETAILED EMPLOYEE REPORT (New) ---
// Mencetak detail seperti di Modal View Detail
function exportDetailedEmployeePDF(items, doc, getPeriodFn, searchVal) {
    // 1. Group by Employee (Should be mostly 1 employee if searched by name, but could be multiple matches)
    // We will iterate through unique employees found in the filter
    const uniqueEmpIds = [...new Set(items.map(i => i.emp_id))];

    let isFirstPage = true;

    uniqueEmpIds.forEach(empId => {
        // Get all records for this employee from filtered items
        const rawRecords = items.filter(i => i.emp_id === empId); // Raw filtered

        // IMPORTANT: To get FULL details (targets, scores per dim), we need the raw data structure
        // The 'items' from enrichedData contains flattened fields (emp_id, full_name, etc) AND data_json string usually?
        // Let's check fetchHistoryData in app.js. 
        // api.php?action=get_all_assessments returns: 
        // a.id, assessment_date, total_score, period, e.id as emp_id, full_name...
        // It DOES NOT return 'data_json' or 'targets' in the bulk list!
        // PROBLEM: We cannot print details without fetching 'get_history' detail for each.

        // SOLUTION: We must warn user or do individual fetch.
        // Doing N fetches might be slow.
        // Alternative: The user request implies "Like Report Detail".
        // If we don't have the data, we can't print it.
        // Let's look at `viewDetail` in app.js -> it calls `api.php?action=get_history&id=${id}`.
        // This returns the full JSON history for that employee.

        // Since this is a client-side export, we can try to fetch detailed history for these employees.
        // Since user likely searches for 1 person (e.g. "Slamet"), it's 1 fetch.
        // If user searches "A", it might be many. We should limit.
    });

    // REVISION: Because we don't have detailed data in the table view (only summary columns),
    // we cannot generate the "Detailed Report" instantly without fetching.
    // However, I will implement a fetch loop for the found employees (limit to top 5 to avoid crash).

    (async () => {
        try {
            // Limit to first 5 employees to prevent overload if search is vague
            const targets = uniqueEmpIds.slice(0, 5);

            for (const eid of targets) {
                // Fetch full history for this employee
                const res = await fetch(`php/api.php?action=get_history&id=${eid}`);
                const json = await res.json();

                if (json.status !== 'success') continue;

                const fullHistory = json.data; // Array of assessment details
                if (!fullHistory || fullHistory.length === 0) continue;

                // Sort Descending
                fullHistory.sort((a, b) => new Date(b.date) - new Date(a.date));

                // A. CALCULATE CUMULATIVE (RATA-RATA AKUMULASI)
                // Similar logic to renderDetailContent('cumulative') in app.js
                const count = fullHistory.length;
                let totalSum = 0;
                let extraSum = 0;
                let dimSums = {};
                // Init Dimensions keys
                const dimKeys = ['kinerja', 'kolaborasi', 'integritas', 'inisiatif', 'kompetensi'];
                const dimLabels = {
                    'kinerja': 'Kinerja & Produktivitas',
                    'kolaborasi': 'Kolaborasi & Komunikasi',
                    'integritas': 'Integritas & Kepatuhan',
                    'inisiatif': 'Inisiatif & Pemecahan Masalah',
                    'kompetensi': 'Kompetensi Teknis'
                };
                dimKeys.forEach(k => dimSums[k] = 0);

                fullHistory.forEach(h => {
                    totalSum += parseFloat(h.total_db || 0);
                    extraSum += parseFloat(h.extra_score || 0);
                    if (h.scores) {
                        dimKeys.forEach(k => {
                            if (h.scores[k]) dimSums[k] += (parseFloat(h.scores[k].avg) || 0);
                        });
                    }
                });

                const cumulativeData = {
                    profile: fullHistory[0].profile, // Latest Profile
                    targets: fullHistory[0].targets, // Latest Targets
                    notes: `Rata-rata Akumulasi dari ${count} periode.`,
                    extra_score: (extraSum / count),
                    total: { avg: (totalSum / count) },
                    scores: {},
                    isCumulative: true,
                    periodLabel: "RATA-RATA AKUMULASI"
                };

                dimKeys.forEach(k => {
                    cumulativeData.scores[k] = {
                        avg: (dimSums[k] / count),
                        cumulative: true
                    };
                });

                // DRAW CUMULATIVE PAGE
                if (!isFirstPage) doc.addPage();
                isFirstPage = false;
                drawPDFPage(doc, cumulativeData, dimKeys, dimLabels);

                // B. DRAW INDIVIDUAL PAGES (PERIODE X) for Filtered Matches? 
                // User said: "LAPORAN DETAIL KARYAWAN (PERIODE Periode 1)" ... "jika ada filtering nama".
                // Usually this means "Print the filtered rows as pages".
                // If I filtered by "Jan 2024", I only want Jan 2024 page.
                // Let's filter the fullHistory based on the DOM filters again?

                // Re-apply DOM filters to this full history list
                const startFilter = document.getElementById('history-start') ? document.getElementById('history-start').value : '';
                const endFilter = document.getElementById('history-end') ? document.getElementById('history-end').value : '';
                const periodFilter = document.getElementById('filter-period-header') ? document.getElementById('filter-period-header').value : '';

                const filteredHistory = fullHistory.filter(item => {
                    const itemMonth = item.date.substring(0, 7);
                    let match = true;
                    if (startFilter && itemMonth < startFilter) match = false;
                    if (endFilter && itemMonth > endFilter) match = false;
                    if (periodFilter) {
                        const pLabel = getPeriodFn(item.date);
                        if (pLabel !== periodFilter) match = false;
                    }
                    return match;
                });

                filteredHistory.forEach(hItem => {
                    doc.addPage();
                    hItem.periodLabel = `PERIODE ${new Date(hItem.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })} (${getPeriodFn(hItem.date)})`;
                    drawPDFPage(doc, hItem, dimKeys, dimLabels);
                });
            }

            doc.save(`Laporan_Detail_${searchVal}.pdf`);

        } catch (e) {
            console.error(e);
            alert("Gagal generate detail PDF: " + e.message);
        }
    })();
}

// --- HELPER: DRAW SINGLE PAGE LAYOUT (sama persis dengan modal printDetailPDF) ---
function drawPDFPage(doc, data, dimKeys, dimLabels) {
    const pageWidth = doc.internal.pageSize.getWidth();
    let currentY = 15;

    // --- HEADER (Centered Title) ---
    doc.setFontSize(14);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(0, 0, 0);

    // Ambil suffix dari periodLabel
    const periodLabel = data.periodLabel || '';
    // Format: "LAPORAN DETAIL KARYAWAN (RATA-RATA AKUMULASI)" atau "(PERIODE ...)"
    // periodLabel sudah lengkap, kita cetak sebagai subtitle
    doc.text("LAPORAN DETAIL KARYAWAN", pageWidth / 2, currentY, { align: "center" });

    doc.setFontSize(9);
    doc.setFont("helvetica", "normal");
    doc.text(periodLabel, pageWidth / 2, currentY + 5, { align: "center" });
    doc.text(`Dicetak pada: ${new Date().toLocaleDateString('id-ID')}`, pageWidth / 2, currentY + 10, { align: "center" });

    doc.setLineWidth(0.5);
    doc.setDrawColor(0, 0, 0);
    doc.line(10, currentY + 13, pageWidth - 10, currentY + 13);

    currentY += 20;

    // --- PHOTO PLACEHOLDER (top-right corner) ---
    const photoW = 30;
    const photoH = 40;
    const photoX = pageWidth - 10 - photoW;
    const photoY = currentY - 4;

    doc.setDrawColor(0);
    doc.rect(photoX, photoY, photoW, photoH);
    doc.setFontSize(8);
    doc.setFont("helvetica", "normal");
    doc.text("FOTO", photoX + (photoW / 2), photoY + (photoH / 2), { align: "center" });
    doc.text("3x4", photoX + (photoW / 2), photoY + (photoH / 2) + 4, { align: "center" });

    // --- INFORMASI PEGAWAI ---
    doc.setFontSize(11);
    doc.setFont("helvetica", "bold");
    doc.text("INFORMASI PEGAWAI", 10, currentY);
    currentY += 5;

    doc.setFontSize(10);
    doc.setFont("helvetica", "normal");

    const leftColX = 10;
    const rightColX = 100;
    const rowHeight = 6;

    const p = data.profile || {};

    doc.text("Nama Lengkap", leftColX, currentY + 4);
    doc.text(`: ${p.name || '-'}`, leftColX + 35, currentY + 4);

    doc.text("Pendidikan", rightColX, currentY + 4);
    doc.text(`: ${p.education || '-'}`, rightColX + 25, currentY + 4);

    doc.text("NIK / NIP", leftColX, currentY + 4 + rowHeight);
    doc.text(`: ${p.empId || '-'}`, leftColX + 35, currentY + 4 + rowHeight);

    doc.text("Lama Kerja", rightColX, currentY + 4 + rowHeight);
    doc.text(`: ${p.tenure || '-'}`, rightColX + 25, currentY + 4 + rowHeight);

    doc.text("Departemen", leftColX, currentY + 4 + (rowHeight * 2));
    doc.text(`: ${p.dept || '-'}`, leftColX + 35, currentY + 4 + (rowHeight * 2));

    doc.text("Sertifikat", rightColX, currentY + 4 + (rowHeight * 2));
    doc.text(`: ${p.certificates || '-'}`, rightColX + 25, currentY + 4 + (rowHeight * 2));

    doc.text("Jabatan", leftColX, currentY + 4 + (rowHeight * 3));
    doc.text(`: ${p.role || '-'}`, leftColX + 35, currentY + 4 + (rowHeight * 3));

    currentY = Math.max(currentY + (rowHeight * 4), photoY + photoH) + 5;

    // --- EVIDENCE TARGET KERJA ---
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

    // --- DIMENSI KOMPETENSI TABLE ---
    const tableBody = [];

    dimKeys.forEach(k => {
        const s = data.scores ? data.scores[k] : null;
        if (!s) return;

        let scoreText = parseFloat(s.avg || 0).toFixed(2);
        if (s.cumulative) {
            scoreText += " (Avg)";
        } else {
            const maxScore = (s.count || 3) * 5;
            const sum = s.sum || (s.avg * (s.count || 3));
            scoreText += ` (${Math.round(sum)}/${maxScore})`;
        }

        // Baris dimensi (header row per dimensi) — grey background
        tableBody.push([
            { content: (dimLabels[k] || k).toUpperCase(), styles: { fontStyle: 'bold', fillColor: [229, 231, 235] } },
            { content: scoreText, styles: { fontStyle: 'bold', halign: 'center', fillColor: [229, 231, 235] } }
        ]);

        // Sub-pertanyaan (jika ada, misal dari mode periode detail)
        if (data.rawAnswers && !s.cumulative) {
            // pertanyaan per dimensi tidak tersedia di bulk history, skip
        }
    });

    // Extra Score row (jika ada)
    const extra = parseFloat(data.extra_score || 0);
    if (extra > 0) {
        tableBody.push([
            "Tugas Tambahan",
            { content: extra.toFixed(2), styles: { halign: 'center' } }
        ]);
    }

    // Total Row
    const totalVal = parseFloat(data.total ? (data.total.avg || 0) : (data.total_db || 0));
    tableBody.push([
        { content: "TOTAL SKOR AKHIR", styles: { fontStyle: 'bold', fillColor: [22, 163, 74], textColor: 255 } },
        { content: totalVal.toFixed(2), styles: { fontStyle: 'bold', halign: 'center', fillColor: [22, 163, 74], textColor: 255 } }
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

    // --- CATATAN / FEEDBACK ---
    doc.setFontSize(11);
    doc.setFont("helvetica", "bold");
    doc.setTextColor(0, 0, 0);
    doc.text("CATATAN / FEEDBACK", 10, currentY);
    currentY += 5;

    doc.setFontSize(9);
    doc.setFont("helvetica", "italic");
    const notes = data.notes || "Tidak ada catatan tambahan.";
    const splitNotes = doc.splitTextToSize(notes, pageWidth - 20);
    doc.text(splitNotes, 10, currentY);
}

