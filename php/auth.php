<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action === 'login') {
    $username = $conn->real_escape_string($input['username']);
    $password = $input['password'];
    $login_as = $input['login_as'] ?? 'auto'; // 'admin', 'user', or 'auto' (default)

    // Updated query: Join users with employees
    $sql = "SELECT u.*, e.full_name, e.nik, e.role_title 
            FROM users u 
            JOIN employees e ON u.employee_id = e.id 
            WHERE e.nik = '$username'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // --- MAC ADDRESS SECURITY (Hardware + NIK Pemilik) ---
        $client_mac = $input['mac_address'] ?? 'unknown';
        
        // Ekstrak NIK yang terikat di perangkat (dari string: HW-MESIN-NIK)
        $parts = explode('-', $client_mac);
        $bound_nik = end($parts); // Ambil bagian terakhir (NIK)
        
        $user_mac = $user['mac_address'] ?? '';
        
        // 1. VALIDASI KEPEMILIKAN PERANGKAT (Anti-Titip)
        // Jika NIK yang terikat di browser TIDAK SAMA dengan NIK yang sedang login
        if ($bound_nik !== $user['nik'] && $user['role'] !== 'admin' && $user['role'] !== 'super admin') {
            echo json_encode([
                "status" => "error", 
                "message" => "❌ Perangkat ini sudah terikat dengan akun lain (\". $bound_nik .\"). Harap gunakan perangkat Anda sendiri atau hubungi Admin untuk reset binding."
            ]);
            exit;
        }

        // 2. CEK DUPLIKASI GLOBAL (Cegah 1 HP fisik didaftarkan ke banyak orang)
        // Kita cek apakah ID unik ini (HW-MESIN-NIK) sudah pernah dipakai akun lain?
        $dupSql = "SELECT e.full_name FROM users u 
                   JOIN employees e ON u.employee_id = e.id 
                   WHERE (FIND_IN_SET('$client_mac', REPLACE(u.mac_address, ' ', '')) > 0)
                   AND u.id != '" . $user['id'] . "'";
        $dupRes = $conn->query($dupSql);
        
        if ($dupRes->num_rows > 0) {
            $other = $dupRes->fetch_assoc();
            echo json_encode([
                "status" => "error", 
                "message" => "❌ Identitas perangkat ini sudah terdaftar untuk akun: " . $other['full_name'] . "."
            ]);
            exit;
        }

        // 3. BINDING / LOGIN
        if (empty($user_mac)) {
            $conn->query("UPDATE users SET mac_address='$client_mac' WHERE id='" . $user['id'] . "'");
        } else {
            // Verifikasi kecocokan ID (Mendukung Multi-Device, misal: HP & Laptop)
            $allowed_macs = array_map('trim', explode(',', $user_mac));
            if (!in_array($client_mac, $allowed_macs)) {
                // Berikan jatah maksimal 2 perangkat untuk user biasa
                if (count($allowed_macs) < 2 || $user['role'] === 'admin' || $user['role'] === 'super admin') {
                    $new_mac_list = implode(',', array_merge($allowed_macs, [$client_mac]));
                    $conn->query("UPDATE users SET mac_address='$new_mac_list' WHERE id='" . $user['id'] . "'");
                } else {
                    echo json_encode([
                        "status" => "error", 
                        "message" => "❌ Batas maksimal perangkat (2 HP/Laptop) tercapai! Gunakan perangkat yang sudah terdaftar atau hubungi Admin."
                    ]);
                    exit;
                }
            }
        }
        // ----------------------------------------------------

        if ($password === $user['password']) {
            $actualRole = $user['role']; // Role asli dari database ('admin' atau 'user')
            
            // --- LOGIKA LOGIN AS ---
            // Jika user memilih 'admin' tapi role-nya bukan admin -> Tolak
            if ($login_as === 'admin' && $actualRole !== 'admin' && $actualRole !== 'super admin') {
                echo json_encode(["status" => "error", "message" => "Akun Anda tidak memiliki hak akses Admin."]);
                exit;
            }
            
            // Tentukan mode sesi yang aktif
            // Jika admin pilih mode 'user' -> set session_role ke 'user' (akses ke tracking_mobile)
            // Jika admin pilih mode 'admin' -> tetap 'admin'
            // Jika user biasa -> tetap 'user'
            if (($actualRole === 'admin' || $actualRole === 'super admin') && $login_as === 'user') {
                $sessionRole = 'user'; // Mode karyawan meski akun adalah admin
            } else {
                $sessionRole = $actualRole;
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $sessionRole;
            $_SESSION['actual_role'] = $actualRole; // Simpan role asli untuk referensi
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role_title'] = $user['role_title'] ?? '';
            
            // Tentukan redirect
            if ($sessionRole === 'super admin') {
                $redirect = 'superadmin.php';
            } elseif ($sessionRole === 'admin') {
                $redirect = 'index.php';
            } else {
                $redirect = 'tracking_mobile.php';
            }
            
            echo json_encode([
                "status" => "success",
                "role" => $sessionRole,
                "redirect" => $redirect
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Password salah"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User tidak ditemukan"]);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(["status" => "success"]);
    exit;
}

if ($action === 'check_session') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            "status" => "logged_in",
            "user" => [
                "id" => $_SESSION['user_id'],
                "role" => $_SESSION['role'],
                "name" => $_SESSION['full_name'],
                "role_title" => $_SESSION['role_title'] ?? ''
            ]
        ]);
    } else {
        echo json_encode(["status" => "not_logged_in"]);
    }
    exit;
}
?>
