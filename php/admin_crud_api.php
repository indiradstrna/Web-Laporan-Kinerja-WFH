<?php
// php/admin_crud_api.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors to prevent JSON corruption
header('Content-Type: application/json');

include 'connection.php';

// Cek Otorisasi (Hanya Super Admin)
$isSuperAdmin = false;
if (isset($_SESSION['actual_role']) && strcasecmp($_SESSION['actual_role'], 'super admin') === 0) {
    $isSuperAdmin = true;
} elseif (isset($_SESSION['role']) && strcasecmp($_SESSION['role'], 'super admin') === 0) {
    $isSuperAdmin = true;
}

if (!$isSuperAdmin) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya Super Admin yang diizinkan.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET') {
    if ($action === 'get_employees') {
        $sql = "SELECT id, full_name, nip_nik, department, role_title, position, education, tenure, type FROM employees ORDER BY full_name ASC";
        $res = $conn->query($sql);
        $data = [];
        if($res) while($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }
    if ($action === 'get_attendance') {
        $sql = "SELECT a.id, a.user_id, e.full_name, a.date, a.clock_in_time, a.clock_out_time, a.status 
                FROM attendance a 
                JOIN users u ON a.user_id = u.id 
                JOIN employees e ON u.employee_id = e.id 
                ORDER BY a.date DESC, a.clock_in_time DESC LIMIT 500";
        $res = $conn->query($sql);
        $data = [];
        if($res) while($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }
    if ($action === 'get_work_sessions') {
        $sql = "SELECT w.id, w.user_id, e.full_name, w.start_time, w.end_time, w.status, w.task_name 
                FROM work_sessions w 
                JOIN users u ON w.user_id = u.id 
                JOIN employees e ON u.employee_id = e.id 
                ORDER BY w.start_time DESC LIMIT 500";
        $res = $conn->query($sql);
        $data = [];
        if($res) while($r = $res->fetch_assoc()) $data[] = $r;
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }
}

if ($method === 'POST') {
    if ($action === 'update_employee') {
        $id = $conn->real_escape_string($input['id']);
        $full_name = $conn->real_escape_string($input['full_name']);
        $nip_nik = $conn->real_escape_string($input['nip_nik']);
        $dept = $conn->real_escape_string($input['department']);
        $role = $conn->real_escape_string($input['role_title']);
        $pos = $conn->real_escape_string($input['position']);
        
        $sql = "UPDATE employees SET full_name='$full_name', nip_nik='$nip_nik', department='$dept', role_title='$role', position='$pos' WHERE id='$id'";
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Data Karyawan berhasil diupdate.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
        exit;
    }
    if ($action === 'update_attendance') {
        $id = $conn->real_escape_string($input['id']);
        $date = $conn->real_escape_string($input['date']);
        $in = $conn->real_escape_string($input['clock_in_time']);
        $out = !empty($input['clock_out_time']) ? "'".$conn->real_escape_string($input['clock_out_time'])."'" : "NULL";
        $status = $conn->real_escape_string($input['status']);
        
        $sql = "UPDATE attendance SET date='$date', clock_in_time='$in', clock_out_time=$out, status='$status' WHERE id='$id'";
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Data Absensi berhasil diupdate.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
        exit;
    }
    if ($action === 'update_work_session') {
        $id = $conn->real_escape_string($input['id']);
        $start = $conn->real_escape_string($input['start_time']);
        $end = !empty($input['end_time']) ? "'".$conn->real_escape_string($input['end_time'])."'" : "NULL";
        $status = $conn->real_escape_string($input['status']);
        $task = $conn->real_escape_string($input['task_name']);
        
        $sql = "UPDATE work_sessions SET start_time='$start', end_time=$end, status='$status', task_name='$task' WHERE id='$id'";
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Sesi Kerja berhasil diupdate.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
        exit;
    }
}

if ($method === 'DELETE') {
    $id = $conn->real_escape_string($_GET['id'] ?? '');
    $type = $_GET['type'] ?? '';
    
    if ($type === 'employee') {
        $sql = "DELETE FROM employees WHERE id='$id'";
    } elseif ($type === 'attendance') {
        $sql = "DELETE FROM attendance WHERE id='$id'";
    } elseif ($type === 'work_session') {
        $sql = "DELETE FROM work_sessions WHERE id='$id'";
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tipe hapus tidak valid.']);
        exit;
    }
    
    if ($conn->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid.']);
