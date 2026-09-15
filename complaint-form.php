<?php
require_once 'session_check_resident.php';

// Server-side gate: even if someone reaches this URL directly, don't let
// an unverified resident file a complaint. The dashboard button is also
// disabled for them, but this is the enforcement that actually matters.
if (!$session_id_verified) {
    header('Location: resident-portal.php?verify=required');
    exit;
}

// Avatar initials from the resident's name (e.g. "John Dela Cruz" -> "JD")
$name_parts = preg_split('/\s+/', trim($session_resident_name ?? 'Resident'));
$initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts) - 1] ?? '', 0, 1));
if (count($name_parts) < 2) {
    $initials = strtoupper(substr($name_parts[0], 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File a Complaint - Barangay San Roque</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --green-900: #1B4332;
            --green-700: #2D6A4F;
            --green-500: #40916c;
            --green-300: #74c69d;
            --green-100: #e8f5e9;
            --green-050: #f6fbf7;
            --border: #e0ede5;
            --text: #374151;
            --muted: #52796f;
            --paper: #f9fbf9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: var(--paper); display: flex; min-height: 100vh; }

        /* =============================================
           TOP BAR + SIDEBAR RAIL (desktop: static rail / mobile: hamburger drawer)
           ============================================= */

        .sidebar {
            width: 76px;
            height: 100vh;
            background: #004d2c;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            z-index: 9200;
            transform: translateX(0);
            transition: transform 0.3s ease;
        }

        .sidebar-top,
        .sidebar-bottom {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            width: 100%;
        }

        .nav-item {
            width: 46px;
            height: 46px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: background 0.2s, opacity 0.2s;
            opacity: 0.65;
            border-radius: 12px;
            text-decoration: none;
            margin-bottom: 4px;
        }

        .nav-item.active, .nav-item:hover { opacity: 1; background-color: rgba(255, 255, 255, 0.14); }

        .nav-item img {
            width: 22px;
            height: 22px;
            filter: brightness(0) invert(1);
        }

        .sidebar-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.14);
            border: 1.5px solid rgba(255,255,255,0.35);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .logout-item { margin-bottom: 0; }

        .sidebar-close-btn {
            display: none;
            position: absolute;
            top: 14px;
            right: 14px;
            background: none;
            border: none;
            color: #fff;
            opacity: 0.75;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 8px;
        }
        .sidebar-close-btn:hover { opacity: 1; background: rgba(255,255,255,0.14); }
        .sidebar-close-btn svg { width: 18px; height: 18px; }

        /* Top bar */
        .app-topbar {
            position: fixed;
            top: 0;
            left: 76px;
            width: calc(100% - 76px);
            height: 64px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            z-index: 200;
        }

        .topbar-left { display: flex; align-items: center; gap: 16px; }

        .hamburger-btn {
            display: none;
            background: none;
            border: none;
            color: var(--green-900);
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 8px;
            flex-shrink: 0;
        }
        .hamburger-btn:hover { background: var(--green-100); }
        .hamburger-btn img {
            width: 20px;
            height: 20px;
            filter: invert(17%) sepia(35%) saturate(1352%) hue-rotate(115deg) brightness(94%) contrast(92%);
        }

        .topbar-titles { display: flex; flex-direction: column; line-height: 1.25; }
        .topbar-eyebrow {
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .topbar-title { font-size: 1rem; font-weight: 600; color: var(--green-900); }

        .topbar-right { display: flex; align-items: center; gap: 16px; }

        .topbar-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--green-700);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Content Area */
        .portal-content {
            margin-left: 76px;
            width: 100%;
            padding: 96px 50px 50px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        /* =============================================
           RESIDENT NOTIFICATION PANEL
           ============================================= */

        .res-notif-wrapper { position:relative;display:flex;justify-content:center;align-items:center;padding:15px;cursor:pointer;opacity:0.65;transition:opacity 0.2s; border-radius:12px; }
        .res-notif-wrapper:hover { opacity:1; }
        .res-notif-wrapper img { width:22px;filter:brightness(0) invert(1); }
        .res-notif-badge { position:absolute;top:8px;right:8px;background:#e53e3e;color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:none;align-items:center;justify-content:center;padding:0 3px;border:2px solid #004d2c;line-height:1;pointer-events:none; }
        .res-notif-badge.on { display:flex;animation:resBadgePop 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
        @keyframes resBadgePop{0%{transform:scale(0)}65%{transform:scale(1.2)}100%{transform:scale(1)}}

        .res-notif-overlay { display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.25); }
        .res-notif-overlay.show { display:block; }

        .res-notif-panel { position:fixed;top:0;left:-420px;width:360px;max-width:90vw;height:100vh;background:#fff;border-radius:0 16px 16px 0;box-shadow:6px 0 30px rgba(0,0,0,0.14);z-index:9999;display:flex;flex-direction:column;transition:left 0.32s cubic-bezier(0.4,0,0.2,1);overflow:hidden; }
        .res-notif-panel.open { left:76px; }

        .res-panel-header { background:#1B4332;color:#fff;padding:20px 22px 16px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;gap:12px; }
        .res-panel-header h3 { font-size:15px;font-weight:600;margin:0 0 2px; }
        .res-panel-header small { font-size:11px;opacity:0.75;display:block; }
        .res-panel-user { font-size:12px;opacity:0.85;margin-bottom:4px; }
        .res-mark-read { background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:#fff;font-size:11px;font-weight:600;padding:5px 12px;border-radius:20px;cursor:pointer;white-space:nowrap;transition:background 0.2s;flex-shrink:0; }
        .res-mark-read:hover { background:rgba(255,255,255,0.28); }
.res-panel-actions { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.res-panel-close {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 15px;
            line-height: 1;
            flex-shrink: 0;
            transition: background 0.2s;
        }
        .res-panel-close:hover { background: rgba(255,255,255,0.26); }

        .res-notif-list { overflow-y:auto;flex:1; }
        .res-notif-item { display:flex;gap:13px;padding:15px 18px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;transition:background 0.15s;cursor:pointer; }
        .res-notif-item:hover { background:#f8fafc; }
        .res-notif-item.unread { background:#f0fdf4;border-left:3px solid #2D6A4F; }
        .res-notif-item.unread:hover { background:#e8f5e9; }

        .rn-dot { width:38px;height:38px;border-radius:50%;background:#e8f5e9;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
        .rn-dot img { width:17px;height:17px;filter:invert(29%) sepia(61%) saturate(446%) hue-rotate(105deg); }
        .type-approved  .rn-dot { background:#d1fae5; }
        .unread.type-approved  .rn-dot { background:#059669; }
        .unread.type-approved  .rn-dot img { filter:brightness(0) invert(1); }
        .type-rejected  .rn-dot { background:#fee2e2; }
        .unread.type-rejected  .rn-dot { background:#dc2626; }
        .unread.type-rejected  .rn-dot img { filter:brightness(0) invert(1); }
        .type-cannot_handle .rn-dot { background:#ffedd5; }
        .unread.type-cannot_handle .rn-dot { background:#ea580c; }
        .unread.type-cannot_handle .rn-dot img { filter:brightness(0) invert(1); }
        .type-followup .rn-dot { background:#dbeafe; }
        .unread.type-followup .rn-dot { background:#2563eb; }
        .unread.type-followup .rn-dot img { filter:brightness(0) invert(1); }

        .rn-body { flex:1;min-width:0; }
        .rn-body strong { display:block;font-size:13px;color:#1a202c;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .rn-body p { font-size:12px;color:#64748b;line-height:1.45;margin:0 0 4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
        .rn-body time { font-size:11px;color:#94a3b8; }
        .rn-view-link { font-size:11px;font-weight:600;color:#2D6A4F;margin-top:3px;display:block; }

        .res-empty { text-align:center;padding:50px 20px;color:#94a3b8; }
        .res-empty img { width:42px;opacity:0.28;display:block;margin:0 auto 12px; }
        .res-empty p { font-size:13px; }

        @keyframes resBellPulse{0%{box-shadow:0 0 0 0 rgba(229,62,62,0.5)}70%{box-shadow:0 0 0 7px rgba(229,62,62,0)}100%{box-shadow:0 0 0 0 rgba(229,62,62,0)}}
        .res-bell-pulse { animation:resBellPulse 2s ease-out infinite;border-radius:50%; }

        /* =============================================
           COMPLAINT FORM (KP FORM NO. 9 - SUMBONG)
           ============================================= */

        .top-nav {
            width: 100%;
            max-width: 800px;
            margin-bottom: 18px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2D6A4F;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: gap 0.15s, color 0.15s;
        }

        .back-link:hover { gap: 9px; color: #1B4332; }

        .form-header {
            width: 100%;
            max-width: 800px;
            text-align: center;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1B4332;
        }

        .form-header h2 {
            color: #1B4332;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .form-header p { color: #374151; font-size: 0.85rem; margin-top: 4px; }

        .form-header small {
            display: block;
            margin-top: 8px;
            color: #52796f;
            font-size: 0.72rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .form-container {
            width: 100%;
            max-width: 800px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            padding: 44px 48px;
            margin-bottom: 50px;
        }

        .form-title {
            color: #1B4332;
            font-size: 1.6rem;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.08em;
            margin-bottom: 14px;
        }

        .form-intro {
            color: #52796f;
            font-size: 0.88rem;
            line-height: 1.7;
            text-align: center;
            max-width: 620px;
            margin: 0 auto 32px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 34px 0 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0ede5;
        }

        .section-header:first-of-type { margin-top: 0; }

        .section-icon {
            width: 34px;
            height: 34px;
            padding: 7px;
            background: #e8f5e9;
            border-radius: 50%;
            filter: contrast(0.5);
            flex-shrink: 0;
        }

        .section-header h3 { color: #1B4332; font-size: 1rem; font-weight: 600; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            width: 100%;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 18px;
            min-width: 0;
        }

        .form-group label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #1B4332;
            margin-bottom: 7px;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="tel"],
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #d7e4dc;
            border-radius: 10px;
            font-size: 0.92rem;
            font-family: 'Poppins', sans-serif;
            color: #374151;
            background: #fdfdfd;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2D6A4F;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.12);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder { color: #a3b3ac; }

        .form-group textarea { min-height: 110px; resize: vertical; line-height: 1.6; }

        .form-footer {
            margin-top: 36px;
            padding-top: 22px;
            border-top: 1px dashed #d7e4dc;
            text-align: right;
        }

        .form-footer p { color: #52796f; font-size: 0.82rem; line-height: 1.6; }

        .form-footer strong {
            display: block;
            margin-top: 22px;
            color: #1B4332;
            font-size: 0.95rem;
            letter-spacing: 0.03em;
        }

        .form-footer span { display: block; color: #52796f; font-size: 0.78rem; margin-top: 2px; }

        .form-actions { display: flex; gap: 14px; margin-top: 32px; }

        .submit-btn {
            flex: 2;
            background: #1B4332;
            color: #fff;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }

        .submit-btn:hover { background: #2D6A4F; transform: translateY(-1px); }
        .submit-btn:disabled { background: #b5b5b5; cursor: not-allowed; transform: none; }

        .cancel-btn {
            flex: 1;
            background: transparent;
            color: #2D6A4F;
            border: 2px solid #2D6A4F;
            padding: 13px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .cancel-btn:hover { background: #f0f7f0; }

        /* Success modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }

        .modal-content {
            width: 100%;
            max-width: 420px;
            max-height: 90vh;
            overflow-y: auto;
            background: #fff;
            border-radius: 16px;
            padding: 40px 32px 32px;
            text-align: center;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .modal-content .success-icon {
            width: 64px;
            height: 64px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
        }

        .success-icon img {
            width: 40px;
            filter: invert(36%) sepia(85%) saturate(452%) hue-rotate(105deg) brightness(91%) contrast(87%);
        }

        .modal-content h2 { color: #1B4332; font-size: 1.25rem; margin-bottom: 10px; }
        .modal-content p { color: #52796f; font-size: 0.88rem; line-height: 1.6; }

        .modal-choices { margin-top: 25px; display: flex; flex-direction: column; gap: 12px; }

        .view-btn {
            background: #2D6A4F; color: white; border: none; padding: 12px;
            border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer;
            transition: background 0.2s;
        }
        .view-btn:hover { background: #1B4332; }

        .return-btn {
            background: transparent; color: #2D6A4F; border: 2px solid #2D6A4F; padding: 12px;
            border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer;
            transition: all 0.2s;
        }
        .return-btn:hover { background: #f0f7f0; }

        /* Upload box */
        .upload-box{background:#fdfdfd;border:2px dashed #c6d9ce;padding:28px 20px;border-radius:10px;text-align:center;transition:border-color 0.2s,background 0.2s;cursor:pointer;}
        .upload-box:hover,.upload-box.dragover{border-color:#2D6A4F;background:#f0faf5;}
        .upload-box label{font-size:.9rem;font-weight:600;color:#2D6A4F;cursor:pointer;display:block;margin-bottom:6px;}
        .upload-box input[type="file"]{display:none;}
        .upload-icon{font-size:2rem;margin-bottom:8px;display:block;}
        .file-hint{font-size:.75rem;color:#999;margin-top:6px;}
        .size-bar-wrap{margin-top:14px;display:none;}.size-bar-wrap.visible{display:block;}
        .size-bar-track{background:#e8f0eb;border-radius:99px;height:7px;overflow:hidden;margin-bottom:5px;}
        .size-bar-fill{height:100%;background:#2D6A4F;border-radius:99px;transition:width .3s ease,background .3s;width:0%;}
        .size-bar-fill.warning{background:#e67e22;}.size-bar-fill.over{background:#e53e3e;}
        .size-label{font-size:11px;color:#64748b;text-align:right;}
        .file-pill-list{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;}
        .file-pill{display:flex;align-items:center;gap:6px;background:#f0faf5;border:1px solid #c6d9ce;border-radius:99px;padding:5px 12px 5px 10px;font-size:12px;color:#2D6A4F;font-weight:500;max-width:220px;}
        .file-pill span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;}
        .file-pill .remove-file{cursor:pointer;color:#94a3b8;font-size:15px;line-height:1;flex-shrink:0;transition:color .15s;}
        .file-pill .remove-file:hover{color:#e53e3e;}
        .upload-error{color:#e53e3e;font-size:12px;margin-top:8px;display:none;}.upload-error.show{display:block;}

        /* ===================================
           RESPONSIVE
           =================================== */

        @media screen and (max-width: 992px) {
            .sidebar { transform: translateX(-100%); box-shadow: 6px 0 24px rgba(0,0,0,0.18); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-close-btn { display: flex; }
            .hamburger-btn { display: flex; }

            .app-topbar { left: 0; width: 100%; padding: 0 16px; }

            .portal-content { margin-left: 0; width: 100%; padding: 84px 20px 40px; }

            .res-notif-panel.open { left: 0; }

            .form-container { padding: 36px; }
        }

        @media screen and (max-width: 768px) {
            .portal-content { padding: 78px 16px 32px; }

            .topbar-eyebrow { display: none; }
            .topbar-title { font-size: 0.95rem; }

            .res-notif-panel { width: 100%; max-width: 100vw; }

            .form-row { grid-template-columns: 1fr; gap: 0; }

            .form-container { padding: 26px 20px; border-radius: 14px; }

            .form-header h2 { font-size: 1rem; }
            .form-header p { font-size: 0.8rem; }

            .form-title { font-size: 1.35rem; }
            .form-intro { font-size: 0.84rem; }

            .section-header { margin: 28px 0 16px; }
            .section-header h3 { font-size: 0.92rem; }

            .form-footer { text-align: left; }
            .form-footer strong { margin-top: 16px; }

            .form-actions { flex-direction: column-reverse; }
            .submit-btn, .cancel-btn { flex: none; width: 100%; }
        }

        @media screen and (max-width: 480px) {
            .portal-content { padding: 74px 12px 28px; }
            .app-topbar { height: 58px; padding: 0 12px; }

            .form-container { padding: 20px 16px; }

            .form-title { font-size: 1.2rem; letter-spacing: 0.04em; }

            .form-group input[type="text"],
            .form-group input[type="date"],
            .form-group input[type="tel"],
            .form-group textarea {
                padding: 11px 12px;
                font-size: 0.9rem;
            }

            .section-icon { width: 28px; height: 28px; padding: 6px; }
            .section-header h3 { font-size: 0.86rem; }

            .modal-content { padding: 30px 22px 24px; }
        }
    </style>
</head>
<body>

<!-- Top bar -->
<header class="app-topbar">
    <div class="topbar-left">
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
            <img src="menu.png" alt="Menu">
        </button>
        <div class="topbar-titles">
            <span class="topbar-eyebrow">Resident Portal</span>
            <span class="topbar-title">File a Complaint</span>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-avatar" title="<?php echo htmlspecialchars($session_resident_name ?? 'Resident'); ?>">
            <?php echo htmlspecialchars($initials); ?>
        </div>
    </div>
</header>

<!-- Sidebar rail (desktop: static rail / mobile: slide-in drawer) -->
<nav class="sidebar" id="sidebarNav">
    <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close menu">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </button>
    <div class="sidebar-top">
        <a href="resident-portal.php" class="nav-item" title="Dashboard">
            <img src="dashboard.png" alt="Dashboard">
        </a>
        <a href="complaint-form.php" class="nav-item active" title="File Complaint">
            <img src="file.png" alt="File Complaint">
        </a>
        <div class="res-notif-wrapper nav-item" id="resBellBtn" title="Notifications">
            <img src="bell.png" alt="Notifications" id="resBellIcon">
            <span class="res-notif-badge" id="resBadge"></span>
        </div>
    </div>
    <div class="sidebar-bottom">
        <div class="sidebar-avatar" title="<?php echo htmlspecialchars($session_resident_name ?? 'Resident'); ?>">
            <?php echo htmlspecialchars($initials); ?>
        </div>
<a href="#" onclick="openLogoutModal(); return false;" class="nav-item logout-item" title="Logout">
                <img src="logout.png">
            </a>
    </div>
</nav>

<!-- Backdrop shared by the mobile drawer and the notification panel -->
<div class="res-notif-overlay" id="resOverlay"></div>

<div class="res-notif-panel" id="resPanel">
    <div class="res-panel-header">
        <div>
            <p class="res-panel-user">👤 <?php echo htmlspecialchars($session_resident_name ?? 'Resident'); ?></p>
            <h3>My Notifications</h3>
            <small id="resUnreadLabel">Loading…</small>
        </div>
	<div class = "res-panel-actions">
        <button class="res-mark-read" id="resMarkRead">Mark all read</button>
	<button class="res-panel-close" id="resPanelClose" aria-label="Close notifications">✕</button>
        </div>
    </div>
    <div class="res-notif-list" id="resNotifList">
        <div class="res-empty"><img src="bell.png"><p>No notifications yet.</p></div>
    </div>
</div>

<main class="portal-content">
    <div class="top-nav"><a href="resident-portal.php" class="back-link">← Back to Dashboard</a></div>
    <header class="form-header">
        <h2>OFFICE OF THE SANGGUNIANG BARANGAY</h2>
        <p>Barangay San Roque, Marikina City</p>
        <p>Republic of the Philippines - National Capital Region</p>
        <small>KP Form No. 9 - APPENDIX "H"</small>
    </header>
    <section class="form-container">
        <h1 class="form-title">SUMBONG</h1>
        <p class="form-intro">Ako/Kami sa pamamagitan nito ay naghahain ng sumbong laban sa mga ipinagsusumbong na binanggit sa itaas dahil sa paglabag sa aking/aming karapatan sa sumusunod na paraan:</p>
        <form action="submit_complaint.php" method="POST" enctype="multipart/form-data" id="complaintForm">
            <div class="form-row">
                <div class="form-group"><label>Usapin Blg. / Subject *</label><input type="text" name="subject" placeholder="e.g., Noise Complaint, Property Dispute" required></div>
                <div class="form-group"><label>Date</label><input type="date" name="complaint_date" value="<?php echo date('Y-m-d'); ?>"></div>
            </div>
            <div class="section-header"><img src="people.png" class="section-icon"><h3>Mga May Sumbong (Complainant Information)</h3></div>
            <div class="form-group"><label>Full Name(s) *</label><input type="text" name="complainant_name" value="<?php echo htmlspecialchars($session_resident_name); ?>" required></div>
            <div class="form-row">
                <div class="form-group"><label>Address *</label><input type="text" name="complainant_address" placeholder="Complete address" required></div>
                <div class="form-group"><label>Contact Number *</label><input type="text" name="complainant_contact" placeholder="+63 XXX XXX XXXX" required></div>
            </div>
            <div class="section-header"><img src="people.png" class="section-icon"><h3>Mga Ipinagsusumbong (Respondent Information)</h3></div>
            <div class="form-group"><label>Full Name(s) *</label><input type="text" name="respondent_name" placeholder="Enter respondent's full name" required></div>
            <div class="form-group"><label>Address *</label><input type="text" name="respondent_address" placeholder="Complete address" required></div>
            <div class="section-header"><img src="file.png" class="section-icon"><h3>Complaint Details</h3></div>
            <div class="form-group"><label>Narrative / Statement *</label><textarea name="narrative" placeholder="DAHIL DITO Ako/Kami ay namamantik na ipagkaloob..." required></textarea></div>
            <div class="form-group"><label>Desired Resolution</label><textarea name="resolution" placeholder="What outcome are you seeking?"></textarea></div>
            <div class="section-header"><img src="upload.png" class="section-icon"><h3>Valid ID and other support documents</h3></div>
            <div class="upload-box" id="uploadBox">
                <span class="upload-icon">📎</span>
                <label for="file-upload">Click to add files or drag & drop here</label>
                <input type="file" name="evidence[]" id="file-upload" multiple>
                <p class="file-hint">PDF, JPG, PNG, DOC and more — up to 25 MB total</p>
            </div>
            <div class="size-bar-wrap" id="sizeBarWrap"><div class="size-bar-track"><div class="size-bar-fill" id="sizeBarFill"></div></div><div class="size-label" id="sizeLabel">0 MB / 25 MB</div></div>
            <p class="upload-error" id="uploadError">⚠ Total file size exceeds 25 MB. Please remove some files.</p>
            <div class="file-pill-list" id="filePillList"></div>
            <div class="form-footer">
                <p>Tinanggap at itinala ngayong ika - <?php echo date('d'); ?> araw ng <?php echo date('F, Y'); ?>.</p>
                <p>(Mga) May Sumbong / Complainant(s)</p>
                <strong>TADEO ALLAN M. ARAMIL</strong><span>Punong Barangay</span>
            </div>
            <div class="form-actions">
                <button type="submit" class="submit-btn" id="submitBtn">Submit Complaint</button>
                <button type="button" class="cancel-btn" onclick="window.history.back()">Cancel</button>
            </div>
        </form>
    </section>
</main>

<div id="successModal" class="modal-overlay" style="display:none !important;">
    <div class="modal-content">
        <div class="success-icon"><img src="secure.png" alt="Success"></div>
        <h2>Submission Successful!</h2>
        <p>Your complaint has been successfully passed and is now waiting for admin case review.</p>
        <div class="modal-choices">
            <button class="view-btn" onclick="goToViewComplaint()">View Submitted Complaint</button>
            <button class="return-btn" onclick="goToDashboard()">Back to Dashboard</button>
        </div>
    </div>
</div>

<script>
// Success modal + upload widget (unchanged logic)
(function(){const p=new URLSearchParams(window.location.search);if(p.has('success'))document.getElementById('successModal').style.setProperty('display','flex','important');})();
function goToDashboard(){window.location.href='resident-portal.php';}
function goToViewComplaint(){window.location.href='view-complaint.php?id='+new URLSearchParams(window.location.search).get('id');}
const MAX=25*1024*1024,inp=document.getElementById('file-upload'),plist=document.getElementById('filePillList'),sw=document.getElementById('sizeBarWrap'),sb=document.getElementById('sizeBarFill'),sl=document.getElementById('sizeLabel'),ue=document.getElementById('uploadError'),sbtn=document.getElementById('submitBtn'),ub=document.getElementById('uploadBox');
let fm=new Map(),ni=0;
function fs(b){if(b<1024)return b+' B';if(b<1048576)return(b/1024).toFixed(1)+' KB';return(b/1048576).toFixed(2)+' MB';}
function ts(){let t=0;fm.forEach(f=>t+=f.size);return t;}
function ru(){const t=ts(),p=Math.min((t/MAX)*100,100),o=t>MAX;sw.classList.toggle('visible',fm.size>0);sb.style.width=p+'%';sb.className='size-bar-fill'+(o?' over':p>80?' warning':'');sl.textContent=fs(t)+' / 25 MB';ue.classList.toggle('show',o);sbtn.disabled=o;sbtn.style.opacity=o?'0.5':'1';}
function ri(){const dt=new DataTransfer();fm.forEach(f=>dt.items.add(f));inp.files=dt.files;}
function af(nf){Array.from(nf).forEach(file=>{let d=false;fm.forEach(f=>{if(f.name===file.name&&f.size===file.size)d=true;});if(d)return;const id=ni++;fm.set(id,file);const pill=document.createElement('div');pill.className='file-pill';pill.innerHTML=`<span title="${file.name}">📄 ${file.name}</span><span class="remove-file">×</span>`;pill.querySelector('.remove-file').addEventListener('click',()=>{fm.delete(id);pill.remove();ri();ru();});plist.appendChild(pill);});ri();ru();}
ub.addEventListener('click',e=>{if(e.target!==inp)inp.click();});
inp.addEventListener('change',()=>{af(inp.files);inp.value='';});
ub.addEventListener('dragover',e=>{e.preventDefault();ub.classList.add('dragover');});
ub.addEventListener('dragleave',()=>ub.classList.remove('dragover'));
ub.addEventListener('drop',e=>{e.preventDefault();ub.classList.remove('dragover');af(e.dataTransfer.files);});
document.getElementById('complaintForm').addEventListener('submit',e=>{if(ts()>MAX){e.preventDefault();ue.classList.add('show');}});

// Sidebar drawer + notifications
(function() {
    const bellBtn   = document.getElementById('resBellBtn');
    const panel     = document.getElementById('resPanel');
    const overlay   = document.getElementById('resOverlay');
    const badge     = document.getElementById('resBadge');
    const list      = document.getElementById('resNotifList');
    const label     = document.getElementById('resUnreadLabel');
    const markBtn   = document.getElementById('resMarkRead');
    const panelClose = document.getElementById('resPanelClose');
    const sidebar   = document.getElementById('sidebarNav');
    const hamburger = document.getElementById('hamburgerBtn');
    const closeBtn  = document.getElementById('sidebarCloseBtn');
    let panelOpen = false;
    let drawerOpen = false;

    function timeAgo(d) {
        const s = Math.floor((new Date() - new Date(d)) / 1000);
        if (s < 60)    return 'just now';
        if (s < 3600)  return Math.floor(s/60)   + 'm ago';
        if (s < 86400) return Math.floor(s/3600)  + 'h ago';
        return Math.floor(s/86400) + 'd ago';
    }
    function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function typeLabel(t) {
        switch(t) {
            case 'approved':      return '✅ Case Approved';
            case 'rejected':      return '❌ Case Rejected';
            case 'cannot_handle': return '⚠️ Cannot Be Handled';
            case 'followup':      return '🔄 Follow-Up Required';
            default:              return '🔔 Status Update';
        }
    }

    function render(data) {
        const { notifications, unread_count } = data;
        if (unread_count > 0) {
            badge.textContent = unread_count > 99 ? '99+' : unread_count;
            badge.classList.add('on');
            document.getElementById('resBellIcon').classList.add('res-bell-pulse');
        } else {
            badge.classList.remove('on');
            document.getElementById('resBellIcon').classList.remove('res-bell-pulse');
        }
        label.textContent = unread_count > 0
            ? `${unread_count} new update${unread_count>1?'s':''}`
            : "You're all caught up!";

        if (!notifications.length) {
            list.innerHTML = `<div class="res-empty"><img src="bell.png"><p>No notifications yet.</p></div>`;
            return;
        }
        list.innerHTML = notifications.map(n => `
            <a class="res-notif-item ${n.is_read==0?'unread':''} type-${esc(n.type)}"
               href="view-complaint.php?id=${n.complaint_id}">
                <div class="rn-dot"><img src="bell.png" alt=""></div>
                <div class="rn-body">
                    <strong>${typeLabel(n.type)}</strong>
                    <p>${esc(n.message)}</p>
                    <time>${timeAgo(n.created_at)}</time>
                    <span class="rn-view-link">View complaint →</span>
                </div>
            </a>`).join('');
    }

    function fetch_notifs() {
        fetch('get_resident_notifications.php?action=fetch')
            .then(r=>r.json()).then(render).catch(()=>{});
    }

    function closeAll() {
        panel.classList.remove('open');
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        panelOpen = false;
        drawerOpen = false;
    }
    function openPanel()  { closeAll(); panel.classList.add('open'); overlay.classList.add('show'); panelOpen = true; }
    function openDrawer() { closeAll(); sidebar.classList.add('open'); overlay.classList.add('show'); drawerOpen = true; }

bellBtn.addEventListener('click', () => panelOpen ? closeAll() : openPanel());
    if (hamburger) hamburger.addEventListener('click', () => drawerOpen ? closeAll() : openDrawer());
    if (closeBtn) closeBtn.addEventListener('click', closeAll);
    if (panelClose) panelClose.addEventListener('click', closeAll);
    overlay.addEventListener('click', closeAll);
    markBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        fetch('get_resident_notifications.php?action=mark_read')
            .then(r=>r.json()).then(()=>fetch_notifs());
    });

    fetch_notifs();
    setInterval(fetch_notifs, 15000);
})();

</script>

<!-- ── Logout confirmation modal ── -->
<div id="logoutOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99999;justify-content:center;align-items:center;padding:20px;backdrop-filter:blur(3px);" onclick="if(event.target===this)closeLogoutModal()">
    <div style="background:#fff;border-radius:18px;padding:36px 32px 28px;width:100%;max-width:380px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,0.18);animation:lgIn .22s cubic-bezier(.34,1.2,.64,1);position:relative;">
        <button onclick="closeLogoutModal()" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">×</button>
        <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
            <img src="logout.png" style="width:28px;height:28px;filter:invert(29%) sepia(91%) saturate(1500%) hue-rotate(330deg) brightness(90%);">
        </div>
        <h2 style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:700;color:#1a202c;margin:0 0 8px;">Log out of your account?</h2>
        <p style="font-family:'Poppins',sans-serif;font-size:13px;color:#64748b;margin:0 0 28px;line-height:1.6;">You will be returned to the login page.<br>Any unsaved changes will be lost.</p>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <a href="logout.php" style="display:block;background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;padding:13px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;text-decoration:none;box-shadow:0 4px 14px rgba(220,38,38,0.28);">Yes, log me out</a>
            <button onclick="closeLogoutModal()" style="background:#f8fafc;color:#374151;border:1.5px solid #e2e8f0;padding:13px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">Cancel, stay logged in</button>
        </div>
    </div>
</div>
<style>
@keyframes lgIn { from{transform:translateY(16px) scale(.97);opacity:0} to{transform:translateY(0) scale(1);opacity:1} }
</style>
<script>
function openLogoutModal()  { const o=document.getElementById('logoutOverlay'); o.style.display='flex'; }
function closeLogoutModal() { document.getElementById('logoutOverlay').style.display='none'; }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLogoutModal(); });

</script>


</body>
</html>
