<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay San Roque - Login</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Error / success messages */
        .alert {
            padding: 12px 16px; border-radius: 10px; font-size: 13px;
            font-weight: 500; margin-bottom: 18px; display: none;
        }
        .alert.show { display: block; }
        .alert.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        .alert.success { background: #f0fdf4; color: #166534; border: 1px solid #86efac; }

        .login-btn { gap: 8px; transition: opacity 0.2s; }
        .login-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        /* "Create account" link */
        .register-link-row {
            text-align: center; margin-top: 20px;
            font-size: 13px; color: #6c757d;
        }
        .register-link-row a {
            color: #388E3C; font-weight: 700;
            text-decoration: none; cursor: pointer;
        }
        .register-link-row a:hover { text-decoration: underline; }

        /* ── Register slide-over panel ── */
        .reg-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.45); z-index: 8000;
            justify-content: center; align-items: center;
        }
        .reg-overlay.open { display: flex; }

        .reg-panel {
            background: #fff; border-radius: 20px;
            padding: 36px 40px; width: 92%; max-width: 480px;
            max-height: 90vh; overflow-y: auto;
            position: relative;
            animation: regIn 0.28s cubic-bezier(0.34,1.2,0.64,1);
        }
        @keyframes regIn {
            from { transform: translateY(24px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .reg-panel h2 { color: #1B4332; font-size: 1.5rem; margin: 0 0 4px; }
        .reg-panel .reg-sub { color: #64748b; font-size: 13px; margin: 0 0 24px; }

        .reg-close {
            position: absolute; top: 14px; right: 18px;
            background: none; border: none; font-size: 24px;
            cursor: pointer; color: #94a3b8; line-height: 1;
        }
        .reg-close:hover { color: #374151; }

        .reg-field { margin-bottom: 14px; }
        .reg-field label {
            display: block; font-size: 12px; font-weight: 600;
            color: #374151; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.03em;
        }
        .reg-field input {
            width: 100%; padding: 13px 15px;
            border: 1px solid #e2e8f0; border-radius: 10px;
            background: #f8fafc; font-size: 14px; font-family: inherit;
            outline: none; box-sizing: border-box; transition: border-color 0.2s;
        }
        .reg-field input:focus { border-color: #2D6A4F; background: #fff; }

        .reg-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .reg-submit {
            width: 100%; padding: 14px; margin-top: 8px;
            border: none; border-radius: 30px;
            background: linear-gradient(135deg, #43A047 0%, #2D6A4F 100%);
            color: #fff; font-weight: 700; font-size: 15px;
            cursor: pointer; font-family: inherit;
            box-shadow: 0 4px 14px rgba(56,142,60,0.28);
            transition: opacity 0.2s;
        }
        .reg-submit:disabled { opacity: 0.6; cursor: not-allowed; }

        .back-to-login {
            text-align: center; margin-top: 16px;
            font-size: 13px; color: #6c757d;
        }
        .back-to-login a { color: #388E3C; font-weight: 600; cursor: pointer; text-decoration: none; }

        /* Strength bar */
        .strength-bar { height: 4px; border-radius: 99px; background: #e2e8f0; margin-top: 6px; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 99px; width: 0%; transition: width 0.3s, background 0.3s; }

        /* Password toggle eye */
        .pw-wrap { position: relative; }
        .pw-wrap input { padding-right: 42px; }
        .pw-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #94a3b8;
            font-size: 16px; padding: 0; line-height: 1;
        }```html

body {
    
margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    background: #f4f7f5;
}

img {
    max-width: 100%;
    height: auto;
}

/* Main layout */
.container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 60px;
    padding: 40px 20px;
    box-sizing: border-box;
    flex-wrap: wrap;
}

/* Branding */
.branding-section {
    flex: 1;
    min-width: 280px;
    max-width: 500px;
    text-align: center;
}

.main-logo {
    width: 140px;
    max-width: 100%;
    height: auto;
    margin-bottom: 20px;
}

.brand-middle {
    font-size: clamp(2rem, 5vw, 3.5rem);
    margin: 0;
    line-height: 1.1;
}

.brand-top,
.brand-bottom {
    font-size: clamp(0.9rem, 2vw, 1.1rem);
}

/* Login Card */
.login-card {
    flex: 1;
    min-width: 300px;
    max-width: 420px;
    width: 100%;
    background: #fff;
    padding: 35px 30px;
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    box-sizing: border-box;
}

/* Inputs */
.input-field,
.reg-field input {
    width: 100%;
    box-sizing: border-box;
}

/* Buttons */
.login-btn,
.reg-submit {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Tabs */
.tab-container {
    width: 100%;
    display: flex;
    position: relative;
    overflow: hidden;
}

.tab-label {
    flex: 1;
    text-align: center;
    white-space: nowrap;
    font-size: 13px;
    padding: 12px 8px;
}

/* Register modal */
.reg-panel {
    width: 95%;
    max-width: 480px;
    padding: 30px 24px;
    box-sizing: border-box;
}

@media (max-width: 992px) {
    .container {
        flex-direction: column;
        gap: 30px;
        padding: 30px 18px;
    }

    .branding-section {
        max-width: 100%;
    }

    .login-card {
        max-width: 500px;
    }
}

@media (max-width: 768px) {

    .container {
        padding: 20px 14px;
        gap: 24px;
    }

    .main-logo {
        width: 100px;
    }

    .brand-middle {
        font-size: 2rem;
    }

    .login-card {
        padding: 24px 18px;
        border-radius: 18px;
    }

    .tab-label {
        font-size: 11px;
        padding: 10px 4px;
    }

    .tab-icon {
        width: 14px;
        height: 14px;
    }

    .input-field {
        padding: 12px 14px;
        font-size: 14px;
    }

    .form-options {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .login-btn {
        padding: 14px;
        font-size: 14px;
    }

    .reg-row {
        grid-template-columns: 1fr;
    }

    .reg-panel {
        padding: 24px 18px;
        border-radius: 18px;
    }
}

@media (max-width: 480px) {

    .brand-middle {
        font-size: 1.6rem;
    }

    .brand-bottom {
        font-size: 11px;
    }

    .login-card {
        padding: 20px 16px;
    }

    .login-card h2 {
        font-size: 1.4rem;
    }

    .subtitle {
        font-size: 13px;
    }

    .tab-label {
        font-size: 10px;
    }

    .register-link-row,
    .back-to-login,
    .forgot-password {
        font-size: 12px;
    }

    .alert {
        font-size: 12px;
    }
}

@media (min-width: 1400px) {
    .container {
        gap: 100px;
    }

    .login-card {
        max-width: 460px;
    }

    .main-logo {
        width: 180px;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <!-- Branding -->
        <div class="branding-section">
            <img src="logo.png" alt="Logo" class="main-logo">
            <div class="brand-text">
                <span class="brand-top">BARANGAY</span>
                <h1 class="brand-middle">SAN ROQUE</h1>
                <p class="brand-bottom">COMMUNITY CONCERN REPORT SYSTEM</p>
            </div>
        </div>

        <!-- Login Card -->
        <div class="login-card">
            <h2>Welcome!</h2>
            <p class="subtitle">Please log in to your account</p>

            <!-- Admin / Resident Tabs -->
            <div class="tab-wrapper">
                <div class="tab-container">
                    <input type="radio" id="admin" name="tabs" checked>
                    <label for="admin" class="tab-label">
                        <img src="gear.png" class="tab-icon"> ADMINISTRATOR
                    </label>
                    <input type="radio" id="resident" name="tabs">
                    <label for="resident" class="tab-label">
                        <img src="user.png" class="tab-icon"> RESIDENTS
                    </label>
                    <span class="glider"></span>
                </div>
            </div>

            <!-- Alert box -->
            <div class="alert" id="loginAlert"></div>

            <!-- Login Form -->
            <form id="loginForm" autocomplete="off">
                <!-- Admin fields -->
                <div id="adminFields">
                    <input type="text" id="adminUser" placeholder="Admin Username" class="input-field">
                    <input type="password" id="adminPass" placeholder="Password" class="input-field">
                </div>
                <!-- Resident fields -->
                <div id="residentFields" style="display:none;">
                    <input type="email" id="resEmail" placeholder="Email Address" class="input-field">
                    <input type="password" id="resPass" placeholder="Password" class="input-field">
                </div>

                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" id="rememberMe"> Remember Me
                    </label>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <img src="unlock.png" alt="" class="btn-icon"> LOG IN
                </button>
            </form>

            <!-- Register link (resident tab only) -->
            <div class="register-link-row" id="registerLinkRow" style="display:none;">
                Don't have an account? <a onclick="openRegister()">Create one here</a>
            </div>
        </div>
    </div>

    <!-- ── Register Panel Overlay ── -->
    <div class="reg-overlay" id="regOverlay">
        <div class="reg-panel">
            <button class="reg-close" onclick="closeRegister()">×</button>
            <h2>Create Account</h2>
            <p class="reg-sub">Register as a resident to file and track your complaints.</p>

            <div class="alert" id="regAlert"></div>

            <div class="reg-field">
                <label>Full Name *</label>
                <input type="text" id="rName" placeholder="Juan dela Cruz">
            </div>
            <div class="reg-field">
                <label>Home Address *</label>
                <input type="text" id="rAddress" placeholder="Block 1, Lot 2, San Roque, Marikina City">
            </div>
            <div class="reg-field">
                <label>Email Address *</label>
                <input type="email" id="rEmail" placeholder="juan@email.com">
            </div>
            <div class="reg-field">
                <label>Birthday *</label>
                <input type="date" id="rBirthday" max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
            </div>
            <div class="reg-row">
                <div class="reg-field">
                    <label>Password *</label>
                    <div class="pw-wrap">
                        <input type="password" id="rPass" placeholder="Min. 6 characters" oninput="checkStrength(this.value)">
                        <button type="button" class="pw-toggle" onclick="togglePw('rPass',this)">👁</button>
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                </div>
                <div class="reg-field">
                    <label>Confirm Password *</label>
                    <div class="pw-wrap">
                        <input type="password" id="rConfirm" placeholder="Repeat password">
                        <button type="button" class="pw-toggle" onclick="togglePw('rConfirm',this)">👁</button>
                    </div>
                </div>
            </div>

            <button class="reg-submit" id="regBtn" onclick="submitRegister()">Create Account</button>
            <div class="back-to-login">Already have an account? <a onclick="closeRegister()">Log in</a></div>
        </div>
    </div>

    <script>
        const adminRadio    = document.getElementById('admin');
        const residentRadio = document.getElementById('resident');
        const adminFields   = document.getElementById('adminFields');
        const residentFields= document.getElementById('residentFields');
        const registerRow   = document.getElementById('registerLinkRow');
        const loginAlert    = document.getElementById('loginAlert');

        // ── Tab switching ──
        function switchTab() {
            const isAdmin = adminRadio.checked;
            adminFields.style.display    = isAdmin ? 'block' : 'none';
            residentFields.style.display = isAdmin ? 'none'  : 'block';
            registerRow.style.display    = isAdmin ? 'none'  : 'block';
            loginAlert.className = 'alert'; // hide alert on tab switch
        }
        adminRadio.addEventListener('change', switchTab);
        residentRadio.addEventListener('change', switchTab);

        // ── Show alert ──
        function showAlert(el, msg, type) {
            el.textContent = msg;
            el.className = 'alert show ' + type;
        }
        function hideAlert(el) { el.className = 'alert'; }

        // ── Login submit ──
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert(loginAlert);
            const btn = document.getElementById('loginBtn');
            btn.disabled = true; btn.textContent = 'Logging in…';

            const isAdmin = adminRadio.checked;
            const fd = new FormData();
            fd.append('role', isAdmin ? 'admin' : 'resident');

            if (isAdmin) {
                fd.append('username', document.getElementById('adminUser').value.trim());
                fd.append('password', document.getElementById('adminPass').value);
            } else {
                fd.append('email',    document.getElementById('resEmail').value.trim());
                fd.append('password', document.getElementById('resPass').value);
            }

            try {
                const res  = await fetch('auth.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showAlert(loginAlert, data.error || 'Login failed.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<img src="unlock.png" class="btn-icon"> LOG IN';
                }
            } catch {
                showAlert(loginAlert, 'Network error. Please try again.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<img src="unlock.png" class="btn-icon"> LOG IN';
            }
        });

        // ── Register panel ──
        function openRegister()  { document.getElementById('regOverlay').classList.add('open'); }
        function closeRegister() { document.getElementById('regOverlay').classList.remove('open'); hideAlert(document.getElementById('regAlert')); }
        document.getElementById('regOverlay').addEventListener('click', e => {
            if (e.target === document.getElementById('regOverlay')) closeRegister();
        });

        // Password strength
        function checkStrength(val) {
            const fill = document.getElementById('strengthFill');
            let score = 0;
            if (val.length >= 6)  score++;
            if (val.length >= 10) score++;
            if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
            const widths = ['0%','33%','66%','100%'];
            const colors = ['#e2e8f0','#e53e3e','#e67e22','#059669'];
            fill.style.width      = widths[score];
            fill.style.background = colors[score];
        }

        // Toggle password visibility
        function togglePw(id, btn) {
            const inp = document.getElementById(id);
            inp.type  = inp.type === 'password' ? 'text' : 'password';
            btn.textContent = inp.type === 'password' ? '👁' : '🙈';
        }

        async function submitRegister() {
            const alert = document.getElementById('regAlert');
            hideAlert(alert);
            const btn = document.getElementById('regBtn');

            const name    = document.getElementById('rName').value.trim();
            const address = document.getElementById('rAddress').value.trim();
            const email   = document.getElementById('rEmail').value.trim();
            const bday    = document.getElementById('rBirthday').value;
            const pass    = document.getElementById('rPass').value;
            const confirm = document.getElementById('rConfirm').value;

            if (!name||!address||!email||!bday||!pass||!confirm) {
                showAlert(alert, 'Please fill in all fields.', 'error'); return;
            }
            if (pass !== confirm) {
                showAlert(alert, 'Passwords do not match.', 'error'); return;
            }
            if (pass.length < 6) {
                showAlert(alert, 'Password must be at least 6 characters.', 'error'); return;
            }

            btn.disabled = true; btn.textContent = 'Creating account…';

            const fd = new FormData();
            fd.append('full_name',        name);
            fd.append('address',          address);
            fd.append('email',            email);
            fd.append('birthday',         bday);
            fd.append('password',         pass);
            fd.append('confirm_password', confirm);

            try {
                const res  = await fetch('register.php', { method:'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showAlert(alert, '✅ Account created! You can now log in.', 'success');
                    // Pre-fill email in login
                    document.getElementById('resEmail').value = email;
                    setTimeout(() => {
                        closeRegister();
                        residentRadio.checked = true;
                        switchTab();
                    }, 1800);
                } else {
                    showAlert(alert, data.error || 'Registration failed.', 'error');
                }
            } catch {
                showAlert(alert, 'Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false; btn.textContent = 'Create Account';
            }
        }
    </script>
</body>
</html>
