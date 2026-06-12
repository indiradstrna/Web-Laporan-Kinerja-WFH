// js/admin_crud.js
const CRUDApiUrl = 'php/admin_crud_api.php';

function fetchCRUDData(type) {
    let action = '';
    if (type === 'employee') action = 'get_employees';
    else if (type === 'attendance') action = 'get_attendance';
    else if (type === 'work_session') action = 'get_work_sessions';
    
    fetch(`${CRUDApiUrl}?action=${action}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                renderCRUDTable(type, data.data);
            } else {
                alert("Error fetching data: " + data.message);
            }
        })
        .catch(err => console.error(err));
}

function renderCRUDTable(type, data) {
    const tbody = document.getElementById(`crud-${type}-tbody`);
    if (!tbody) return;
    tbody.innerHTML = '';
    
    data.forEach(row => {
        let tr = document.createElement('tr');
        if (type === 'employee') {
            tr.innerHTML = `
                <td>${row.full_name}</td>
                <td>${row.nip_nik}</td>
                <td>${row.department}</td>
                <td>${row.role_title}</td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick='editCRUDEmployee(${JSON.stringify(row)})'><i data-lucide="edit-3" style="width:14px;"></i></button>
                    <button class="btn btn-outline btn-sm" onclick='deleteCRUDData("${row.id}", "employee")' style="color:#ef4444; border-color:#fee2e2; background:#fef2f2;"><i data-lucide="trash-2" style="width:14px;"></i></button>
                </td>
            `;
        } else if (type === 'attendance') {
            tr.innerHTML = `
                <td>${row.full_name}</td>
                <td>${row.date}</td>
                <td>${row.clock_in_time}</td>
                <td>${row.clock_out_time || '-'}</td>
                <td>${row.status}</td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick='editCRUDAttendance(${JSON.stringify(row)})'><i data-lucide="edit-3" style="width:14px;"></i></button>
                    <button class="btn btn-outline btn-sm" onclick='deleteCRUDData("${row.id}", "attendance")' style="color:#ef4444; border-color:#fee2e2; background:#fef2f2;"><i data-lucide="trash-2" style="width:14px;"></i></button>
                </td>
            `;
        } else if (type === 'work_session') {
            tr.innerHTML = `
                <td>${row.full_name}</td>
                <td>${row.task_name}</td>
                <td>${row.start_time}</td>
                <td>${row.end_time || '-'}</td>
                <td>${row.status}</td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick='editCRUDWorkSession(${JSON.stringify(row)})'><i data-lucide="edit-3" style="width:14px;"></i></button>
                    <button class="btn btn-outline btn-sm" onclick='deleteCRUDData("${row.id}", "work_session")' style="color:#ef4444; border-color:#fee2e2; background:#fef2f2;"><i data-lucide="trash-2" style="width:14px;"></i></button>
                </td>
            `;
        }
        tbody.appendChild(tr);
    });
    if (window.lucide) lucide.createIcons();
}

function showCRUDModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeCRUDModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function editCRUDEmployee(row) {
    document.getElementById('crud-emp-id').value = row.id;
    document.getElementById('crud-emp-name').value = row.full_name;
    document.getElementById('crud-emp-nik').value = row.nip_nik;
    document.getElementById('crud-emp-dept').value = row.department;
    document.getElementById('crud-emp-role').value = row.role_title;
    document.getElementById('crud-emp-pos').value = row.position;
    showCRUDModal('modal-crud-employee');
}

function editCRUDAttendance(row) {
    document.getElementById('crud-att-id').value = row.id;
    document.getElementById('crud-att-date').value = row.date;
    document.getElementById('crud-att-in').value = row.clock_in_time;
    document.getElementById('crud-att-out').value = row.clock_out_time || '';
    document.getElementById('crud-att-status').value = row.status;
    showCRUDModal('modal-crud-attendance');
}

function editCRUDWorkSession(row) {
    document.getElementById('crud-ws-id').value = row.id;
    document.getElementById('crud-ws-task').value = row.task_name;
    document.getElementById('crud-ws-start').value = row.start_time;
    document.getElementById('crud-ws-end').value = row.end_time || '';
    document.getElementById('crud-ws-status').value = row.status;
    showCRUDModal('modal-crud-worksession');
}

function saveCRUDEmployee(e) {
    e.preventDefault();
    const payload = {
        id: document.getElementById('crud-emp-id').value,
        full_name: document.getElementById('crud-emp-name').value,
        nip_nik: document.getElementById('crud-emp-nik').value,
        department: document.getElementById('crud-emp-dept').value,
        role_title: document.getElementById('crud-emp-role').value,
        position: document.getElementById('crud-emp-pos').value
    };
    sendCRUDUpdate('update_employee', payload, 'modal-crud-employee', 'employee');
}

function saveCRUDAttendance(e) {
    e.preventDefault();
    const payload = {
        id: document.getElementById('crud-att-id').value,
        date: document.getElementById('crud-att-date').value,
        clock_in_time: document.getElementById('crud-att-in').value,
        clock_out_time: document.getElementById('crud-att-out').value,
        status: document.getElementById('crud-att-status').value
    };
    sendCRUDUpdate('update_attendance', payload, 'modal-crud-attendance', 'attendance');
}

function saveCRUDWorkSession(e) {
    e.preventDefault();
    const payload = {
        id: document.getElementById('crud-ws-id').value,
        task_name: document.getElementById('crud-ws-task').value,
        start_time: document.getElementById('crud-ws-start').value,
        end_time: document.getElementById('crud-ws-end').value,
        status: document.getElementById('crud-ws-status').value
    };
    sendCRUDUpdate('update_work_session', payload, 'modal-crud-worksession', 'work_session');
}

function sendCRUDUpdate(action, payload, modalId, type) {
    fetch(`${CRUDApiUrl}?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            closeCRUDModal(modalId);
            fetchCRUDData(type);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => console.error(err));
}

function deleteCRUDData(id, type) {
    if (!confirm('PERINGATAN: Apakah Anda yakin ingin menghapus data ini secara permanen?')) return;
    
    fetch(`${CRUDApiUrl}?type=${type}&id=${id}`, {
        method: 'DELETE'
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            fetchCRUDData(type);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => console.error(err));
}

// Initial fetch when tab is opened
function loadKelolaDatabaseTab() {
    fetchCRUDData('employee');
    fetchCRUDData('attendance');
    fetchCRUDData('work_session');
}
