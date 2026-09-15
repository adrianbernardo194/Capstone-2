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

        /* ---- Page transition (login <-> register) ---- */
        body {
            opacity: 0;
            animation: pageFadeIn 0.45s ease forwards;
        }
        @keyframes pageFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        body.page-exit {
            animation: pageFadeOut 0.3s ease forwards;
        }
        @keyframes pageFadeOut {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-10px); }
        }

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
        }

        /* ── Forgot password modal — phase indicator ── */
        .fp-phase-dots { display: flex; justify-content: center; gap: 8px; margin-bottom: 22px; }
        .fp-phase-dots span {
            width: 8px; height: 8px; border-radius: 50%; background: #e2e8f0; transition: background 0.2s, width 0.2s;
        }
        .fp-phase-dots span.active { background: #2D6A4F; width: 22px; border-radius: 5px; }
        .fp-phase-dots span.done { background: #86efac; }

        .fp-step { display: none; }
        .fp-step.active { display: block; }

        .fp-otp-row { display: flex; gap: 8px; justify-content: center; margin: 16px 0 8px; }
        .fp-otp-row input {
            width: 42px; height: 52px; text-align: center; font-size: 20px; font-weight: 700;
            border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc; color: #1B4332;
            font-family: inherit; outline: none; transition: border-color 0.2s, background 0.2s;
        }
        .fp-otp-row input:focus { border-color: #2D6A4F; background: #fff; }

        .fp-timer { text-align: center; font-size: 12px; color: #6c757d; margin-bottom: 18px; }
        .fp-resend-link { color: #388E3C; font-weight: 600; cursor: pointer; text-decoration: none; }
        .fp-resend-link.disabled { color: #cbd5e1; cursor: not-allowed; pointer-events: none; }

        .fp-success-icon {
            width: 60px; height: 60px; border-radius: 50%; background: #f0fdf4;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;
            font-size: 28px;
        }

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
                <p class="brand-bottom">LUPON TAGAPAMAYAPA APPLICATION</p>
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
                    <a href="#" class="forgot-password" onclick="openForgotModal(); return false;">Forgot Password?</a>
                </div>

                <button type="submit" class="login-btn" id="loginBtn">
                    <img src="unlock.png" alt="" class="btn-icon"> LOG IN
                </button>
            </form>

            <!-- Register link (resident tab only) -->
            <div class="register-link-row" id="registerLinkRow" style="display:none;">
                Don't have an account? <a href="register.php">Create one here</a>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         FORGOT PASSWORD MODAL — 3 phases:
         1) enter email + send OTP
         2) enter OTP code
         3) set new password
         ══════════════════════════════════════════ -->
    <div class="reg-overlay" id="fpOverlay" onclick="if(event.target===this)closeForgotModal()">
        <div class="reg-panel">
            <button class="reg-close" onclick="closeForgotModal()">&times;</button>

            <div class="fp-phase-dots">
                <span id="fpDot1" class="active"></span>
                <span id="fpDot2"></span>
                <span id="fpDot3"></span>
            </div>

            <div class="alert" id="fpAlert"></div>

            <!-- ── Step 1: Email ── -->
            <div class="fp-step active" id="fpStep1">
                <h2>Reset your password</h2>
                <p class="reg-sub">Enter your email address and we'll send you a verification code.</p>
                <div class="reg-field">
                    <label>Email Address</label>
                    <input type="email" id="fpEmail" placeholder="juandelacruz@email.com">
                </div>
                <button class="reg-submit" id="fpSendBtn" onclick="sendResetOTP()">Send Verification Code</button>
                <p class="back-to-login"><a onclick="closeForgotModal()">Back to login</a></p>
            </div>

            <!-- ── Step 2: OTP ── -->
            <div class="fp-step" id="fpStep2">
                <h2>Enter verification code</h2>
                <p class="reg-sub">We sent a 6-digit code to <strong id="fpEmailDisplay"></strong></p>
                <div class="fp-otp-row">
                    <input type="text" maxlength="1" inputmode="numeric" id="fpOtp1" oninput="fpOtpMove(this,1)">
                    <input type="text" maxlength="1" inputmode="numeric" id="fpOtp2" oninput="fpOtpMove(this,2)">
                    <input type="text" maxlength="1" inputmode="numeric" id="fpOtp3" oninput="fpOtpMove(this,3)">
                    <input type="text" maxlength="1" inputmode="numeric" id="fpOtp4" oninput="fpOtpMove(this,4)">
                    <input type="text" maxlength="1" inputmode="numeric" id="fpOtp5" oninput="fpOtpMove(this,5)">
                    <input type="text" maxlength="1" inputmode="numeric" id="fpOtp6" oninput="fpOtpMove(this,6)">
                </div>
                <div class="fp-timer" id="fpTimer">Code expires in <span id="fpTimerCount">5:00</span></div>
                <button class="reg-submit" id="fpVerifyBtn" onclick="verifyResetOTP()">Verify Code</button>
                <p class="back-to-login">
                    Didn't get it? <a class="fp-resend-link" id="fpResendLink" onclick="sendResetOTP(true)">Resend code</a>
                </p>
            </div>

            <!-- ── Step 3: New password ── -->
            <div class="fp-step" id="fpStep3">
                <h2>Set a new password</h2>
                <p class="reg-sub">Choose a new password for your account.</p>
                <div class="reg-field">
                    <label>New Password</label>
                    <div class="pw-wrap">
                        <input type="password" id="fpNewPass" placeholder="At least 8 characters">
                        <button type="button" class="pw-toggle" onclick="fpTogglePw('fpNewPass', this)">👁</button>
                    </div>
                </div>
                <div class="reg-field">
                    <label>Confirm New Password</label>
                    <div class="pw-wrap">
                        <input type="password" id="fpConfirmPass" placeholder="Re-enter password">
                        <button type="button" class="pw-toggle" onclick="fpTogglePw('fpConfirmPass', this)">👁</button>
                    </div>
                </div>
                <button class="reg-submit" id="fpResetBtn" onclick="submitNewPassword()">Reset Password</button>
            </div>

            <!-- ── Success state ── -->
            <div class="fp-step" id="fpStepSuccess">
                <div class="fp-success-icon">✅</div>
                <h2 style="text-align:center;">Password reset!</h2>
                <p class="reg-sub" style="text-align:center;">You can now log in with your new password.</p>
                <button class="reg-submit" onclick="closeForgotModal()">Back to login</button>
            </div>
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
        // Registration now handled by register.php (separate page)

        // ══════════════════════════════════════════════════════════════
        // FORGOT PASSWORD MODAL — 3 phases
        // ══════════════════════════════════════════════════════════════
        let fpTimerInterval = null;
        let fpVerifiedEmail = null;

        function fpShowAlert(msg, type) {
            const el = document.getElementById('fpAlert');
            el.textContent = msg;
            el.className = 'alert show ' + type;
        }
        function fpHideAlert() { document.getElementById('fpAlert').className = 'alert'; }

        function openForgotModal() {
            document.getElementById('fpOverlay').classList.add('open');
            fpGoToStep(1);
        }
        function closeForgotModal() {
            document.getElementById('fpOverlay').classList.remove('open');
            clearInterval(fpTimerInterval);
            // Reset everything for next time
            document.getElementById('fpEmail').value = '';
            document.getElementById('fpNewPass').value = '';
            document.getElementById('fpConfirmPass').value = '';
            fpClearOtp();
            fpHideAlert();
            fpVerifiedEmail = null;
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && document.getElementById('fpOverlay').classList.contains('open')) closeForgotModal();
        });

        function fpGoToStep(n) {
            document.querySelectorAll('.fp-step').forEach(s => s.classList.remove('active'));
            const stepId = n === 'success' ? 'fpStepSuccess' : 'fpStep' + n;
            document.getElementById(stepId).classList.add('active');
            [1, 2, 3].forEach(i => {
                const dot = document.getElementById('fpDot' + i);
                dot.classList.remove('active', 'done');
                if (n !== 'success' && i < n) dot.classList.add('done');
                if (n !== 'success' && i === n) dot.classList.add('active');
                if (n === 'success') dot.classList.add('done');
            });
            fpHideAlert();
        }

        // ── OTP input auto-advance (reused pattern from register.php) ──
        function fpOtpMove(input, index) {
            if (input.value && index < 6) {
                document.getElementById('fpOtp' + (index + 1)).focus();
            }
        }
        function fpClearOtp() {
            for (let i = 1; i <= 6; i++) document.getElementById('fpOtp' + i).value = '';
        }
        function fpGetOtp() {
            let code = '';
            for (let i = 1; i <= 6; i++) code += document.getElementById('fpOtp' + i).value;
            return code;
        }

        function fpStartTimer(seconds) {
            clearInterval(fpTimerInterval);
            let remaining = seconds;
            const resendLink = document.getElementById('fpResendLink');
            resendLink.classList.add('disabled');
            fpTimerInterval = setInterval(() => {
                remaining--;
                const m = Math.floor(remaining / 60), s = remaining % 60;
                document.getElementById('fpTimerCount').textContent = `${m}:${s.toString().padStart(2, '0')}`;
                if (remaining <= 0) {
                    clearInterval(fpTimerInterval);
                    document.getElementById('fpTimer').textContent = 'Code expired.';
                    resendLink.classList.remove('disabled');
                }
            }, 1000);
        }

        // ── Step 1: send OTP ──
        async function sendResetOTP(isResend = false) {
            fpHideAlert();
            const email = document.getElementById('fpEmail').value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                fpShowAlert('Please enter a valid email address.', 'error');
                return;
            }

            const btn = document.getElementById('fpSendBtn');
            if (!isResend) { btn.disabled = true; btn.textContent = 'Sending…'; }

            try {
                const fd = new FormData();
                fd.append('email', email);
                fd.append('action', 'send_reset_otp');
                const res  = await fetch('send_otp.php', { method: 'POST', body: fd });
                const data = await res.json();

                // Generic response regardless of whether the email exists — see fpShowAlert below
                if (data.success) {
                    document.getElementById('fpEmailDisplay').textContent = email;
                    fpClearOtp();
                    fpGoToStep(2);
                    fpStartTimer(300);
                    document.getElementById('fpOtp1').focus();
                } else {
                    fpShowAlert(data.error || 'Something went wrong. Please try again.', 'error');
                }
            } catch {
                fpShowAlert('Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Send Verification Code';
            }
        }

        // ── Step 2: verify OTP ──
        async function verifyResetOTP() {
            fpHideAlert();
            const email = document.getElementById('fpEmail').value.trim();
            const code  = fpGetOtp();

            if (code.length !== 6) {
                fpShowAlert('Please enter the full 6-digit code.', 'error');
                return;
            }

            const btn = document.getElementById('fpVerifyBtn');
            btn.disabled = true; btn.textContent = 'Verifying…';

            try {
                const fd = new FormData();
                fd.append('email', email);
                fd.append('otp', code);
                fd.append('action', 'verify_reset_otp');
                const res  = await fetch('send_otp.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    clearInterval(fpTimerInterval);
                    fpVerifiedEmail = email;
                    fpGoToStep(3);
                } else {
                    fpShowAlert(data.error || 'Incorrect code. Please try again.', 'error');
                }
            } catch {
                fpShowAlert('Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Verify Code';
            }
        }

        // ── Step 3: set new password ──
        function fpTogglePw(id, btn) {
            const inp = document.getElementById(id);
            inp.type = inp.type === 'password' ? 'text' : 'password';
            btn.textContent = inp.type === 'password' ? '👁' : '🙈';
        }

        async function submitNewPassword() {
            fpHideAlert();
            const pass    = document.getElementById('fpNewPass').value;
            const confirm = document.getElementById('fpConfirmPass').value;

            if (pass.length < 8) {
                fpShowAlert('Password must be at least 8 characters.', 'error');
                return;
            }
            if (pass !== confirm) {
                fpShowAlert('Passwords do not match.', 'error');
                return;
            }

            const btn = document.getElementById('fpResetBtn');
            btn.disabled = true; btn.textContent = 'Resetting…';

            try {
                const fd = new FormData();
                fd.append('email', fpVerifiedEmail);
                fd.append('password', pass);
                const res  = await fetch('reset_password.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    fpGoToStep('success');
                } else {
                    fpShowAlert(data.error || 'Failed to reset password. Please try again.', 'error');
                }
            } catch {
                fpShowAlert('Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Reset Password';
            }
        }

    </script>

    <!-- ══════════════════════════════════════════
         SYSTEM UPDATE NOTICE MODAL
         ✏️  To update: edit sn-body text, sn-version, sn-date, and noticeKey
         🔕  To disable: set showNotice = false
         ══════════════════════════════════════════ -->
    <div id="sn-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99999;justify-content:center;align-items:center;padding:20px;backdrop-filter:blur(4px);">
        <div style="background:#fff;border-radius:20px;padding:36px 30px 28px;width:100%;max-width:420px;box-shadow:0 24px 60px rgba(0,0,0,0.2);animation:snIn .25s cubic-bezier(.34,1.2,.64,1);position:relative;font-family:'Poppins',sans-serif;">

            <!-- Top accent bar -->
            <div style="position:absolute;top:0;left:0;right:0;height:5px;background:linear-gradient(90deg,#1B4332,#43A047);border-radius:20px 20px 0 0;"></div>

            <!-- Icon + Version badge -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
                <div style="width:48px;height:48px;border-radius:12px;background:#e8f5e9;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2D6A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <div>
                    <div style="font-size:11px;font-weight:700;color:#2D6A4F;letter-spacing:.1em;text-transform:uppercase;">System Notice</div>
                    <span id="sn-version" style="display:inline-block;background:#1B4332;color:#fff;font-size:10px;font-weight:700;padding:2px 10px;border-radius:99px;margin-top:3px;letter-spacing:.05em;">Version 1.0.0</span>
                </div>
            </div>

            <!-- Title -->
            <h2 style="font-size:17px;font-weight:700;color:#1B4332;margin:0 0 10px;">System Update</h2>

            <!-- ✏️ EDIT THIS MESSAGE FOR EACH UPDATE -->
            <p id="sn-body" style="font-size:13px;color:#374151;line-height:1.75;margin:0 0 20px;">
                Welcome to the <strong>Barangay San Roque Lupon Tagapamayapa Application</strong>.
                This system is currently under active development as part of an academic thesis project.
                Some features may be subject to change. For concerns or issues, please contact the development team.
            </p>

            <!-- Date -->
            <div style="font-size:11px;color:#94a3b8;margin-bottom:20px;">
                📅 Last updated: <strong id="sn-date">September 13 2026</strong>
            </div>

            <!-- Divider -->
            <div style="height:1px;background:#e2e8f0;margin-bottom:20px;"></div>

            <!-- Don't show again checkbox -->
            <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#6c757d;cursor:pointer;margin-bottom:18px;">
                <input type="checkbox" id="sn-noshow" style="width:15px;height:15px;accent-color:#2D6A4F;cursor:pointer;">
                Don't show this again
            </label>

            <!-- Confirm button -->
            <button onclick="closeNotice()" style="width:100%;padding:13px;border:none;border-radius:10px;background:linear-gradient(135deg,#43A047,#2D6A4F);color:#fff;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 14px rgba(45,106,79,0.3);transition:opacity .15s;" onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                Got it, proceed to login
            </button>
        </div>
    </div>

    <style>
        @keyframes snIn {
            from { transform: translateY(20px) scale(.96); opacity: 0; }
            to   { transform: translateY(0) scale(1); opacity: 1; }
        }
    </style>

    <script>
        // ── Config: update these on every new release ──
        const showNotice = true;                   // set false to hide entirely
        const noticeKey  = 'sn_dismissed_v1_0_0'; // change this key on each new update
        //                                           so returning users see the notice again

        (function initNotice() {
            if (!showNotice) return;
            if (localStorage.getItem(noticeKey) === '1') return;
            document.getElementById('sn-overlay').style.display = 'flex';
        })();

        function closeNotice() {
            if (document.getElementById('sn-noshow').checked) {
                localStorage.setItem(noticeKey, '1');
            }
            document.getElementById('sn-overlay').style.display = 'none';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeNotice();
        });
    </script>

    <script>
        // ---- Smooth page transition to register.php ----
        (function() {
            function navigateWithTransition(e) {
                if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                const href = this.getAttribute('href');
                e.preventDefault();
                document.body.classList.add('page-exit');
                setTimeout(function() { window.location.href = href; }, 280);
            }
            document.querySelectorAll('a[href="register.php"]').forEach(function(link) {
                link.addEventListener('click', navigateWithTransition);
            });
            // Reset in case the page is restored from bfcache (browser back button)
            window.addEventListener('pageshow', function() {
                document.body.classList.remove('page-exit');
            });
        })();
    </script>
</body>
</html>
