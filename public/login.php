<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/escape.php';

session_start();
$error = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        $validAdmin = ($username === 'admin_sembako' && $password === 'admin123');
        $validStaff = ($username === 'staff_gudang'  && $password === 'staff123');

        if ($validAdmin || $validStaff) {
            $role        = ($username === 'admin_sembako') ? 'admin' : 'staff';
            $plainPw     = ($role === 'admin') ? 'admin123' : 'staff123';

            if (!$user) {
                $stmt_insert = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                $stmt_insert->execute([$username, password_hash($plainPw, PASSWORD_DEFAULT), $role]);
                $stmt->execute([$username]);
                $user = $stmt->fetch();
            }

            $_SESSION['user_id']      = $user['id'] ?? 1;
            $_SESSION['username']     = $username;
            $_SESSION['role']         = $user['role'] ?? $role;
            $_SESSION['display_name'] = ($role === 'admin') ? 'Admin' : 'Staff';

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Username atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Kios Sembako Azwa</title>
    <meta name="description" content="Masuk ke Sistem Manajemen Inventori Kios Sembako Azwa">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            background: #f0fdf4;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* Subtle grid pattern */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(22,163,74,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(22,163,74,0.05) 1px, transparent 1px);
            background-size: 44px 44px;
            pointer-events: none;
            z-index: 0;
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: rgba(255,255,255,0.88);
            border: 1px solid rgba(22,163,74,0.15);
            border-radius: 22px;
            padding: 44px 40px;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow:
                0 0 0 1px rgba(22,163,74,0.06),
                0 20px 48px rgba(22,163,74,0.10),
                0 4px 12px rgba(0,0,0,0.06);
        }

        /* Logo */
        .logo-area { text-align: center; margin-bottom: 36px; }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #15803d, #22c55e);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
            margin-bottom: 14px;
            box-shadow: 0 8px 24px rgba(22,163,74,0.28);
        }

        .logo-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: -0.4px;
            line-height: 1.25;
        }

        .logo-title span { color: #16a34a; }

        .logo-subtitle {
            font-size: 0.83rem;
            color: #6b7280;
            margin-top: 5px;
        }

        /* Error */
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-left: 3px solid #ef4444;
            color: #b91c1c;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.875rem;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form */
        .form-group { margin-bottom: 20px; }

        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }

        .input-wrap { position: relative; }

        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.8rem;
            font-weight: 700;
            pointer-events: none;
            color: #9ca3af;
            width: 16px;
            text-align: center;
        }

        .form-input {
            width: 100%;
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-radius: 11px;
            color: #111827;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            padding: 12px 42px 12px 40px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .form-input::placeholder { color: #d1d5db; }

        .form-input:focus {
            border-color: #22c55e;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(34,197,94,0.13);
        }

        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            font-size: 0.95rem;
            padding: 0;
            line-height: 1;
            transition: color 0.15s;
        }
        .pw-toggle:hover { color: #4b5563; }

        /* Submit */
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #15803d, #22c55e);
            border: none;
            border-radius: 11px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.01em;
            box-shadow: 0 4px 16px rgba(22,163,74,0.28);
            transition: box-shadow 0.2s, opacity 0.15s;
            margin-top: 6px;
        }

        .btn-login:hover {
            box-shadow: 0 6px 22px rgba(22,163,74,0.40);
            opacity: 0.95;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">

            <div class="logo-area">
                <div class="logo-icon">KSA</div>
                <div class="logo-title">Kios Sembako<br><span>Azwa</span></div>
                <div class="logo-subtitle">Sistem Manajemen Inventori</div>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert-error">
                <span>⚠</span>
                <span><?= escape($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-wrap">
                        <span class="input-icon" style="font-size:0.75rem;color:#aab;">ID</span>
                        <input type="text" id="username" name="username" class="form-input"
                               placeholder="" required autocomplete="new-password" value="">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" class="form-input"
                               placeholder="" required autocomplete="new-password" value="" style="padding-left:14px;">
                        <button type="button" class="pw-toggle" id="togglePw" aria-label="Tampilkan password">
                            <svg id="iconEye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">Masuk ke Sistem</button>
            </form>

        </div>
    </div>

    <script>
        document.getElementById('togglePw').addEventListener('click', function () {
            const pw       = document.getElementById('password');
            const iconEye    = document.getElementById('iconEye');
            const iconEyeOff = document.getElementById('iconEyeOff');
            const isHidden = pw.type === 'password';
            pw.type = isHidden ? 'text' : 'password';
            iconEye.style.display    = isHidden ? 'none'  : '';
            iconEyeOff.style.display = isHidden ? ''      : 'none';
        });

        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.textContent = 'Memverifikasi...';
            btn.disabled = true;
        });
    </script>
</body>
</html>