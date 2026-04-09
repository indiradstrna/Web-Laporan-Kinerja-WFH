<?php
session_start();
include 'connection.php'; // Pastikan connection.php ada dan benar

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Helper to get input
function getInput() {
    return json_decode(file_get_contents('php://input'), true);
}

// Helper: Get simulated or real datetime
// Returns array ['datetime' => 'YYYY-MM-DD HH:MM:SS', 'date' => 'YYYY-MM-DD', 'time' => 'HH:MM']
function getSimTime($simDatetimeStr = null) {
    if (!empty($simDatetimeStr)) {
        // Parse simulated datetime
        $ts = strtotime($simDatetimeStr);
        if ($ts) {
            return [
                'timestamp' => $ts,
                'datetime'  => date('Y-m-d H:i:s', $ts),
                'date'      => date('Y-m-d', $ts),
                'time'      => date('H:i', $ts),
                'is_sim'    => true
            ];
        }
    }
    return [
        'timestamp' => time(),
        'datetime'  => date('Y-m-d H:i:s'),
        'date'      => date('Y-m-d'),
        'time'      => date('H:i'),
        'is_sim'    => false
    ];
}

// Helper: Check Office Network (WiFi Kantor)
function isConnectedToOfficeNetwork() {
    // 1. Daftar Subnet Kantor yang Diizinkan (Lokal)
    // SSID: STAFF_BIOTROP (101), GUEST_BIOTROP (102)
    $allowed_subnets = ['192.168.101.', '192.168.102.'];
    
    // 2. Daftar IP Public Kantor (Jika lewat Internet/NAT)
    // Masukkan IP Public yang muncul di pesan error Anda: 36.88.167.27
    // Jika berubah-ubah (Dynamic IP), ini perlu diupdate berkala atau gunakan Range.
    $allowed_public_ips = ['36.88.167.27']; 

    $remote = $_SERVER['REMOTE_ADDR'];

    // Cek Localhost
    if ($remote === '127.0.0.1' || $remote === '::1') return true;

    // Cek Subnet Lokal
    foreach ($allowed_subnets as $prefix) {
        if (strpos($remote, $prefix) === 0) return true;
    }

    // Cek IP Public (NAT)
    if (in_array($remote, $allowed_public_ips)) return true;

    // Optional: Log IP yang gagal untuk debugging user
    // error_log("Absensi Gagal. IP User: " . $remote);

    return false;
}

// Check Auth
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// --- ADMIN: DASHBOARD STATS (Department-Aware) ---
if ($method === 'GET' && $action === 'admin_dashboard_stats') {
    $today = date('Y-m-d');

    // --- Determine department filter (same logic as get_all_assignments & admin_monitor) ---
    $deptFilter   = null;
    $myName       = $_SESSION['full_name'] ?? '';

    if (!empty($myName)) {
        $safeName = $conn->real_escape_string($myName);
        $mChk = $conn->query("SELECT department, role_title FROM employees WHERE full_name = '$safeName' LIMIT 1");
        if ($mChk && $mChk->num_rows > 0) {
            $me     = $mChk->fetch_assoc();
            $myDept = strtoupper(trim($me['department']));
            $myRole = strtoupper(trim($me['role_title']));

            $isSuperAdmin = (isset($_SESSION['actual_role']) && $_SESSION['actual_role'] === 'super admin');

            if (!$isSuperAdmin) {
                $deptFilter = $conn->real_escape_string($me['department']);
            }
        }
    }

    // Helper: build WHERE clause for department-scoped queries via task_assignments
    $deptJoin  = $deptFilter ? " JOIN users u2 ON ta.user_id = u2.id JOIN employees e2 ON u2.employee_id = e2.id" : "";
    $deptWhere = $deptFilter ? " AND e2.department = '$deptFilter'" : "";

    // Helper: build WHERE for attendance/work_sessions via users→employees
    $deptJoinWS  = $deptFilter ? " JOIN users uw ON ws.user_id = uw.id JOIN employees ew ON uw.employee_id = ew.id" : "";
    $deptWhereWS = $deptFilter ? " AND ew.department = '$deptFilter'" : "";

    $deptJoinAtt  = $deptFilter ? " JOIN users ua ON a.user_id = ua.id JOIN employees ea ON ua.employee_id = ea.id" : "";
    $deptWhereAtt = $deptFilter ? " AND ea.department = '$deptFilter'" : "";

    // 1. Total employees in scope
    if ($deptFilter) {
        $r1 = $conn->query("SELECT COUNT(DISTINCT u.id) as cnt FROM users u JOIN employees e ON u.employee_id = e.id WHERE e.department = '$deptFilter'");
    } else {
        $r1 = $conn->query("SELECT COUNT(DISTINCT u.id) as cnt FROM users u JOIN employees e ON u.employee_id = e.id");
    }
    $totalEmp = $r1 ? (int)$r1->fetch_assoc()['cnt'] : 0;

    // 2. Active work sessions RIGHT NOW in scope (pegawai sedang start work)
    $r2 = $conn->query("SELECT COUNT(*) as cnt FROM work_sessions ws $deptJoinWS
                        WHERE ws.status = 'active' $deptWhereWS");
    $activeSessions = $r2 ? (int)$r2->fetch_assoc()['cnt'] : 0;

    // 2b. Tasks scheduled today (for Penjadwalan badge)
    $r2b = $conn->query("SELECT COUNT(*) as cnt FROM task_assignments ta $deptJoin
                         WHERE ta.start_date <= '$today' AND (ta.end_date IS NULL OR ta.end_date >= '$today') $deptWhere");
    $tasksToday = $r2b ? (int)$r2b->fetch_assoc()['cnt'] : 0;

    // 3. Pending approvals in scope
    $r3 = $conn->query("SELECT COUNT(*) as cnt FROM work_sessions ws $deptJoinWS
                        WHERE ws.status = 'pending_approval' $deptWhereWS");
    $pendingApprovals = $r3 ? (int)$r3->fetch_assoc()['cnt'] : 0;

    // 4. Attendance today in scope
    $r4 = $conn->query("SELECT COUNT(*) as cnt FROM attendance a $deptJoinAtt
                        WHERE a.date = '$today' $deptWhereAtt");
    $attendanceToday = $r4 ? (int)$r4->fetch_assoc()['cnt'] : 0;

    // 5. Late today in scope
    $r5 = $conn->query("SELECT COUNT(*) as cnt FROM attendance a $deptJoinAtt
                        WHERE a.date = '$today' AND a.status = 'late' $deptWhereAtt");
    $lateToday = $r5 ? (int)$r5->fetch_assoc()['cnt'] : 0;

    echo json_encode([
        "status" => "success",
        "data" => [
            "total_employees"   => $totalEmp,
            "active_sessions"   => $activeSessions,
            "tasks_today"       => $tasksToday,
            "pending_approvals" => $pendingApprovals,
            "attendance_today"  => $attendanceToday,
            "late_today"        => $lateToday
        ]

    ]);
    exit;
}

// --- GET TASKS (From task_assignments) ---
if ($method === 'GET' && $action === 'get_tasks') {
    // Only fetch tasks assigned to this user that are still active (not expired)
    $sql = "SELECT DISTINCT title as task_name FROM task_assignments 
            WHERE user_id = '$user_id' 
            AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY title ASC";
    $result = $conn->query($sql);
    $task_list = [];
    
    while($row = $result->fetch_assoc()) {
        $task_list[] = ["task_name" => $row['task_name']];
    }
    
    // If empty (maybe new user), you might want to show default general tasks or empty
    // For now we just return what is assigned.

    echo json_encode(["status" => "success", "data" => $task_list]);
    exit;
}

// --- GET ACTIVE SESSION (Unchanged, relies on work_sessions) ---
if ($method === 'GET' && $action === 'check_active_session') {
    $sql = "SELECT ws.* FROM work_sessions ws 
            WHERE ws.user_id = '$user_id' AND ws.status IN ('active', 'revision') 
            ORDER BY ws.start_time DESC";
    $result = $conn->query($sql);
    
    $sessions = [];
    if ($result->num_rows > 0) {
        while($sess = $result->fetch_assoc()) {
            // Calculate elapsed time on server to avoid timezone issues on client
            $sess['elapsed_seconds'] = time() - strtotime($sess['start_time']);
            $sessions[] = $sess;
        }
        echo json_encode(["status" => "active", "sessions" => $sessions]);
    } else {
        echo json_encode(["status" => "inactive"]);
    }
    exit;
}

// --- START WORKING (Insert into start log to gps_logs) ---
if ($method === 'POST' && $action === 'start_work') {
    $input = getInput();
    $task_name = $conn->real_escape_string($input['task_name']);
    $lat = $conn->real_escape_string($input['lat']);
    $lng = $conn->real_escape_string($input['lng']);
    
    // Block check removed per request
    $sql = "INSERT INTO work_sessions (user_id, task_name, start_time, status) VALUES ('$user_id', '$task_name', NOW(), 'active')";
    if ($conn->query($sql)) {
        $session_id = $conn->insert_id;
        
        // Log Initial GPS to gps_logs
        $conn->query("INSERT INTO gps_logs (session_id, latitude, longitude, trigger_type) VALUES ('$session_id', '$lat', '$lng', 'start')");
        
        // Update task_assignment status to 'in_progress' if matches?
        // Optional logic: Find task assignment with this title and set status.
        $conn->query("UPDATE task_assignments SET status='in_progress' WHERE user_id='$user_id' AND title='$task_name' AND status='pending'");
        
        echo json_encode(["status" => "success", "session_id" => $session_id]);
    } else {
        echo json_encode(["status" => "error", "message" => "DB Error: " . $conn->error]);
    }
    exit;
}

// --- UPDATE LOCATION (gps_logs) ---
if ($method === 'POST' && $action === 'update_location') {
    $input = getInput();
    $session_id = $conn->real_escape_string($input['session_id']);
    $lat = $conn->real_escape_string($input['lat']);
    $lng = $conn->real_escape_string($input['lng']);
    
    $sql = "INSERT INTO gps_logs (session_id, latitude, longitude, trigger_type) VALUES ('$session_id', '$lat', '$lng', 'update')";
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
    exit;
} 

// --- INSTANT REPORT (Conditional Tasks) ---
if ($method === 'POST' && $action === 'submit_instant_report') {
    $task_name = $conn->real_escape_string($_POST['task_name']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $lat = $conn->real_escape_string($_POST['lat']);
    $lng = $conn->real_escape_string($_POST['lng']);

    // Handle File Upload
    $evidence_path = '';
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] == 0) {
        if ($_FILES['evidence']['size'] > 5 * 1024 * 1024) {
            echo json_encode(["status" => "error", "message" => "Ukuran file terlalu besar (Max 5MB)"]);
            exit;
        }

        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $filename = time() . "_" . basename($_FILES["evidence"]["name"]);
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES["evidence"]["tmp_name"], $target_file)) {
            $evidence_path = "uploads/" . $filename;
        }
    }

    // Pending block check removed per request
    // 1. Create Work Session (Instant Complete)
    $sql = "INSERT INTO work_sessions (user_id, task_name, start_time, end_time, status) 
            VALUES ('$user_id', '$task_name', NOW(), NOW(), 'pending_approval')";
            
    if ($conn->query($sql)) {
        $session_id = $conn->insert_id;
        
        // 2. Log Single GPS Point (Trigger = 'instant')
        $conn->query("INSERT INTO gps_logs (session_id, latitude, longitude, trigger_type) VALUES ('$session_id', '$lat', '$lng', 'instant')");
        
        // 3. Insert Evidence
        $conn->query("INSERT INTO evidence (session_id, file_path, note) VALUES ('$session_id', '$evidence_path', '$notes')");
        
        echo json_encode(["status" => "success", "message" => "Rekap dikirim, menunggu approval Manager."]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}

// --- STOP WORKING & UPLOAD EVIDENCE (evidence table + gps_logs) ---
if ($method === 'POST' && $action === 'stop_work') {
    $session_id = $conn->real_escape_string($_POST['session_id']);
    $lat = $conn->real_escape_string($_POST['lat']);
    $lng = $conn->real_escape_string($_POST['lng']);
    $notes = $conn->real_escape_string($_POST['notes']);
    
    // Handle File Upload
    $evidence_path = '';
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] == 0) {
        if ($_FILES['evidence']['size'] > 5 * 1024 * 1024) {
            echo json_encode(["status" => "error", "message" => "Ukuran file terlalu besar (Max 5MB)"]);
            exit;
        }

        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $filename = time() . "_" . basename($_FILES["evidence"]["name"]);
        $target_file = $target_dir . $filename;
        
        if (move_uploaded_file($_FILES["evidence"]["tmp_name"], $target_file)) {
            $evidence_path = "uploads/" . $filename;
        }
    }

    // Update Session
    $sql = "UPDATE work_sessions SET 
            end_time = NOW(), 
            status = 'pending_approval'
            WHERE id = '$session_id' AND user_id = '$user_id'";
            
    if ($conn->query($sql)) {
        // Log Final GPS
        $conn->query("INSERT INTO gps_logs (session_id, latitude, longitude, trigger_type) VALUES ('$session_id', '$lat', '$lng', 'stop')");
        
        // Insert or Update into EVIDENCE table
        $chkEv = $conn->query("SELECT id FROM evidence WHERE session_id='$session_id'");
        if ($chkEv->num_rows > 0) {
            $evId = $chkEv->fetch_assoc()['id'];
            $sqlEv = "UPDATE evidence SET note='$notes'";
            if ($evidence_path) $sqlEv .= ", file_path='$evidence_path'";
            $sqlEv .= " WHERE id='$evId'";
            $conn->query($sqlEv);
        } else {
            $conn->query("INSERT INTO evidence (session_id, file_path, note) VALUES ('$session_id', '$evidence_path', '$notes')");
        }
        
        // Update assignment status to pending so it waits for manager approval?
        // We will just leave it.
        
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}

// --- ADMIN: GET ALL MONITORING DATA ---
if ($method === 'GET' && $action === 'admin_monitor') {
    // --- Department Filter Logic ---
    $deptFilter = null;
    $myName = $_SESSION['full_name'] ?? '';
    if (!empty($myName)) {
        $mChk = $conn->query("SELECT department FROM employees WHERE full_name = '" . $conn->real_escape_string($myName) . "' LIMIT 1");
        if ($mChk && $mChk->num_rows > 0) {
             $me = $mChk->fetch_assoc();
             $isSuperAdmin = (isset($_SESSION['actual_role']) && $_SESSION['actual_role'] === 'super admin');
             if (!$isSuperAdmin) {
                 $deptFilter = $conn->real_escape_string($me['department']);
             }
        }
    }

    $sql = "SELECT ws.*, e.full_name, ws.task_name,
            COALESCE((SELECT type FROM task_assignments WHERE title = ws.task_name AND user_id = ws.user_id LIMIT 1), 'Kondisional') as task_type,
            (SELECT COUNT(*) FROM gps_logs gl WHERE gl.session_id = ws.id) as log_count,
            ev.file_path as evidence_file,
            ev.note as evidence_text
            FROM work_sessions ws
            JOIN users u ON ws.user_id = u.id
            JOIN employees e ON u.employee_id = e.id
            LEFT JOIN evidence ev ON ws.id = ev.session_id";

    if ($deptFilter) {
        $sql .= " WHERE e.department = '$deptFilter'";
    }

    $sql .= " ORDER BY ws.start_time DESC";
            
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        $row['logs'] = [];
        // Optional: Fetch logs for map path
        $logs = $conn->query("SELECT latitude, longitude, log_time, trigger_type as type FROM gps_logs WHERE session_id = '" . $row['id'] . "' ORDER BY log_time ASC");
        while($l = $logs->fetch_assoc()) {
            $row['logs'][] = $l;
        }
        $data[] = $row;
    }
    
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// --- ADMIN: ASSIGN TASK ---
if ($method === 'POST' && $action === 'assign_task') {
    $input = getInput();
    $user_id = $conn->real_escape_string($input['user_id']);
    $title = $conn->real_escape_string($input['title']);
    $desc = $conn->real_escape_string($input['description']);
    $type = $conn->real_escape_string($input['type']);
    $start = $conn->real_escape_string($input['start_date']);
    $end = $conn->real_escape_string($input['end_date']);

    $sql = "INSERT INTO task_assignments (user_id, title, description, type, start_date, end_date, status) 
            VALUES ('$user_id', '$title', '$desc', '$type', '$start', '$end', 'pending')";
            
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => "Tugas berhasil dijadwalkan"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}

// --- ADMIN: GET ASSIGNMENTS ---
if ($method === 'GET' && $action === 'get_all_assignments') {
    // Filter Logic for Manager
    $deptFilter = null;
    $myName = $_SESSION['full_name'] ?? ''; // From Auth
    
    if (!empty($myName)) {
        // Check Manager/Admin Role & Department
        $mChk = $conn->query("SELECT department, role_title FROM employees WHERE full_name = '$myName'");
        if ($mChk && $mChk->num_rows > 0) {
             $me = $mChk->fetch_assoc();
             $myDept = strtoupper(trim($me['department']));
             $myRole = strtoupper(trim($me['role_title']));
             
             // Super Admins see everything
             $isSuperAdmin = (isset($_SESSION['actual_role']) && $_SESSION['actual_role'] === 'super admin');

             // If not super admin, filter by own department
             if (!$isSuperAdmin) {
                 $deptFilter = $conn->real_escape_string($me['department']);
             }
        }
    }

    $sql = "SELECT ta.*, e.full_name, e.department 
            FROM task_assignments ta 
            JOIN users u ON ta.user_id = u.id 
            LEFT JOIN employees e ON u.employee_id = e.id";
    
    if ($deptFilter) {
        $sql .= " WHERE e.department = '$deptFilter'";
    }
            
    $sql .= " ORDER BY ta.created_at DESC";
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// --- USER: GET ASSIGNMENTS ---
if ($method === 'GET' && $action === 'get_my_schedule') {
    $my_id = $_SESSION['user_id'];
    // Hanya tampilkan jadwal yang belum berakhir (end_date >= hari ini)
    // Jika end_date NULL, tetap tampilkan (jadwal tanpa batas waktu)
    $sql = "SELECT * FROM task_assignments 
            WHERE user_id = '$my_id' 
            AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY start_date ASC";
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// --- USER: GET HISTORY ---
if ($method === 'GET' && $action === 'get_user_history') {
    $my_id = $_SESSION['user_id'];
    
    $sql = "SELECT ws.*, ev.file_path, ev.note 
            FROM work_sessions ws 
            LEFT JOIN evidence ev ON ws.id = ev.session_id 
            WHERE ws.user_id = '$my_id' 
            ORDER BY ws.start_time DESC";
            
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        // Calculate duration if completed
        if ($row['status'] == 'completed' && $row['end_time']) {
            $start = new DateTime($row['start_time']);
            $end = new DateTime($row['end_time']);
            $diff = $start->diff($end);
            $row['duration_text'] = $diff->format('%H:%I:%S');
        } else {
            $row['duration_text'] = '-';
        }
        $data[] = $row;
    }
    
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// --- GET ALL USERS (Simple helper for dropdown) ---
// --- GET ALL USERS (Modified: Fetch from ASSESSMENTS as requested) ---
// --- GET ALL USERS (Modified: Auto-Sync with Assessments) ---
if ($method === 'GET' && $action === 'get_users_list') {
    
    // 1. SYNC LOGIC: Cari pegawai di tabel employees yang belum punya akun di tabel users
    $syncSql = "SELECT id, full_name, role_title, nik FROM employees 
                WHERE id NOT IN (SELECT employee_id FROM users WHERE employee_id IS NOT NULL)";
    $missing = $conn->query($syncSql);
    
    if ($missing && $missing->num_rows > 0) {
        while($row = $missing->fetch_assoc()) {
            $name = $conn->real_escape_string($row['full_name']);
            $empId = $row['id'];
            $roleTitle = $row['role_title'] ?? '';
            
            // Password only (username is NIK from employee table)
            $defaultPass = 'seabiotrop68';
            
            // Determine User Role Dynamically
            $adminRoles = ['manager', 'manajer', 'spv', 'supervisor', 'direktur', 'head', 'chief', 'admin', 'hr', 'koordinator'];
            $userRole = 'user';
            foreach ($adminRoles as $ar) {
                if (stripos($roleTitle, $ar) !== false) {
                    $userRole = 'admin';
                    break;
                }
            }
            
            // Insert ke table users (Link via employee_id)
            $conn->query("INSERT INTO users (employee_id, password, role) VALUES ('$empId', '$defaultPass', '$userRole')");
        }
    }

    // 2. Fetch directly from users table (Now updated)
    // Filter by Dept if Manager
    $deptFilter = null;
    $excludeName = null;
    $myName = $_SESSION['full_name'] ?? ''; // Assuming session full_name is set upon login in auth.php

    if (!empty($myName)) {
        // Check if I am an admin that should be filtered by department
        $mChk = $conn->query("SELECT department, role_title FROM employees WHERE full_name = '$myName'");
        if ($mChk && $mChk->num_rows > 0) {
             $me = $mChk->fetch_assoc();
             $myDept = strtoupper(trim($me['department']));
             $myRole = strtoupper(trim($me['role_title']));
             
             $isSuperAdmin = (isset($_SESSION['actual_role']) && $_SESSION['actual_role'] === 'super admin');

             if (!$isSuperAdmin) {
                 $deptFilter = $conn->real_escape_string($me['department']);
             }
             // Exclude self from the "Pilih Pegawai" schedule dropdown
             $excludeName = $conn->real_escape_string($myName);
        }
    }

    $sql = "SELECT DISTINCT u.id, e.full_name 
            FROM users u 
            JOIN employees e ON u.employee_id = e.id 
            WHERE 1=1";

    if ($deptFilter) {
        $sql .= " AND e.department = '$deptFilter'";
    }
    if ($excludeName) {
        $sql .= " AND e.full_name != '$excludeName'";
    }
            
    $sql .= " ORDER BY e.full_name ASC";
    $result = $conn->query($sql);
    $data = [];
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// --- ATTENDANCE SYSTEM ---

// 1. Get Today's Attendance
if ($method === 'GET' && $action === 'get_attendance_today') {
    $my_id = $_SESSION['user_id'];
    
    // Support simulation datetime via GET param
    $simDT = isset($_GET['sim_datetime']) ? $_GET['sim_datetime'] : null;
    $simTime = getSimTime($simDT);
    $date     = $simTime['date'];     // Simulated or real date
    $nowExpr  = $simTime['is_sim'] ? "'" . $simTime['datetime'] . "'" : 'NOW()';
    
    // First, try to get today's record
    $sql = "SELECT a.*, e.position, e.type FROM attendance a 
            JOIN users u ON a.user_id = u.id
            JOIN employees e ON u.employee_id = e.id
            WHERE a.user_id = '$my_id' AND a.date = '$date'";
    $result = $conn->query($sql);
    
    $row = null;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        // Fallback: Check if there's an open session (no clock_out) from the previous day
        $yesterday = date('Y-m-d', $simTime['timestamp'] - 86400);
        $cutoffDT  = date('Y-m-d H:i:s', $simTime['timestamp'] - 86400); // 24h ago
        $sqlPrev = "SELECT a.*, e.position, e.type FROM attendance a 
                    JOIN users u ON a.user_id = u.id
                    JOIN employees e ON u.employee_id = e.id
                    WHERE a.user_id = '$my_id' 
                    AND a.date = '$yesterday' 
                    AND a.clock_out_time IS NULL 
                    AND a.clock_in_time >= '$cutoffDT'
                    ORDER BY a.clock_in_time DESC LIMIT 1";
        $resPrev = $conn->query($sqlPrev);
        if ($resPrev && $resPrev->num_rows > 0) {
            $row = $resPrev->fetch_assoc();
            $row['is_overnight'] = true;
        }
    }

    if ($row) {
        // Calculate target_out_time
        $pos = $row['position'] ?? '';
        $type = $row['type'] ?? '';
        $isSecurity = ((stripos($pos, 'Security') !== false) || (stripos($pos, 'Satpam') !== false)) && (stripos($pos, 'Chief') === false);
        $isPrama = (stripos($pos, 'Pramubakti') !== false) && (stripos($type, 'outsourcing') !== false);

        $targetOut = '16:00'; // Default
        if ($isSecurity) {
            $inTime = date('H:i', strtotime($row['clock_in_time']));
            // Shift 1 (Pagi): IN 05:00-16:59 -> OUT 19:00
            // Shift 2 (Malam): IN 17:00-04:59 -> OUT 07:00 (next day)
            if ($inTime >= '05:00' && $inTime < '17:00') {
                $targetOut = '19:00';
            } else {
                $targetOut = '07:00';
            }
        } elseif ($isPrama) {
            $targetOut = '16:00';
        }

        $row['target_out_time'] = $targetOut;
        echo json_encode(["status" => "success", "data" => $row, "sim" => $simTime['is_sim']]);
    } else {
        echo json_encode(["status" => "empty", "sim" => $simTime['is_sim']]);
    }
    exit;
    exit;
}

// Helper: Validate QR
function validateQrToken($conn, $token) {
    if (empty($token)) return "QR Code wajib discan!";
    
    $token = $conn->real_escape_string($token);
    $now = date('Y-m-d H:i:s');
    
    $sql = "SELECT * FROM qr_tokens WHERE token = '$token' AND is_used = 0 AND expires_at > '$now'";
    $res = $conn->query($sql);
    
    if ($res->num_rows > 0) {
        // Token Valid! Tidak perlu di-delete atau set is_used=1 jika ingin multi-user scan (antrian).
        // Tapi user minta "generate baru setiap ada orang scan".
        // Opsi terbaik: Biarkan valid selama durasi (misal 15 detik) agar orang yang lagi antri scan tidak gagal.
        // Cukup validasi expired saja.
        return true; 
    }
    return "QR Code Kadaluarsa atau Tidak Valid. Silahkan scan ulang.";
}

// 2. Clock In
if ($method === 'POST' && $action === 'clock_in') {
    $my_id = $_SESSION['user_id'];
    $input = getInput();
    $lat = $conn->real_escape_string($input['lat']);
    $lng = $conn->real_escape_string($input['lng']);
    $work_type = isset($input['work_type']) ? $conn->real_escape_string($input['work_type']) : 'WFO';
    
    // Simulation support: frontend can pass sim_datetime for testing
    $simDT   = $conn->real_escape_string($input['sim_datetime'] ?? '');
    $simTime = getSimTime($simDT);
    $date        = $simTime['date'];
    $currentTime = $simTime['time'];
    // For SQL insertion: use the simulated datetime if provided, else NOW()
    $nowExpr = $simTime['is_sim'] ? "'" . $simTime['datetime'] . "'" : 'NOW()';
    
    // 0. Check Network (skip in simulation mode or WFH)
    if (!$simTime['is_sim'] && $work_type === 'WFO' && !isConnectedToOfficeNetwork()) {
        echo json_encode(["status" => "error", "message" => "Gagal Absen! Anda tidak terhubung ke WiFi Kantor untuk WFO."]);
        exit;
    }
    
    if ($work_type === 'WFH') {
        $uResWfh = $conn->query("SELECT wfh_lat, wfh_lng FROM users WHERE id = '$my_id'");
        if ($uResWfh && $uResWfh->num_rows > 0) {
            $uWfh = $uResWfh->fetch_assoc();
            if (empty($uWfh['wfh_lat']) || empty($uWfh['wfh_lng'])) {
                // First WFH ever - Register home location
                $conn->query("UPDATE users SET wfh_lat = '$lat', wfh_lng = '$lng' WHERE id = '$my_id'");
            } else {
                // Validate WFH radius
                $wLat = floatval($uWfh['wfh_lat']);
                $wLng = floatval($uWfh['wfh_lng']);
                $cLat = floatval($lat);
                $cLng = floatval($lng);
                
                $earth_radius = 6371; // km
                $dLat = deg2rad($cLat - $wLat);
                $dLon = deg2rad($cLng - $wLng);
                $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($wLat)) * cos(deg2rad($cLat)) * sin($dLon/2) * sin($dLon/2);
                $c = 2 * asin(sqrt($a));
                $d = $earth_radius * $c;
                
                if ($d > 0.5) { // Max 500 meters from registered home
                    echo json_encode(["status" => "error", "message" => "Gagal Absen WFH! Jarak terlalu jauh (" . round($d, 2) . " km) dari lokasi WFH terdaftar."]);
                    exit;
                }
            }
        }
    }

    // Block if there's already a clock-in for this date
    $check = $conn->query("SELECT id FROM attendance WHERE user_id='$my_id' AND date='$date'");
    if ($check->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Anda sudah melakukan clock-in hari ini " . ($simTime['is_sim'] ? "(simulasi: $date)" : "") . "."]);
        exit;
    }
    
    // Also block if there's an open session from yesterday (Shift 2 not clocked out yet)
    $yesterday  = date('Y-m-d', $simTime['timestamp'] - 86400);
    $cutoffDT   = date('Y-m-d H:i:s', $simTime['timestamp'] - 86400);
    $openCheck  = $conn->query("SELECT id FROM attendance WHERE user_id='$my_id' AND date='$yesterday' AND clock_out_time IS NULL AND clock_in_time >= '$cutoffDT'");
    if ($openCheck->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Anda masih memiliki sesi Shift 2 kemarin yang belum Clock Out. Harap Clock Out terlebih dahulu."]);
        exit;
    }

    // Get Employee Info
    $position = '';
    $uRes = $conn->query("SELECT e.full_name, e.position, e.type FROM users u 
                          JOIN employees e ON u.employee_id = e.id 
                          WHERE u.id = '$my_id'");
    if ($uRes && $uRes->num_rows > 0) {
        $uRow = $uRes->fetch_assoc();
        $position = $uRow['position'] ?? '';
    }

    // Determine Status (Late Logic)
    $status = 'ontime';
    $position = $uRow['position'] ?? '';
    $type = $uRow['type'] ?? '';

    $isSecurity = ((stripos($position, 'Security') !== false) || (stripos($position, 'Satpam') !== false)) && (stripos($position, 'Chief') === false);
    $isPramubaktiOutsourcing = (stripos($position, 'Pramubakti') !== false) && (stripos($type, 'outsourcing') !== false);

    if ($isSecurity) {
        // Shift 1 (Pagi): check-in 05:00-16:59, terlambat jika > 07:00 (tanpa toleransi)
        // Shift 2 (Malam): check-in 17:00-04:59 (next day), terlambat jika > 19:00 (tanpa toleransi)
        if ($currentTime >= '05:00' && $currentTime < '17:00') {
            if ($currentTime > '07:00') $status = 'late';
        } else {
            if ($currentTime > '19:00') $status = 'late';
        }
    } elseif ($isPramubaktiOutsourcing) {
        // Pramubakti Outsourcing: masuk jam 07:00
        if ($currentTime > '07:00') $status = 'late';
    } else {
        // Regular: masuk jam 08:00
        if ($currentTime > '08:00') $status = 'late';
    }

    // Shift Label
    $shiftLabel = 'Regular Shift';
    if ($isSecurity) {
        $shiftLabel = ($currentTime >= '05:00' && $currentTime < '17:00') ? 'Shift 1 (Pagi)' : 'Shift 2 (Malam)';
    } elseif ($isPramubaktiOutsourcing) {
        $shiftLabel = 'Shift Pramubakti (07:00 - 16:00)';
    }

    // Insert attendance record — date uses simulated date, clock_in_time uses simulated datetime
    $sql = "INSERT INTO attendance (user_id, date, clock_in_time, clock_in_lat, clock_in_lng, status, work_type) 
            VALUES ('$my_id', '$date', $nowExpr, '$lat', '$lng', '$status', '$work_type')";
            
    if ($conn->query($sql)) {
        echo json_encode([
            "status"      => "success", 
            "clock_in_time" => $simTime['time'],
            "status_att"  => $status,
            "shift_info"  => $shiftLabel,
            "sim"         => $simTime['is_sim'],
            "sim_datetime" => $simTime['datetime']
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}

// 3. Clock Out
if ($method === 'POST' && $action === 'clock_out') {
    $my_id = $_SESSION['user_id'];
    $input = getInput();
    $lat = $conn->real_escape_string($input['lat']);
    $lng = $conn->real_escape_string($input['lng']);
    
    // Simulation support
    $simDT   = $conn->real_escape_string($input['sim_datetime'] ?? '');
    $simTime = getSimTime($simDT);
    $nowExpr = $simTime['is_sim'] ? "'" . $simTime['datetime'] . "'" : 'NOW()';
    $nowTs   = $simTime['timestamp'];
    
    // Find the latest open attendance record (no filter by date — supports overnight Shift 2)
    $check = $conn->query("SELECT id, date, clock_in_time, clock_out_time, work_type FROM attendance 
                           WHERE user_id='$my_id' AND clock_out_time IS NULL 
                           ORDER BY clock_in_time DESC LIMIT 1");
    
    if ($check->num_rows == 0) {
        echo json_encode(["status" => "error", "message" => "Anda belum clock-in atau sudah clock-out sebelumnya."]);
        exit;
    }
    
    $row = $check->fetch_assoc();
    $work_type = $row['work_type'];
    
    // 0. Check Network (skip in simulation mode or WFH)
    if (!$simTime['is_sim'] && $work_type === 'WFO' && !isConnectedToOfficeNetwork()) {
        echo json_encode(["status" => "error", "message" => "Gagal Absen! Anda tidak terhubung ke WiFi Kantor untuk WFO."]);
        exit;
    }
    
    if ($work_type === 'WFH') {
        $uResWfh = $conn->query("SELECT wfh_lat, wfh_lng FROM users WHERE id = '$my_id'");
        if ($uResWfh && $uResWfh->num_rows > 0) {
            $uWfh = $uResWfh->fetch_assoc();
            if (!empty($uWfh['wfh_lat']) && !empty($uWfh['wfh_lng'])) {
                $wLat = floatval($uWfh['wfh_lat']);
                $wLng = floatval($uWfh['wfh_lng']);
                $cLat = floatval($lat);
                $cLng = floatval($lng);
                
                $earth_radius = 6371; // km
                $dLat = deg2rad($cLat - $wLat);
                $dLon = deg2rad($cLng - $wLng);
                $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($wLat)) * cos(deg2rad($cLat)) * sin($dLon/2) * sin($dLon/2);
                $c = 2 * asin(sqrt($a));
                $d = $earth_radius * $c;
                
                if ($d > 0.5) { // 500 meters
                    echo json_encode(["status" => "error", "message" => "Gagal Absen Pulang WFH! Jarak terlalu jauh (" . round($d, 2) . " km) dari lokasi WFH terdaftar."]);
                    exit;
                }
            }
        }
    }
    
    // Safety: reject if open session is older than 24 hours (from simulated now)
    $inTs = strtotime($row['clock_in_time']);
    if (($nowTs - $inTs) > (24 * 3600)) {
        echo json_encode(["status" => "error", "message" => "Sesi anda sudah kadaluwarsa (>24 jam). Silahkan hubungi Admin."]);
        exit;
    }

    // Update: clock_out_time = simulated or real NOW()
    // The 'date' column stays as the original clock-in date (shift start date) — this is CORRECT.
    // clock_out_time will hold the actual/simulated datetime of check-out (can be next day for Shift 2).
    $row_id = $row['id'];
    $sql = "UPDATE attendance SET clock_out_time = $nowExpr, clock_out_lat = '$lat', clock_out_lng = '$lng' 
            WHERE id = '$row_id'";
            
    if ($conn->query($sql)) {
        echo json_encode([
            "status"         => "success", 
            "clock_out_time" => $simTime['time'],
            "clock_out_date" => $simTime['date'],
            "shift_date"     => $row['date'], // original shift date
            "is_next_day"    => ($simTime['date'] !== $row['date']),
            "sim"            => $simTime['is_sim']
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}

// 4. Get Attendance History
if ($method === 'GET' && $action === 'get_attendance_history') {
    $my_id = $_SESSION['user_id'];
    
    $sql = "SELECT * FROM attendance WHERE user_id = '$my_id' ORDER BY date DESC LIMIT 30";
    $result = $conn->query($sql);
    
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}
// 5. Get All Attendance History (Admin)
if ($method === 'GET' && $action === 'get_all_attendance_history') {
    $whereClauses = ["1=1"];

    // --- Department Filter Logic ---
    $deptFilter = null;
    $myName = $_SESSION['full_name'] ?? '';
    if (!empty($myName)) {
        $mChk = $conn->query("SELECT department FROM employees WHERE full_name = '" . $conn->real_escape_string($myName) . "' LIMIT 1");
        if ($mChk && $mChk->num_rows > 0) {
             $me = $mChk->fetch_assoc();
             $isSuperAdmin = (isset($_SESSION['actual_role']) && $_SESSION['actual_role'] === 'super admin');
             if (!$isSuperAdmin) {
                 $deptFilter = $conn->real_escape_string($me['department']);
             }
        }
    }

    if ($deptFilter) {
        $whereClauses[] = "e.department = '$deptFilter'";
    }

    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $start = $conn->real_escape_string($_GET['start_date']);
        $whereClauses[] = "a.date >= '$start'";
    }
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $end = $conn->real_escape_string($_GET['end_date']);
        $whereClauses[] = "a.date <= '$end'";
    }

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $s = $conn->real_escape_string($_GET['search']);
        $whereClauses[] = "e.full_name LIKE '%$s%'";
    }

    $whereSql = implode(" AND ", $whereClauses);

    $sql = "SELECT a.*, e.full_name as user_name, e.position as user_position
            FROM attendance a 
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN employees e ON u.employee_id = e.id
            WHERE $whereSql
            ORDER BY a.date DESC, a.clock_in_time DESC";

    $result = $conn->query($sql);
    $data = [];
    if ($result) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// --- ADMIN: APPROVE TASK ---
if ($method === 'POST' && $action === 'admin_approve_task') {
    $input = getInput();
    $session_id = $conn->real_escape_string($input['session_id']);
    
    $sql = "UPDATE work_sessions SET status = 'completed' WHERE id = '$session_id'";
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}

// --- ADMIN: REVISE TASK ---
if ($method === 'POST' && $action === 'admin_revise_task') {
    $input = getInput();
    $session_id = $conn->real_escape_string($input['session_id']);
    $note = $conn->real_escape_string($input['note']);
    
    $sql = "UPDATE work_sessions SET status = 'revision', manager_note = '$note' WHERE id = '$session_id'";
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}
?>
