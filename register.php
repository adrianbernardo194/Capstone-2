<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Barangay San Roque</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

        :root {
            --green-dark:   #1B4332;
            --green-mid:    #2D6A4F;
            --green-bright: #43A047;
            --green-btn:    #388E3C;
            --green-light:  #e8f5e9;
            --bg:           #f4faf6;
            --white:        #ffffff;
            --border:       #dee2e6;
            --input-bg:     #F8F9FA;
            --text:         #1a202c;
            --muted:        #6c757d;
            --red:          #dc2626;
            --red-light:    #fee2e2;
        }

        html, body { min-height:100%; }

        body {
            background: var(--bg);
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
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

        /* ── Page wrapper ── */
        .reg-wrapper {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 60px;
            width: 100%;
            flex-wrap: wrap;
        }

        /* ── Branding ── */
        .branding {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
            min-width: 280px;
            max-width: 500px;
        }

.main-logo { width: 140px; max-width: 100%; height: auto; margin-bottom: 20px; }
.brand-top { color: #2D6A4F; font-size: clamp(0.9rem, 2vw, 1.1rem); letter-spacing: 1px; }
.brand-middle { color: #1B4332; font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 800; line-height: 1.1; margin: 0; }
.brand-bottom { color: #6c757d; font-weight: 600; font-size: clamp(0.9rem, 2vw, 1.1rem); margin-top: 5px; }


        /* ── Card ── */
        .reg-card {
            background: var(--white);
            border-radius: 22px;
            box-shadow: 0 20px 60px rgba(27,67,50,.10);
            flex: 1;
            min-width: 300px;
            max-width: 420px;
            width: 100%;
            overflow: hidden;
        }

        /* ── Progress bar ── */
        .progress-bar {
            height: 4px;
            background: #e9ecef;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--green-bright), var(--green-mid));
            border-radius: 4px;
            transition: width .4s cubic-bezier(.4,0,.2,1);
        }

        /* ── Step indicator ── */
        .step-indicators {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px 28px 0;
        }
        .step-dot {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            flex: 1;
        }
        .step-dot-circle {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: #e9ecef;
            color: var(--muted);
            font-size: 12px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            transition: all .3s;
            border: 2px solid transparent;
        }
        .step-dot-circle.active {
            background: var(--green-mid);
            color: #fff;
            border-color: var(--green-mid);
            box-shadow: 0 0 0 4px rgba(45,106,79,.15);
        }
        .step-dot-circle.done {
            background: var(--green-light);
            color: var(--green-mid);
            border-color: var(--green-mid);
        }
        .step-dot-label {
            font-size: 9px; font-weight: 600;
            color: var(--muted); letter-spacing: .04em;
            text-align: center; text-transform: uppercase;
        }
        .step-dot.active .step-dot-label { color: var(--green-mid); }
        .step-connector {
            flex-grow: 1; height: 2px; background: #e9ecef;
            align-self: center; margin-top: -18px;
            border-radius: 2px;
        }

        /* ── Card inner ── */
        .card-body { padding: 24px 32px 32px; }

        .phase { display: none; animation: fadeIn .3s ease both; }
        .phase.active { display: block; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

        .phase-title { font-size: 1.4rem; font-weight: 700; color: var(--green-dark); margin-bottom: 4px; }
        .phase-sub   { font-size: 13px; color: var(--muted); margin-bottom: 22px; line-height: 1.55; }

        /* ── Labels ── */
        .field-label {
            display: block;
            font-size: 11px; font-weight: 700;
            color: #374151; text-transform: uppercase;
            letter-spacing: .06em; margin-bottom: 5px;
        }
        .field-opt { font-weight: 400; color: var(--muted); text-transform: none; letter-spacing: 0; }

        /* ── Inputs ── */
        .input-field {
            width: 100%; padding: 13px 15px;
            border: 1.5px solid var(--border);
            border-radius: 11px; background: var(--input-bg);
            font-size: 14px; font-family: inherit;
            outline: none; transition: border-color .2s, background .2s;
            -webkit-appearance: none; margin-bottom: 14px;
        }
        .input-field:focus { border-color: var(--green-mid); background: #fff; }
        .input-field.error { border-color: var(--red); background: #fff9f9; }

        /* ── Two-col row ── */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        /* ── Phone row with OTP button ── */
        .phone-row { display: flex; gap: 10px; margin-bottom: 14px; }
        .phone-row .input-field { margin-bottom: 0; flex: 1; }
        .send-otp-btn {
            padding: 0 16px; border: none; border-radius: 11px;
            background: var(--green-mid); color: #fff;
            font-family: inherit; font-size: 12px; font-weight: 700;
            cursor: pointer; white-space: nowrap;
            transition: opacity .15s, transform .15s ease; flex-shrink: 0;
        }
        .send-otp-btn:disabled { opacity: .55; cursor: not-allowed; }
        .send-otp-btn:hover:not(:disabled) { transform: scale(1.05); }
        .send-otp-btn:active:not(:disabled) { transform: scale(0.96); }

        /* ── OTP boxes ── */
        .otp-row {
            display: flex; gap: 10px; justify-content: center;
            margin: 20px 0 6px;
        }
        .otp-box {
            width: 48px; height: 56px;
            border: 1.5px solid var(--border); border-radius: 11px;
            background: var(--input-bg);
            text-align: center; font-size: 22px; font-weight: 700;
            color: var(--green-dark); outline: none;
            transition: border-color .2s;
            -webkit-appearance: none;
        }
        .otp-box:focus { border-color: var(--green-mid); background: #fff; }

        .otp-timer { text-align: center; font-size: 12px; color: var(--muted); margin-bottom: 16px; }
        .otp-resend { color: var(--green-btn); font-weight: 700; cursor: pointer; text-decoration: underline; display: inline-block; transition: transform .15s ease; }
        .otp-resend:hover:not(:disabled) { transform: scale(1.06); }
        .otp-resend:disabled { color: var(--muted); text-decoration: none; cursor: not-allowed; }

        /* ── Alert ── */
        .alert {
            padding: 11px 14px; border-radius: 9px;
            font-size: 12px; font-weight: 500; margin-bottom: 14px;
            display: none; line-height: 1.5;
        }
        .alert.show.error   { display:block; background: var(--red-light); color:#991b1b; border:1px solid #fca5a5; }
        .alert.show.success { display:block; background: var(--green-light); color:#166534; border:1px solid #86efac; }

        /* ── ID upload area ── */
        .id-upload-area {
            border: 2px dashed var(--border);
            border-radius: 12px; padding: 24px 16px;
            text-align: center; cursor: pointer;
            transition: border-color .2s, background .2s, transform .15s ease;
            margin-bottom: 14px;
            position: relative;
        }
        .id-upload-area:hover, .id-upload-area.dragover {
            border-color: var(--green-mid); background: var(--green-light);
            transform: scale(1.01);
        }
        .id-upload-area input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width:100%; height:100%;
        }
        .id-upload-icon { font-size: 28px; margin-bottom: 8px; }
        .id-upload-text { font-size: 13px; color: var(--muted); line-height: 1.55; }
        .id-upload-text strong { color: var(--green-mid); }

        .id-preview-wrap { display:none; margin-bottom:14px; }
        .id-preview-img  {
            width:100%; max-height:160px; object-fit:cover;
            border-radius:10px; border:1.5px solid var(--border);
        }
        .id-preview-name {
            font-size:12px; color:var(--muted); margin-top:6px;
            display:flex; align-items:center; gap:6px;
        }
        .id-remove {
            background:none; border:none; color:var(--red);
            cursor:pointer; font-size:12px; font-weight:700;
            padding:0; margin-left:auto; display:inline-block;
            transition: transform .15s ease;
        }
        .id-remove:hover { transform: scale(1.08); }

        /* ID type select */
        .id-type-select {
            width:100%; padding:13px 15px;
            border:1.5px solid var(--border); border-radius:11px;
            background:var(--input-bg); font-size:14px;
            font-family:inherit; outline:none;
            transition:border-color .2s; margin-bottom:14px;
            -webkit-appearance:none; cursor:pointer;
        }
        .id-type-select:focus { border-color:var(--green-mid); background:#fff; }

        /* Skip notice */
        .skip-notice {
            background: #fff7ed; border: 1px solid #fdba74;
            border-radius: 10px; padding: 12px 14px;
            font-size: 12px; color: #92400e; line-height: 1.6;
            margin-bottom: 16px;
        }

        /* ── Password strength ── */
        .strength-bar { height:4px; border-radius:99px; background:#e9ecef; margin-top:-8px; margin-bottom:14px; overflow:hidden; }
        .strength-fill { height:100%; border-radius:99px; width:0; transition:width .3s, background .3s; }

        /* ── Password toggle ── */
        .pw-wrap { position:relative; }
        .pw-wrap .input-field { padding-right:44px; margin-bottom:14px; }
        .pw-eye {
            position:absolute; right:13px; top:50%; transform:translateY(-55%);
            background:none; border:none; cursor:pointer;
            color:#94a3b8; font-size:15px; line-height:1; padding:4px;
            transition: transform .15s ease;
        }
        .pw-eye:hover { transform: translateY(-55%) scale(1.15); }

        /* ── Main button ── */
        .main-btn {
            width:100%; padding:14px; border:none; border-radius:30px;
            background:linear-gradient(135deg, var(--green-bright) 0%, var(--green-mid) 100%);
            color:#fff; font-family:inherit; font-size:15px; font-weight:700;
            cursor:pointer; box-shadow:0 4px 15px rgba(56,142,60,.28);
            transition:opacity .15s, transform .1s; letter-spacing:.02em;
            display:flex; align-items:center; justify-content:center; gap:8px;
            margin-top:4px;
        }
        .main-btn:hover:not(:disabled) { opacity:.92; transform:translateY(-1px) scale(1.02); }
        .main-btn:active:not(:disabled) { transform:translateY(0) scale(0.98); }
        .main-btn:disabled { opacity:.6; cursor:not-allowed; transform:none; }

        /* ── Skip / back links ── */
        .action-links { text-align:center; margin-top:16px; font-size:13px; color:var(--muted); }
        .action-links a { color:var(--green-btn); font-weight:700; cursor:pointer; text-decoration:none; display:inline-block; transition: transform .15s ease; }
        .action-links a:hover { text-decoration:underline; transform: scale(1.06); }

        /* ── Login link ── */
        .login-link { text-align:center; margin-top:18px; font-size:13px; color:var(--muted); }
        .login-link a { color:var(--green-btn); font-weight:700; text-decoration:none; display:inline-block; transition: transform .15s ease; }
        .login-link a:hover { transform: scale(1.06); text-decoration: underline; }

        /* ── Success screen ── */
        .success-screen {
            text-align:center; padding:12px 0 8px;
        }
        .success-icon-wrap {
            width:72px; height:72px; border-radius:50%;
            background:var(--green-light);
            display:flex; align-items:center; justify-content:center;
            margin:0 auto 18px;
        }
        .success-icon-wrap svg { width:36px; height:36px; }
        .success-title { font-size:20px; font-weight:700; color:var(--green-dark); margin-bottom:8px; }
        .success-msg   { font-size:13px; color:var(--muted); line-height:1.7; margin-bottom:24px; }

        /* Responsive */
        @media (max-width: 992px) {
            .branding { max-width: 100%; }
            .reg-card { max-width: 500px; }
        }

        @media (max-width: 768px) {
            .reg-wrapper { flex-direction:column; gap:24px; }
            .branding { flex-direction:row; gap:14px; text-align:left; max-width: 100%; }
            .main-logo { width:100px; margin-bottom:0; }
            .brand-middle { font-size:2rem; }
            .card-body { padding:20px 20px 26px; }
            .two-col { grid-template-columns:1fr; gap:0; }
            .otp-box { width:42px; height:50px; font-size:18px; }
        }

        @media (max-width: 480px) {
            .brand-middle { font-size:1.6rem; }
            .brand-bottom { font-size:11px; }
        }

        @media (min-width: 1400px) {
            .main-logo { width:180px; }
        }
    </style>
</head>
<body>

<div class="reg-wrapper">

    <!-- Branding -->
    <div class="branding">
        <img src="logo.png" alt="Barangay San Roque" class="main-logo">
        <div>
            <div class="brand-top">BARANGAY</div>
            <div class="brand-middle">SAN ROQUE</div>
            <div class="brand-bottom">LUPON TAGAPAMAYAPA APPLICATION</div>
        </div>
    </div>

    <!-- Registration Card -->
    <div class="reg-card">

        <!-- Progress bar -->
        <div class="progress-bar"><div class="progress-fill" id="progressFill" style="width:33%"></div></div>

        <!-- Step indicators -->
        <div class="step-indicators">
            <div class="step-dot active" id="dot1">
                <div class="step-dot-circle active" id="circle1">1</div>
                <div class="step-dot-label">Personal Info</div>
            </div>
            <div class="step-connector"></div>
            <div class="step-dot" id="dot2">
                <div class="step-dot-circle" id="circle2">2</div>
                <div class="step-dot-label">ID Upload</div>
            </div>
            <div class="step-connector"></div>
            <div class="step-dot" id="dot3">
                <div class="step-dot-circle" id="circle3">3</div>
                <div class="step-dot-label">Account</div>
            </div>
        </div>

        <div class="card-body">

            <!-- ══════════════════════════════════
                 PHASE 1 — Personal Info + OTP
                 ══════════════════════════════════ -->
            <div class="phase active" id="phase1">
                <h2 class="phase-title">Personal Information</h2>
                <p class="phase-sub">Enter your details and verify your email address with a one-time code.</p>

                <div class="alert" id="alert1"></div>

                <div class="two-col">
                    <div>
                        <label class="field-label">First Name *</label>
                        <input type="text" id="firstName" class="input-field" placeholder="Adrian">
                    </div>
                    <div>
                        <label class="field-label">Middle Name</label>
                        <input type="text" id="middleName" class="input-field" placeholder="Cruz">
                    </div>
                </div>

                <label class="field-label">Last Name *</label>
                <input type="text" id="lastName" class="input-field" placeholder="Bernardo">

                <label class="field-label">Birthday *</label>
                <input type="date" id="birthday" class="input-field" max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">

                <label class="field-label">Address *</label>
                <input type="text" id="address" class="input-field" placeholder="Block 4 Lot 12, San Roque St.">

                <label class="field-label">Email Address *</label>
                <div class="phone-row">
                    <input type="email" id="email" class="input-field"
                           placeholder="juandelacruz@email.com">
                    <button class="send-otp-btn" id="sendOtpBtn" onclick="sendOTP()">Send OTP</button>
                </div>

                <!-- OTP entry — hidden until OTP is sent -->
                <div id="otpSection" style="display:none;">
                    <label class="field-label" style="text-align:center;display:block;margin-bottom:6px;">
                        Enter 6-digit code sent to <span id="otpEmail" style="color:var(--green-mid);"></span>
                    </label>
                    <div class="otp-row">
                        <input type="text" class="otp-box" maxlength="1" id="otp1" oninput="otpInput(this,'otp2')" onkeydown="otpBack(event,this,'')">
                        <input type="text" class="otp-box" maxlength="1" id="otp2" oninput="otpInput(this,'otp3')" onkeydown="otpBack(event,this,'otp1')">
                        <input type="text" class="otp-box" maxlength="1" id="otp3" oninput="otpInput(this,'otp4')" onkeydown="otpBack(event,this,'otp2')">
                        <input type="text" class="otp-box" maxlength="1" id="otp4" oninput="otpInput(this,'otp5')" onkeydown="otpBack(event,this,'otp3')">
                        <input type="text" class="otp-box" maxlength="1" id="otp5" oninput="otpInput(this,'otp6')" onkeydown="otpBack(event,this,'otp4')">
                        <input type="text" class="otp-box" maxlength="1" id="otp6" oninput="otpInput(this,'')"    onkeydown="otpBack(event,this,'otp5')">
                    </div>
                    <div class="otp-timer" id="otpTimer">Code expires in <span id="timerCount">5:00</span></div>
                    <p style="text-align:center;font-size:12px;color:var(--muted);margin-bottom:16px;">
                        Didn't receive it? <button class="otp-resend" id="resendBtn" onclick="sendOTP(true)" disabled>Resend code</button>
                    </p>
                </div>

                <button class="main-btn" id="btn1" onclick="validatePhase1()">
                    Continue →
                </button>

                <div class="login-link">Already have an account? <a href="login.php">Log in</a></div>
            </div>

            <!-- ══════════════════════════════════
                 PHASE 2 — ID Upload (optional)
                 ══════════════════════════════════ -->
            <div class="phase" id="phase2">
                <h2 class="phase-title">Identity Verification</h2>
                <p class="phase-sub">Upload a photo of your valid government ID. This is <strong>optional</strong> — you can skip and verify later from your dashboard.</p>

                <div class="alert" id="alert2"></div>

                <div class="skip-notice">
                    ⚠ Without a verified ID, you <strong>cannot file complaints online</strong>. You can submit your ID anytime from your dashboard after registering.
                </div>

                <label class="field-label">ID Type <span class="field-opt">(required if uploading)</span></label>
                <select class="id-type-select" id="idType">
                    <option value="">-- Select ID Type --</option>
                    <option>PhilSys (National ID)</option>
                    <option>Voter's ID</option>
                    <option>Driver's License</option>
                    <option>Passport</option>
                    <option>SSS ID</option>
                    <option>GSIS ID</option>
                    <option>PhilHealth ID</option>
                    <option>Barangay ID</option>
                    <option>Senior Citizen ID</option>
                    <option>PWD ID</option>
                </select>

                <label class="field-label">Photo of ID <span class="field-opt">(JPG or PNG, max 5MB)</span></label>
                <div class="id-upload-area" id="uploadArea">
                    <input type="file" id="idPhoto" accept="image/jpeg,image/png,image/jpg"
                           onchange="previewID(this)">
                    <div class="id-upload-icon">🪪</div>
                    <div class="id-upload-text">
                        <strong>Click to upload</strong> or drag and drop<br>
                        Make sure all details are clear and readable
                    </div>
                </div>

                <!-- Preview -->
                <div class="id-preview-wrap" id="idPreviewWrap">
                    <img class="id-preview-img" id="idPreviewImg" alt="ID Preview">
                    <div class="id-preview-name">
                        <span id="idFileName"></span>
                        <button class="id-remove" onclick="removeID()">✕ Remove</button>
                    </div>
                </div>

                <button class="main-btn" id="btn2" onclick="validatePhase2()">
                    Continue →
                </button>

                <div class="action-links">
                    <a onclick="goToPhase(1)">← Back</a>
                    &nbsp;·&nbsp;
                    <a onclick="skipID()">Skip for now</a>
                </div>
            </div>

            <!-- ══════════════════════════════════
                 PHASE 3 — Username & Password
                 ══════════════════════════════════ -->
            <div class="phase" id="phase3">
                <h2 class="phase-title">Create Your Account</h2>
                <p class="phase-sub">Choose a strong password to secure your account. You'll log in using your email address.</p>

                <div class="alert" id="alert3"></div>

                <label class="field-label">Password *</label>
                <div class="pw-wrap">
                    <input type="password" id="password" class="input-field"
                           placeholder="Min. 8 characters" oninput="checkStrength(this.value)">
                    <button type="button" class="pw-eye" onclick="togglePw('password',this)">👁</button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>

                <label class="field-label">Confirm Password *</label>
                <div class="pw-wrap">
                    <input type="password" id="confirmPassword" class="input-field" placeholder="Repeat password">
                    <button type="button" class="pw-eye" onclick="togglePw('confirmPassword',this)">👁</button>
                </div>

                <button class="main-btn" id="btn3" onclick="submitRegistration()">
                    Create Account
                </button>

                <div class="action-links"><a onclick="goToPhase(2)">← Back</a></div>
            </div>

            <!-- ══════════════════════════════════
                 SUCCESS SCREEN
                 ══════════════════════════════════ -->
            <div class="phase" id="phaseSuccess">
                <div class="success-screen">
                    <div class="success-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#2D6A4F" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                    </div>
                    <h2 class="success-title">Account Created!</h2>
                    <p class="success-msg" id="successMsg">
                        Your account has been created successfully.<br>
                        You can now log in to the Barangay San Roque portal.
                    </p>
                    <a href="login.php" style="text-decoration:none;">
                        <button class="main-btn">Go to Login</button>
                    </a>
                </div>
            </div>

        </div><!-- /card-body -->
    </div><!-- /reg-card -->
</div><!-- /reg-wrapper -->

<script>
// ── State ─────────────────────────────────────────────────────────────────────
let currentPhase  = 1;
let otpSent       = false;
let otpVerified   = false;
let timerInterval = null;
let idUploaded    = false;
let idSkipped     = false;

// ── Step indicator ─────────────────────────────────────────────────────────────
function setStepIndicators(active) {
    [1,2,3].forEach(i => {
        const circle = document.getElementById('circle' + i);
        const dot    = document.getElementById('dot' + i);
        circle.classList.remove('active','done');
        dot.classList.remove('active');
        if (i < active)      { circle.classList.add('done'); circle.innerHTML = '✓'; }
        else if (i === active){ circle.classList.add('active'); circle.textContent = i; dot.classList.add('active'); }
        else                  { circle.textContent = i; }
    });
    const pct = { 1:'33%', 2:'66%', 3:'100%' };
    document.getElementById('progressFill').style.width = pct[active] || '100%';
}

function goToPhase(n) {
    document.querySelectorAll('.phase').forEach(p => p.classList.remove('active'));
    document.getElementById('phase' + n).classList.add('active');
    currentPhase = n;
    if (n <= 3) setStepIndicators(n);
}

// ── Alert helpers ──────────────────────────────────────────────────────────────
function showAlert(id, msg, type) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.className = 'alert show ' + type;
}
function hideAlert(id) { document.getElementById(id).className = 'alert'; }

// ── OTP handling ──────────────────────────────────────────────────────────────
function otpInput(current, nextId) {
    current.value = current.value.replace(/\D/g,'');
    if (current.value && nextId) document.getElementById(nextId)?.focus();
}
function otpBack(e, current, prevId) {
    if (e.key === 'Backspace' && !current.value && prevId)
        document.getElementById(prevId)?.focus();
}
function getOTP() {
    return ['otp1','otp2','otp3','otp4','otp5','otp6']
        .map(id => document.getElementById(id).value).join('');
}
function clearOTP() {
    ['otp1','otp2','otp3','otp4','otp5','otp6'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('otp1').focus();
}

// ── OTP timer ──────────────────────────────────────────────────────────────────
function startTimer(seconds) {
    clearInterval(timerInterval);
    const countEl  = document.getElementById('timerCount');
    const resendBtn= document.getElementById('resendBtn');
    resendBtn.disabled = true;
    let remaining  = seconds;

    timerInterval = setInterval(() => {
        const m = Math.floor(remaining / 60);
        const s = remaining % 60;
        countEl.textContent = `${m}:${s.toString().padStart(2,'0')}`;
        remaining--;
        if (remaining < 0) {
            clearInterval(timerInterval);
            countEl.textContent = '0:00';
            resendBtn.disabled = false;
            document.getElementById('otpTimer').innerHTML = '<span style="color:var(--red)">Code expired. Please resend.</span>';
        }
    }, 1000);
}

// ── Send OTP ──────────────────────────────────────────────────────────────────
async function sendOTP(isResend = false) {
    hideAlert('alert1');
    const email = document.getElementById('email').value.trim();

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showAlert('alert1', 'Please enter a valid email address.', 'error');
        return;
    }

    const btn = document.getElementById('sendOtpBtn');
    btn.disabled = true;
    btn.textContent = 'Sending…';

    try {
        const fd = new FormData();
        fd.append('email', email);
        fd.append('action', 'send_otp');
        const res  = await fetch('send_otp.php', { method:'POST', body:fd });
        const data = await res.json();

        if (data.success) {
            otpSent = true;
            otpVerified = false;
            clearOTP();
            document.getElementById('otpSection').style.display = 'block';
            document.getElementById('otpEmail').textContent = email;
            document.getElementById('otpTimer').innerHTML = 'Code expires in <span id="timerCount">5:00</span>';
            startTimer(300);
            btn.textContent = 'Resend';
            if (!isResend) showAlert('alert1', '✅ OTP sent to ' + email + '. Check your inbox.', 'success');
            else           showAlert('alert1', '✅ New OTP sent.', 'success');
            document.getElementById('otp1').focus();
        } else {
            showAlert('alert1', data.error || 'Failed to send OTP. Please try again.', 'error');
            btn.disabled = false;
            btn.textContent = otpSent ? 'Resend' : 'Send OTP';
        }
    } catch {
        showAlert('alert1', 'Network error. Please check your connection.', 'error');
        btn.disabled = false;
        btn.textContent = otpSent ? 'Resend' : 'Send OTP';
    }
}

// ── Phase 1 validation + OTP verify ──────────────────────────────────────────
async function validatePhase1() {
    hideAlert('alert1');

    const firstName = document.getElementById('firstName').value.trim();
    const lastName  = document.getElementById('lastName').value.trim();
    const birthday  = document.getElementById('birthday').value;
    const address   = document.getElementById('address').value.trim();
    const email     = document.getElementById('email').value.trim();

    if (!firstName || !lastName) {
        showAlert('alert1', 'First Name and Last Name are required.', 'error'); return;
    }
    if (!birthday) {
        showAlert('alert1', 'Please enter your birthday.', 'error'); return;
    }
    if (!address) {
        showAlert('alert1', 'Please enter your address.', 'error'); return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showAlert('alert1', 'Please enter a valid email address and send OTP.', 'error'); return;
    }
    if (!otpSent) {
        showAlert('alert1', 'Please send an OTP to your email address first.', 'error'); return;
    }

    const enteredOTP = getOTP();
    if (enteredOTP.length < 6) {
        showAlert('alert1', 'Please enter the 6-digit OTP you received.', 'error'); return;
    }

    // Verify OTP via server
    const btn = document.getElementById('btn1');
    btn.disabled = true; btn.textContent = 'Verifying…';

    try {
        const fd = new FormData();
        fd.append('email', email);
        fd.append('otp',   enteredOTP);
        fd.append('action','verify_otp');
        const res  = await fetch('send_otp.php', { method:'POST', body:fd });
        const data = await res.json();

        if (data.success) {
            clearInterval(timerInterval);
            otpVerified = true;
            goToPhase(2);
        } else {
            showAlert('alert1', data.error || 'Invalid or expired OTP.', 'error');
            btn.disabled = false; btn.textContent = 'Continue →';
        }
    } catch {
        showAlert('alert1', 'Network error. Please try again.', 'error');
        btn.disabled = false; btn.textContent = 'Continue →';
    }
}

// ── ID upload preview ─────────────────────────────────────────────────────────
function previewID(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    if (file.size > 5 * 1024 * 1024) {
        showAlert('alert2', 'File is too large. Maximum size is 5MB.', 'error');
        input.value = ''; return;
    }

    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('idPreviewImg').src = e.target.result;
        document.getElementById('idFileName').textContent = file.name;
        document.getElementById('idPreviewWrap').style.display = 'block';
        document.getElementById('uploadArea').style.display = 'none';
        idUploaded = true;
    };
    reader.readAsDataURL(file);
}

function removeID() {
    document.getElementById('idPhoto').value = '';
    document.getElementById('idPreviewWrap').style.display = 'none';
    document.getElementById('uploadArea').style.display = 'block';
    document.getElementById('idPreviewImg').src = '';
    idUploaded = false;
}

function skipID() {
    idUploaded = false;
    idSkipped  = true;
    document.getElementById('idPhoto').value = '';
    goToPhase(3);
}

// ── Phase 2 validation ────────────────────────────────────────────────────────
function validatePhase2() {
    hideAlert('alert2');

    if (idUploaded) {
        const idType = document.getElementById('idType').value;
        if (!idType) {
            showAlert('alert2', 'Please select your ID type.', 'error'); return;
        }
    }
    idSkipped = !idUploaded;
    goToPhase(3);
}

// ── Password strength ─────────────────────────────────────────────────────────
function checkStrength(val) {
    const fill = document.getElementById('strengthFill');
    let score  = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const colors = ['#e9ecef','#dc2626','#f59e0b','#059669','#2D6A4F'];
    const widths  = ['0%','25%','50%','75%','100%'];
    fill.style.width      = widths[score];
    fill.style.background = colors[score];
}

function togglePw(id, btn) {
    const inp = document.getElementById(id);
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}

// ── Phase 3 — Final submission ────────────────────────────────────────────────
async function submitRegistration() {
    hideAlert('alert3');

    const password = document.getElementById('password').value;
    const confirm  = document.getElementById('confirmPassword').value;

    if (password.length < 8) {
        showAlert('alert3', 'Password must be at least 8 characters.', 'error'); return;
    }
    if (password !== confirm) {
        showAlert('alert3', 'Passwords do not match.', 'error'); return;
    }

    const btn = document.getElementById('btn3');
    btn.disabled = true; btn.textContent = 'Creating account…';

    const fd = new FormData();
    fd.append('first_name',  document.getElementById('firstName').value.trim());
    fd.append('middle_name', document.getElementById('middleName').value.trim());
    fd.append('last_name',   document.getElementById('lastName').value.trim());
    fd.append('birthday',    document.getElementById('birthday').value);
    fd.append('address',     document.getElementById('address').value.trim());
    fd.append('email',       document.getElementById('email').value.trim());
    fd.append('password',    password);
    fd.append('id_type',     document.getElementById('idType').value);
    fd.append('id_skipped',  idSkipped ? '1' : '0');

    const idFile = document.getElementById('idPhoto').files[0];
    if (idFile) fd.append('id_photo', idFile);

    try {
        const res  = await fetch('register_process.php', { method:'POST', body:fd });
        const data = await res.json();

        if (data.success) {
            document.getElementById('progressFill').style.width = '100%';
            document.querySelectorAll('.phase').forEach(p => p.classList.remove('active'));
            const successMsg = document.getElementById('successMsg');
            if (idUploaded) {
                successMsg.innerHTML = 'Your account has been created and your ID has been submitted for review.<br>You will be notified once your account is verified.';
            } else {
                successMsg.innerHTML = 'Your account has been created successfully.<br>Remember to upload your ID from your dashboard to file complaints.';
            }
            document.getElementById('phaseSuccess').classList.add('active');
            [1,2,3].forEach(i => {
                const c = document.getElementById('circle'+i);
                c.classList.add('done'); c.innerHTML='✓';
            });
        } else {
            showAlert('alert3', data.error || 'Registration failed. Please try again.', 'error');
            btn.disabled = false; btn.textContent = 'Create Account';
        }
    } catch {
        showAlert('alert3', 'Network error. Please try again.', 'error');
        btn.disabled = false; btn.textContent = 'Create Account';
    }
}

// ── Drag and drop for ID upload ───────────────────────────────────────────────
const uploadArea = document.getElementById('uploadArea');
uploadArea.addEventListener('dragover',  e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
uploadArea.addEventListener('drop', e => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
        document.getElementById('idPhoto').files = e.dataTransfer.files;
        previewID(document.getElementById('idPhoto'));
    }
});
</script>

<script>
    // ---- Smooth page transition to login.php ----
    (function() {
        function navigateWithTransition(e) {
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            const href = this.getAttribute('href');
            e.preventDefault();
            document.body.classList.add('page-exit');
            setTimeout(function() { window.location.href = href; }, 280);
        }
        document.querySelectorAll('a[href="login.php"]').forEach(function(link) {
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
