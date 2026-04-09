<?php
// api.php
session_start(); // Start session to access user info
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

include 'connection.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$currentYear = date('Y');

// --- ACCESS CONTROL LOGIC ---
$deptFilter = null;
if (isset($_SESSION['full_name'])) {
    $myName = $conn->real_escape_string($_SESSION['full_name']);
    
    // Check Employee Details (Role & Dept)
    // We check if the logged in user is a "Manager" in the employee record
    $chk = $conn->query("SELECT department, role_title FROM employees WHERE full_name='$myName' LIMIT 1");
    if ($chk && $chk->num_rows > 0) {
        $me = $chk->fetch_assoc();
        
        $myDept = strtoupper(trim($me['department']));
        $myRole = strtoupper(trim($me['role_title']));
        
        $isSuperAdmin = (isset($_SESSION['actual_role']) && $_SESSION['actual_role'] === 'super admin');
        
        if (!$isSuperAdmin) {
             $deptFilter = $conn->real_escape_string($me['department']);
        }
    }
}

// --- GET DATA ---
if ($method === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_all_assessments') {
        $sql = "SELECT a.id, a.assessment_date, a.total_score, a.period,
                e.id as emp_id, e.full_name, e.department, e.role_title, e.nik, e.position, e.type
                FROM assessments a
                JOIN employees e ON a.employee_id = e.id";
        
        if ($deptFilter) {
            $sql .= " WHERE e.department = '$deptFilter' ";
        }
        
        $sql .= " ORDER BY a.assessment_date DESC";
        $result = $conn->query($sql);
        $data = [];
        if($result){
            while($row = $result->fetch_assoc()){
                $data[] = $row;
            }
        }
        echo json_encode(["status" => "success", "data" => $data]);
        exit;
    }

    if (isset($_GET['action']) && $_GET['action'] === 'get_history') {
         // --- GET HISTORY DETAIL ---
         $refId = $conn->real_escape_string($_GET['id']);
         
         // Fix: If ID is numeric and low, it might be Employee ID. If high or not found, maybe Assessment ID?
         // User directive implies the main table now sends Employee ID.
         // Let's assume input is Employee ID.
            
            // Fetch History: Join Assessment + Employee + Target (Year based on Assessment Date Year)
            $sql = "SELECT a.id, a.assessment_date, a.period, a.data_json, a.extra_score, a.total_score,
                    e.full_name, e.nik, e.department, e.role_title, e.position, e.education, e.tenure, e.certificates, e.type,
                    t.target_3_months, t.target_6_months, t.target_1_year
                    FROM assessments a
                    JOIN employees e ON a.employee_id = e.id
                    LEFT JOIN employee_targets t ON e.id = t.employee_id AND t.year = YEAR(a.assessment_date)
                    WHERE a.employee_id = '$refId'
                    ORDER BY a.assessment_date DESC";
            
            $result = $conn->query($sql);
            $history = [];
            if ($result) {
                while($row = $result->fetch_assoc()) {
                    $jsonItem = json_decode($row['data_json']);
                    if(!$jsonItem) $jsonItem = new stdClass();

                    $jsonItem->id = $row['id'];
                    $jsonItem->date = $row['assessment_date'];
                    $jsonItem->period = $row['period']; // Include period
                    $jsonItem->extra_score = $row['extra_score'];
                    $jsonItem->total_db = $row['total_score'];
                    
                    // Profile always from Master (Joined)
                    $jsonItem->profile = [
                        "name" => $row['full_name'],
                        "empId" => $row['nik'],
                        "dept" => $row['department'],
                        "role" => $row['role_title'],
                        "position" => $row['position'],
                        "education" => $row['education'],
                        "tenure" => $row['tenure'],
                        "certificates" => $row['certificates'],
                        "type" => $row['type']
                    ];
                    
                    // Targets: Prioritize Master Table (JOIN), Fallback to Snapshot (JSON)
                    if(!isset($jsonItem->targets)) $jsonItem->targets = new stdClass();
                    
                    $jsonItem->targets->threeMonth = $row['target_3_months'] ?? ($jsonItem->targets->threeMonth ?? '');
                    $jsonItem->targets->sixMonth = $row['target_6_months'] ?? ($jsonItem->targets->sixMonth ?? '');
                    $jsonItem->targets->oneYear = $row['target_1_year'] ?? ($jsonItem->targets->oneYear ?? '');

                    $history[] = $jsonItem;
                }
            }
            echo json_encode(["status" => "success", "data" => $history]);
            exit;
    }

    // --- GET MAIN TABLE (GROUP BY EMPLOYEE - WITH SCORES FROM JSON) ---
    // 1. Fetch ALL assessments JSON first to aggregate Dimension Scores
    $agg = []; // employee_id => [ dim_key => [sum, count] ]
    
    $sqlAss = "SELECT employee_id, data_json FROM assessments";
    $resAss = $conn->query($sqlAss);
    if ($resAss) {
        while ($r = $resAss->fetch_assoc()) {
            $jd = json_decode($r['data_json'], true);
            if (isset($jd['scores']) && is_array($jd['scores'])) {
                $eid = $r['employee_id'];
                foreach ($jd['scores'] as $dim => $val) {
                    if (!isset($agg[$eid][$dim])) {
                        $agg[$eid][$dim] = ['sum' => 0, 'count' => 0];
                    }
                    // Aggregate the avg score of that dimension
                     $score = isset($val['avg']) ? (float)$val['avg'] : 0;
                     $agg[$eid][$dim]['sum'] += $score;
                     $agg[$eid][$dim]['count']++;
                }
            }
        }
    }

    // 2. Fetch Employee List
    $sql = "SELECT e.id as emp_id, e.full_name, e.nik, e.department, e.role_title, e.position,
            e.education, e.tenure, e.certificates, e.type, u.mac_address,
            AVG(a.total_score) as avg_score, MAX(a.assessment_date) as last_date
            FROM employees e
            LEFT JOIN assessments a ON e.id = a.employee_id
            LEFT JOIN users u ON e.id = u.employee_id";
            
    if ($deptFilter) {
        // Filter by Department AND Exclude Self
        $sql .= " WHERE e.department = '$deptFilter' AND e.full_name != '$myName' ";
    }
            
    $sql .= " GROUP BY e.id ORDER BY last_date DESC";
            
    $result = $conn->query($sql);
    
    $data = [];
    if ($result) {
        while($row = $result->fetch_assoc()) {
            
            $avg = $row['avg_score'] ? (float)$row['avg_score'] : 0;
            $eid = $row['emp_id'];

            $jsonItem = new stdClass();
            $jsonItem->id = $eid;
            $jsonItem->date = $row['last_date'];
            
            $jsonItem->profile = [
                "name" => $row['full_name'],
                "empId" => $row['nik'],
                "dept" => $row['department'],
                "role" => $row['role_title'],
                "position" => $row['position'],
                "education" => $row['education'],
                "tenure" => $row['tenure'],
                "certificates" => $row['certificates'],
                "mac" => $row['mac_address'],
                "type" => $row['type']
            ];

            $jsonItem->total = ["avg" => $avg];
            
            // Attach Calculated Dimension Scores
            $myScores = [];
            if (isset($agg[$eid])) {
                foreach ($agg[$eid] as $dim => $info) {
                    $myScores[$dim] = [
                        "avg" => $info['count'] > 0 ? ($info['sum'] / $info['count']) : 0
                    ];
                }
            }
            $jsonItem->scores = $myScores;
            
            $data[] = $jsonItem;
        }
    }
    echo json_encode($data);
    exit;
}

// --- POST DATA (SIMPAN / UPDATE) ---
if ($method === 'POST') {
    if (isset($input['action']) && $input['action'] == 'clear_all') {
        $conn->query("TRUNCATE TABLE assessments");
        echo json_encode(["status" => "success", "message" => "Riwayat reset"]);
        exit;
    }

    if (isset($input['action']) && $input['action'] == 'reset_mac') {
        $name = $conn->real_escape_string($input['name']);
        // full_name ada di tabel employees, bukan users
        $sql = "UPDATE users u 
                JOIN employees e ON u.employee_id = e.id 
                SET u.mac_address = NULL 
                WHERE e.full_name = '$name'";
        if ($conn->query($sql)) {
            if ($conn->affected_rows > 0) {
                echo json_encode(["status" => "success", "message" => "Security MAC Reset Berhasil untuk $name"]);
            } else {
                echo json_encode(["status" => "error", "message" => "User '$name' tidak ditemukan atau MAC sudah kosong."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        exit;
    }

    $date = $conn->real_escape_string($input['date']);
    
    // --- VALIDASI AKSES: HANYA MANAGER YANG BISA MENILAI ---
    $myName = $_SESSION['full_name'] ?? '';
    if (empty($myName)) {
        echo json_encode(["status" => "error", "message" => "Sesi login tidak valid."]);
        exit;
    }
    
    $mChk = $conn->query("SELECT department, role_title FROM employees WHERE full_name = '$myName'");
    if ($mChk && $mChk->num_rows > 0) {
        $me = $mChk->fetch_assoc();
        $myDept = strtoupper(trim($me['department']));
        $myRole = strtoupper(trim($me['role_title']));
        
        $targetDept = isset($input['profile']['dept']) ? strtoupper(trim($input['profile']['dept'])) : '';
        
        // Define what constitutes a "manager" role
        $adminRoles = ['MANAGER', 'MANAJER', 'SPV', 'SUPERVISOR', 'DIREKTUR', 'HEAD', 'CHIEF', 'KOORDINATOR'];
        $isManager = false;
        foreach ($adminRoles as $ar) {
            if (stripos($myRole, $ar) !== false) {
                $isManager = true;
                break;
            }
        }
        
        // Check if BOD (Board of Directors) is allowed to bypass? 
        // Based on user request: "hanya managernya masing-masing" -> STRICT SAME DEPARTMENT
        $isBOD = ($myDept === 'BOD');
        
        if (!$isManager || ($myDept !== $targetDept && !$isBOD)) {
            echo json_encode(['status' => 'error', 'message' => "Akses Ditolak: Anda tidak memiliki akses. Hanya Manager dari departemen " . ($input['profile']['dept'] ?? '') . " yang dapat menilai karyawan ini."]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Otorisasi gagal. Data pegawai Anda tidak ditemukan."]);
        exit;
    }
    // -------------------------------------------------------

    $assessmentYear = date('Y', strtotime($date));
    $assessmentMonth = date('n', strtotime($date));
    $day = date('j', strtotime($date));
    $period = ($day <= 15) ? '1' : '2'; // Determine Period

    // 1. Dapatkan Employee ID (Berdasarkan Nama & NIK)
    $name = $conn->real_escape_string($input['profile']['name']);
    $nik = isset($input['profile']['empId']) ? $conn->real_escape_string($input['profile']['empId']) : '';
    
    $checkSql = "SELECT id FROM employees WHERE full_name = '$name'";
    if (!empty($nik)) $checkSql .= " AND nik = '$nik'";
    
    $checkRes = $conn->query($checkSql);
    $employee_id = 0;

    if ($checkRes && $checkRes->num_rows > 0) {
        $empRow = $checkRes->fetch_assoc();
        $employee_id = $empRow['id'];
        
        // Update Profile in case it changed
        $dept = $conn->real_escape_string($input['profile']['dept']);
        $role = $conn->real_escape_string($input['profile']['role']);
        $pos = isset($input['profile']['position']) ? $conn->real_escape_string($input['profile']['position']) : '';
        $type = isset($input['profile']['type']) ? $conn->real_escape_string($input['profile']['type']) : 'outsourcing';
        
        $updEmp = "UPDATE employees SET department='$dept', role_title='$role', position='$pos', type='$type' WHERE id='$employee_id'";
        $conn->query($updEmp);
    } else {
        // Pegawai Baru -> Insert Basic
        $dept = $conn->real_escape_string($input['profile']['dept']);
        $role = $conn->real_escape_string($input['profile']['role']);
        $pos = isset($input['profile']['position']) ? $conn->real_escape_string($input['profile']['position']) : '';
        $type = isset($input['profile']['type']) ? $conn->real_escape_string($input['profile']['type']) : 'outsourcing';
        
        $insEmp = "INSERT INTO employees (full_name, nik, department, role_title, position, type) VALUES ('$name', '$nik', '$dept', '$role', '$pos', '$type')";
        if ($conn->query($insEmp)) {
            $employee_id = $conn->insert_id;
            
            // --- AUTO CREATE USER ACCOUNT ---
            // 1. Username (Credentials): Use NIK
            $credentialUsername = $nik; // NIK is the login username
            
            // 2. Password: Auto Generate Random String
            $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            
            // 3. User Role Logic dynamically deduced from roles
            $adminRoles = ['manager', 'manajer', 'spv', 'supervisor', 'direktur', 'head', 'chief', 'admin', 'hr', 'koordinator'];
            $userRole = 'user';
            foreach ($adminRoles as $ar) {
                if (stripos($role, $ar) !== false) {
                    $userRole = 'admin';
                    break;
                }
            }
            
            // 4. Insert User (Linked to Employee ID)
            $sqlUser = "INSERT INTO users (employee_id, password, role) VALUES ('$employee_id', '$password', '$userRole')";
            if (!$conn->query($sqlUser)) {
                // Handle duplicate user or error
                error_log("Failed to create user: " . $conn->error);
            }

            // Store to return
            $newCredentials = ["username" => $credentialUsername, "password" => $password];



        } else {
            echo json_encode(["status" => "error", "message" => "Gagal buat pegawai baru"]); 
            exit;
        }
    }

    // 2. Update/Insert TARGET (Tabel employee_targets)
    $t3 = $conn->real_escape_string($input['targets']['threeMonth'] ?? '');
    $t6 = $conn->real_escape_string($input['targets']['sixMonth'] ?? '');
    $t1 = $conn->real_escape_string($input['targets']['oneYear'] ?? '');
    
    $sqlTarget = "INSERT INTO employee_targets (employee_id, year, target_3_months, target_6_months, target_1_year)
                  VALUES ('$employee_id', '$assessmentYear', '$t3', '$t6', '$t1')
                  ON DUPLICATE KEY UPDATE 
                  target_3_months='$t3', target_6_months='$t6', target_1_year='$t1'";
    $conn->query($sqlTarget);


    // 3. Insert/Update ASSESSMENT (Tabel assessments) - LOGIKA PERIODE
    // Cek apakah sudah ada data untuk (Employee + Year + Month + Period)
    $checkExist = $conn->query("SELECT id FROM assessments 
                                WHERE employee_id='$employee_id' 
                                AND YEAR(assessment_date)='$assessmentYear' 
                                AND MONTH(assessment_date)='$assessmentMonth' 
                                AND period='$period'");
    
    $existingId = null;
    if ($checkExist && $checkExist->num_rows > 0) {
        $row = $checkExist->fetch_assoc();
        $existingId = $row['id'];
    }

    $extraScore = isset($input['extra_score']) ? intval($input['extra_score']) : 0;
    $score = $input['total']['avg'];
    
    // Simpan nama penilai ke dalam data JSON
    $input['evaluated_by'] = $myName;
    $jsonFull = $conn->real_escape_string(json_encode($input));

    if ($existingId) {
        // UPDATE Existing Assessment (Upsert Logic)
        $sql = "UPDATE assessments SET 
                assessment_date='$date', 
                period='$period',
                extra_score='$extraScore',
                total_score='$score', 
                data_json='$jsonFull' 
                WHERE id='$existingId'";
        $msg = "Nilai diperbarui (Periode $period)";
    } else {
        // INSERT New Assessment
        $sql = "INSERT INTO assessments (
                    assessment_date, period, employee_id, extra_score, total_score, data_json
                ) VALUES (
                    '$date', '$period', '$employee_id', '$extraScore', '$score', '$jsonFull'
                )";
        $msg = "Nilai baru disimpan (Periode $period)";
    }

    // OVERRIDE Message if New Employee
    if (isset($newCredentials)) {
        $msg = "Data karyawan baru ditambahkan, Username=" . $newCredentials['username'] . "/password=" . $newCredentials['password'];
    }

    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "message" => $msg]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}

// --- DELETE ---
if ($method === 'DELETE') {
    $id = $conn->real_escape_string($_GET['id']);
    if ($conn->query("DELETE FROM assessments WHERE id='$id'")) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}
?>