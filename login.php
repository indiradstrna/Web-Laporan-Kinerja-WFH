<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KPI System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            border: 1px solid #e2e8f0;
        }
        .brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-icon {
            width: 56px;
            height: 56px;
            background: #0f172a;
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 4px 14px rgba(15,23,42,0.4);
        }
        .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* ---- ROLE TOGGLE ---- */
        .role-toggle {
            display: flex;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 4px;
            gap: 4px;
            margin-bottom: 1.5rem;
        }
        .role-toggle-btn {
            flex: 1;
            padding: 9px 12px;
            border: none;
            border-radius: 7px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            background: transparent;
            color: #64748b;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .role-toggle-btn.active {
            background: white;
            color: #0f172a;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        .role-toggle-btn.active.admin-btn {
            color: #7c3aed;
        }
        .role-toggle-btn.active.user-btn {
            color: #0284c7;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }
        .form-group input {
            margin-top: 6px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 14px;
            width: 100%;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            outline: none;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        /* Password toggle wrapper */
        .password-wrapper {
            position: relative;
            margin-top: 6px;
        }
        .password-wrapper input {
            margin-top: 0;
            padding-right: 42px; /* space for icon */
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 4px;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }
        .toggle-password:hover { color: #475569; }
        .btn-login {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: all 0.2s ease;
            color: white;
        }
        .btn-login.admin-mode { background: linear-gradient(135deg, #7c3aed, #4f46e5); }
        .btn-login.admin-mode:hover { background: linear-gradient(135deg, #6d28d9, #4338ca); transform: translateY(-1px); }
        .btn-login.user-mode { background: linear-gradient(135deg, #0284c7, #0369a1); }
        .btn-login.user-mode:hover { background: linear-gradient(135deg, #0369a1, #075985); transform: translateY(-1px); }

        .mode-hint {
            margin-top: 1rem;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            line-height: 1.5;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        .mode-hint.admin-hint { background: #f5f3ff; color: #5b21b6; border: 1px solid #ede9fe; }
        .mode-hint.user-hint { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="12" x2="12" y1="8" y2="16"/><line x1="8" x2="16" y1="12" y2="12"/></svg>
            </div>
            <h1 class="brand-title">KPI System</h1>
            <p style="color: #64748b; margin-top: 4px; font-size: 0.9rem;">SEAMEO BIOTROP</p>
        </div>

        <!-- Role Toggle -->
        <div class="role-toggle" id="roleToggle">
            <button type="button" class="role-toggle-btn user-btn active" onclick="setLoginMode('user')" id="btnUser">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Karyawan
            </button>
            <button type="button" class="role-toggle-btn admin-btn" onclick="setLoginMode('admin')" id="btnAdmin">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                Admin
            </button>
        </div>

        <form id="loginForm">
            <input type="hidden" id="login_as" value="user">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>NIK / Username</label>
                <input type="text" id="username" required placeholder="Masukkan NIK atau username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" required placeholder="Masukkan password">
                    <button type="button" class="toggle-password" onclick="togglePassword()" id="togglePwdBtn" title="Tampilkan/Sembunyikan Password">
                        <!-- Eye icon (default: show) -->
                        <svg id="iconEye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <!-- Eye-off icon (hidden by default) -->
                        <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login user-mode" id="btnSubmit">
                Masuk sebagai Karyawan
            </button>

            <div class="mode-hint user-hint" id="modeHint">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="16"/><line x1="8" x2="16" y1="12" y2="12"/></svg>
                <span>Mode <strong>Karyawan</strong>: Diarahkan ke halaman absensi & laporan harian.</span>
            </div>
        </form>
    </div>

    <script>
        // --- TOGGLE PASSWORD VISIBILITY ---
        function togglePassword() {
            const input = document.getElementById('password');
            const iconEye = document.getElementById('iconEye');
            const iconEyeOff = document.getElementById('iconEyeOff');

            if (input.type === 'password') {
                input.type = 'text';
                iconEye.style.display = 'none';
                iconEyeOff.style.display = 'block';
            } else {
                input.type = 'password';
                iconEye.style.display = 'block';
                iconEyeOff.style.display = 'none';
            }
        }

        let currentMode = 'user';

        function setLoginMode(mode) {
            currentMode = mode;
            document.getElementById('login_as').value = mode;

            const btnAdmin = document.getElementById('btnAdmin');
            const btnUser = document.getElementById('btnUser');
            const btnSubmit = document.getElementById('btnSubmit');
            const modeHint = document.getElementById('modeHint');

            if (mode === 'admin') {
                btnAdmin.classList.add('active');
                btnUser.classList.remove('active');
                btnSubmit.className = 'btn-login admin-mode';
                btnSubmit.textContent = 'Masuk sebagai Admin';
                modeHint.className = 'mode-hint admin-hint';
                modeHint.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:1px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                    <span>Mode <strong>Admin</strong>: Diarahkan ke dashboard manajemen & monitoring.</span>
                `;
            } else {
                btnUser.classList.add('active');
                btnAdmin.classList.remove('active');
                btnSubmit.className = 'btn-login user-mode';
                btnSubmit.textContent = 'Masuk sebagai Karyawan';
                modeHint.className = 'mode-hint user-hint';
                modeHint.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="16"/><line x1="8" x2="16" y1="12" y2="12"/></svg>
                    <span>Mode <strong>Karyawan</strong>: Diarahkan ke halaman absensi & laporan harian.</span>
                `;
            }
        }

        // --- STABLE HARDWARE FINGERPRINT ---
        function getHardwareFingerprint() {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            let renderer = 'unknown';
            if (gl) {
                const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
                renderer = debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : 'generic';
            }
            const info = [
                screen.width + 'x' + screen.height,
                navigator.hardwareConcurrency || '8',
                renderer,
                navigator.platform
            ].join('|');
            
            let hash = 0;
            for (let i = 0; i < info.length; i++) {
                hash = ((hash << 5) - hash) + info.charCodeAt(i);
                hash |= 0;
            }
            return 'HW-' + Math.abs(hash).toString(16).toUpperCase();
        }

        function getDeviceId(currentNik) {
            const hw = getHardwareFingerprint();
            
            // Cek apakah HP ini sudah pernah 'terikat' dengan NIK tertentu di browser ini
            let boundNik = localStorage.getItem('device_bound_nik');
            
            if (!boundNik) {
                // Jika belum ada (HP baru/baru daftar), ikat dengan NIK yang sedang login
                // Kita akan simpan permanen setelah login berhasil, 
                // tapi untuk sekarang kita kirim kombinasi ini.
                boundNik = currentNik;
            }
            
            // Return format: HW-[MESIN]-[NIK_PEMILIK_HP]
            return hw + '-' + boundNik;
        }

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const login_as = document.getElementById('login_as').value;
            
            // Generate Device ID (Hardware + NIK yang mengikat HP ini)
            const mac_address = getDeviceId(username);
            
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.textContent = 'Memproses...';

            try {
                const res = await fetch('php/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'login', username, password, mac_address, login_as })
                });
                const data = await res.json();

                if (data.status === 'success') {
                    // JIKA LOGIN BERHASIL, Kunci NIK ini ke browser (Binding)
                    localStorage.setItem('device_bound_nik', data.bound_nik || username);
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                    btn.disabled = false;
                    setLoginMode(currentMode); // Reset button text
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi');
                btn.disabled = false;
                setLoginMode(currentMode);
            }
        });
    </script>
</body>
</html>
