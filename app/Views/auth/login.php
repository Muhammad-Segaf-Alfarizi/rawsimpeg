<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; SIMPEG BGTK</title>
    <script>
    (function () {
        try {
            var saved = localStorage.getItem('simpeg_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
        } catch (e) {}
    })();
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg:#0f1117;
            --card:#1e2130;
            --input-bg:rgba(255,255,255,0.05);
            --border:rgba(255,255,255,0.07);
            --accent:#6366f1;
            --accent-light:#818cf8;
            --accent-dark:#4f52cc;
            --accent-glow:rgba(99,102,241,0.25);
            --text:#f1f5f9;
            --muted:#64748b;
            --secondary:#94a3b8;
            --shadow:0 8px 48px rgba(0,0,0,0.5);
        }
        [data-theme="light"] {
            --bg:#f1f5f9;
            --card:#ffffff;
            --input-bg:#f8fafc;
            --border:#e2e8f0;
            --accent:#4f46e5;
            --accent-light:#4338ca;
            --accent-dark:#3730a3;
            --accent-glow:rgba(79,70,229,0.18);
            --text:#0f172a;
            --muted:#64748b;
            --secondary:#334155;
            --shadow:0 10px 40px rgba(15,23,42,0.08);
        }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Inter',sans-serif; background:var(--bg); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; position:relative; overflow:hidden; transition:background-color 0.2s ease, color 0.2s ease; }
        body::before { content:''; position:fixed; top:-200px; left:-200px; width:600px; height:600px; background:radial-gradient(circle,rgba(99,102,241,0.15) 0%,transparent 70%); pointer-events:none; }
        body::after  { content:''; position:fixed; bottom:-200px; right:-200px; width:600px; height:600px; background:radial-gradient(circle,rgba(139,92,246,0.12) 0%,transparent 70%); pointer-events:none; }
        .theme-switch-top { position:fixed; top:20px; right:20px; z-index:10; display:inline-flex; align-items:center; gap:7px; background:var(--card); border:1px solid var(--border); color:var(--secondary); padding:8px 14px; border-radius:10px; font-size:12.5px; font-weight:600; cursor:pointer; box-shadow:var(--shadow); transition:all 0.2s; }
        .theme-switch-top:hover { border-color:var(--accent); color:var(--accent); }
        .login-container { width:100%; max-width:420px; position:relative; z-index:1; }
        .login-logo { text-align:center; margin-bottom:32px; }
        .logo-icon { display:inline-flex; align-items:center; justify-content:center; width:64px; height:64px; background:linear-gradient(135deg,var(--accent),var(--accent-dark)); border-radius:16px; font-size:28px; color:white; box-shadow:0 0 32px var(--accent-glow); margin-bottom:16px; animation:glow-pulse 3s ease-in-out infinite; }
        .login-title { font-size:26px; font-weight:800; color:var(--text); letter-spacing:-0.5px; }
        .login-subtitle { font-size:14px; color:var(--muted); margin-top:4px; }
        .login-card { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:32px; box-shadow:var(--shadow); }
        .form-group { margin-bottom:18px; }
        .form-label { display:block; font-size:13px; font-weight:600; color:var(--secondary); margin-bottom:6px; }
        .input-wrapper { position:relative; }
        .input-icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:14px; pointer-events:none; }
        .form-input { width:100%; padding:11px 14px 11px 38px; background:var(--input-bg); border:1px solid var(--border); border-radius:10px; color:var(--text); font-size:14px; font-family:'Inter',sans-serif; outline:none; transition:all 0.2s; }
        .form-input::placeholder { color:var(--muted); }
        .form-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-glow); background:var(--card); }
        .toggle-password { position:absolute; right:13px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); cursor:pointer; font-size:14px; transition:color 0.2s; }
        .toggle-password:hover { color:var(--text); }
        .btn-login { width:100%; padding:12px; background:linear-gradient(135deg,var(--accent),var(--accent-dark)); border:none; border-radius:10px; color:white; font-size:15px; font-weight:600; font-family:'Inter',sans-serif; cursor:pointer; transition:all 0.2s; margin-top:4px; display:flex; align-items:center; justify-content:center; gap:8px; }
        .btn-login:hover { background:linear-gradient(135deg,var(--accent-light),var(--accent)); box-shadow:0 4px 20px var(--accent-glow); transform:translateY(-1px); }
        .alert-error { background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); color:#ef4444; border-radius:10px; padding:12px 14px; font-size:13px; margin-bottom:18px; display:flex; align-items:center; gap:8px; }
        .login-footer { text-align:center; margin-top:24px; font-size:12px; color:var(--muted); }
        .section-divider { border:none; border-top:1px solid var(--border); margin:20px 0 18px; }
        .section-label { font-size:12px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:14px; }
        @keyframes glow-pulse { 0%,100%{box-shadow:0 0 32px var(--accent-glow)} 50%{box-shadow:0 0 48px rgba(99,102,241,0.4)} }
    </style>
</head>
<body>
<button type="button" class="theme-switch-top" onclick="toggleLoginTheme()">
    <i class="fas fa-sun" id="loginThemeIcon"></i>
    <span id="loginThemeText">Light Mode</span>
</button>

<div class="login-container">
    <div class="login-logo">
        <div class="logo-icon"><i class="fas fa-users-cog"></i></div>
        <h1 class="login-title">SIMPEG</h1>
        <p class="login-subtitle">Sistem Informasi Manajemen Pegawai &mdash; BGTK</p>
    </div>

    <div class="login-card">
        <p class="section-label">Masuk ke Akun Anda</p>

        <?php if (!empty($error)): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= esc($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('login') ?>" id="login-form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="nip" class="form-label">Nomor Induk Pegawai (NIP)</label>
                <div class="input-wrapper">
                    <i class="fas fa-id-badge input-icon"></i>
                    <input type="text" id="nip" name="nip"
                           class="form-input" placeholder="Masukkan NIP Anda"
                           value="<?= esc($old_nip ?? '') ?>"
                           autocomplete="username" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password"
                           class="form-input" placeholder="Masukkan password"
                           autocomplete="current-password" required>
                    <button type="button" class="toggle-password" id="toggle-pwd" aria-label="Tampilkan password">
                        <i class="fas fa-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="submit-btn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Masuk</span>
            </button>
        </form>

        <hr class="section-divider">
        <p style="font-size:12px;color:var(--muted);text-align:center;">
            Hubungi administrator jika lupa password atau akun terkunci.
        </p>
    </div>

    <div class="login-footer">
        &copy; <?= date('Y') ?> BGTK &mdash; Sistem Informasi Kepegawaian
    </div>
</div>

<script>
function syncLoginTheme() {
    var cur = document.documentElement.getAttribute('data-theme') || 'dark';
    document.getElementById('loginThemeIcon').className = cur === 'light' ? 'fas fa-moon' : 'fas fa-sun';
    document.getElementById('loginThemeText').textContent = cur === 'light' ? 'Dark Mode' : 'Light Mode';
}
function toggleLoginTheme() {
    var cur  = document.documentElement.getAttribute('data-theme') || 'dark';
    var next = cur === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('simpeg_theme', next); } catch (e) {}
    syncLoginTheme();
}
syncLoginTheme();

document.getElementById('toggle-pwd').addEventListener('click', function () {
    var pwd = document.getElementById('password');
    var icon = document.getElementById('eye-icon');
    if (pwd.type === 'password') { pwd.type = 'text'; icon.className = 'fas fa-eye-slash'; }
    else { pwd.type = 'password'; icon.className = 'fas fa-eye'; }
});
document.getElementById('login-form').addEventListener('submit', function () {
    var btn = document.getElementById('submit-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Memproses...</span>';
    btn.disabled = true;
});
</script>
</body>
</html>
