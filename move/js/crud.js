
// CRUD Database Management JS

let crudUsersList = [];

// Navigation handler
function loadKelolaDatabaseTab() {
    // Determine which tab from the hash
    const hash = window.location.hash;
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(s => s.classList.add('hidden'));
    
    // Default to hide dashboard elements
    if (document.getElementById('dashboard-stats')) {
        document.getElementById('dashboard-stats').classList.add('hidden');
    }
    
    if (hash === '#crud-employee') {
        document.getElementById('crud-employee').classList.remove('hidden');
        loadCrudData('employees');
    } else if (hash === '#crud-attendance') {
        document.getElementById('crud-attendance').classList.remove('hidden');
        loadCrudUsersList().then(() => loadCrudData('attendance'));
    } else if (hash === '#crud-worksession') {
        document.getElementById('crud-worksession').classList.remove('hidden');
        loadCrudUsersList().then(() => loadCrudData('work_sessions'));
    }
}

async function loadCrudUsersList() {
    if (crudUsersList.length > 0) return;
    try {
        const res = await fetch('php/admin_crud_api.php?entity=users_list');
        const json = await res.json();
        if (json.status === 'success') {
            crudUsersList = json.data;
            const selectAtt = document.getElementById('crud-att-user');
            const selectWork = document.getElementById('crud-work-user');
            if (selectAtt) {
                selectAtt.innerHTML = crudUsersList.map(u => `<option value="${u.id}">${u.full_name} (${u.nip_nik})</option>`).join('');
            }
            if (selectWork) {
                selectWork.innerHTML = crudUsersList.map(u => `<option value="${u.id}">${u.full_name} (${u.nip_nik})</option>`).join('');
            }
        }
    } catch(e) {}
}

async function loadCrudData(entity) {
    try {
        const res = await fetch(`php/admin_crud_api.php?entity=${entity}`);
        const json = await res.json();
        if (json.status === 'success') {
            renderCrudTable(entity, json.data);
            lucide.createIcons();
        } else {
            alert("Gagal memuat data: " + json.message);
        }
    } catch (e) {
        console.error(e);
    }
}

function renderCrudTable(entity, data) {
    let html = '';
    
    if (entity === 'employees') {
        data.forEach(item => {
            html += `<tr>
                <td>${item.nip_nik}</td>
                <td>${item.full_name}</td>
                <td>${item.department}</td>
                <td>${item.position}</td>
                <td><span class="badge ${item.role === 'super admin' ? 'badge-danger' : 'badge-primary'}">${item.role || '-'}</span></td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick='editCrudData("employee", ${JSON.stringify(item)})'><i data-lucide="edit"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="deleteCrudData('employees', ${item.id})"><i data-lucide="trash-2"></i></button>
                </td>
            </tr>`;
        });
        document.querySelector('#table-crud-employee tbody').innerHTML = html;
    } else if (entity === 'attendance') {
        data.forEach(item => {
            html += `<tr>
                <td>${item.full_name}<br><small>${item.nip_nik}</small></td>
                <td>${item.date}</td>
                <td>${item.work_type}</td>
                <td>${item.clock_in_time || '-'}</td>
                <td>${item.clock_out_time || '-'}</td>
                <td><span class="badge ${item.status === 'late' ? 'badge-danger' : 'badge-success'}">${item.status}</span></td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick='editCrudData("attendance", ${JSON.stringify(item)})'><i data-lucide="edit"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="deleteCrudData('attendance', ${item.id})"><i data-lucide="trash-2"></i></button>
                </td>
            </tr>`;
        });
        document.querySelector('#table-crud-attendance tbody').innerHTML = html;
    } else if (entity === 'work_sessions') {
        data.forEach(item => {
            let statusBadge = 'badge-primary';
            if(item.status === 'completed') statusBadge = 'badge-success';
            if(item.status === 'pending_approval') statusBadge = 'badge-warning';
            html += `<tr>
                <td>${item.full_name}<br><small>${item.nip_nik}</small></td>
                <td>${item.task_name}</td>
                <td>${item.start_time || '-'}</td>
                <td>${item.end_time || '-'}</td>
                <td><span class="badge ${statusBadge}">${item.status}</span></td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick='editCrudData("worksession", ${JSON.stringify(item)})'><i data-lucide="edit"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="deleteCrudData('work_sessions', ${item.id})"><i data-lucide="trash-2"></i></button>
                </td>
            </tr>`;
        });
        document.querySelector('#table-crud-worksession tbody').innerHTML = html;
    }
}

function openCrudModal(type, mode) {
    const modal = document.getElementById(`modal-crud-${type}`);
    if (modal) {
        modal.style.display = 'block';
        document.getElementById(`form-crud-${type}`).reset();
        document.getElementById(`crud-${type === 'worksession' ? 'work' : (type === 'attendance' ? 'att' : 'emp')}-method`).value = 'POST';
        document.getElementById(`crud-${type === 'worksession' ? 'work' : (type === 'attendance' ? 'att' : 'emp')}-id`).value = '';
        document.getElementById(`modal-crud-${type}-title`).innerText = mode === 'add' ? `Tambah Data` : `Edit Data`;
        
        if(mode === 'add') {
            if (type === 'employee') document.getElementById('crud-emp-password').required = true;
            if (type === 'attendance') document.getElementById('crud-att-user-group').style.display = 'block';
            if (type === 'worksession') document.getElementById('crud-work-user-group').style.display = 'block';
        }
    }
}

function closeCrudModal(type) {
    const modal = document.getElementById(`modal-crud-${type}`);
    if (modal) modal.style.display = 'none';
}

function editCrudData(type, data) {
    openCrudModal(type, 'edit');
    const pfx = type === 'worksession' ? 'work' : (type === 'attendance' ? 'att' : 'emp');
    
    document.getElementById(`crud-${pfx}-id`).value = data.id;
    document.getElementById(`crud-${pfx}-method`).value = 'PUT';
    
    if (type === 'employee') {
        document.getElementById('crud-emp-password').required = false;
        document.getElementById('crud-emp-nik').value = data.nip_nik || '';
        document.getElementById('crud-emp-name').value = data.full_name || '';
        document.getElementById('crud-emp-dept').value = data.department || '';
        document.getElementById('crud-emp-position').value = data.position || '';
        document.getElementById('crud-emp-type').value = data.employee_type || 'PNS';
        document.getElementById('crud-emp-role').value = data.role || 'user';
        document.getElementById('crud-emp-workload').value = data.daily_workload || 8;
    } else if (type === 'attendance') {
        document.getElementById('crud-att-user').value = data.user_id;
        document.getElementById('crud-att-user-group').style.display = 'none'; // Lock user when editing
        document.getElementById('crud-att-date').value = data.date || '';
        document.getElementById('crud-att-type').value = data.work_type || 'WFO';
        document.getElementById('crud-att-in').value = data.clock_in_time || '';
        document.getElementById('crud-att-out').value = data.clock_out_time || '';
        document.getElementById('crud-att-status').value = data.status || 'ontime';
    } else if (type === 'worksession') {
        document.getElementById('crud-work-user').value = data.user_id;
        document.getElementById('crud-work-user-group').style.display = 'none'; // Lock user when editing
        document.getElementById('crud-work-task').value = data.task_name || '';
        document.getElementById('crud-work-start').value = data.start_time || '';
        document.getElementById('crud-work-end').value = data.end_time || '';
        document.getElementById('crud-work-status').value = data.status || 'active';
    }
}

function deleteCrudData(entity, id) {
    if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('_method', 'DELETE');
    
    fetch(`php/admin_crud_api.php?entity=${entity}`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(json => {
        if (json.status === 'success') {
            loadCrudData(entity);
        } else {
            alert('Gagal menghapus: ' + json.message);
        }
    });
}

async function submitCrudForm(e, entity) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    try {
        const res = await fetch(`php/admin_crud_api.php?entity=${entity}`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.status === 'success') {
            const typeMap = {'employees': 'employee', 'attendance': 'attendance', 'work_sessions': 'worksession'};
            closeCrudModal(typeMap[entity]);
            loadCrudData(entity);
        } else {
            alert('Gagal menyimpan: ' + json.message);
        }
    } catch (err) {
        console.error(err);
        alert('Terjadi kesalahan koneksi.');
    }
}

// Hook into existing hash change logic
window.addEventListener('hashchange', () => {
    if(window.location.hash.startsWith('#crud-')) {
        loadKelolaDatabaseTab();
    }
});
// Execute on load if hash is crud
if(window.location.hash.startsWith('#crud-')) {
    setTimeout(loadKelolaDatabaseTab, 500);
}
