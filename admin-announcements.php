<?php
require_once 'session_check_admin.php';
include 'db.php';

// Avatar initials from the admin's name
$admin_display_name = $session_admin_name ?? 'Admin';
$admin_name_parts = preg_split('/\s+/', trim($admin_display_name));
$admin_initials = strtoupper(substr($admin_name_parts[0], 0, 1) . substr($admin_name_parts[count($admin_name_parts) - 1] ?? '', 0, 1));
if (count($admin_name_parts) < 2) {
    $admin_initials = strtoupper(substr($admin_name_parts[0], 0, 2));
}

$result = $conn->query("
    SELECT a.id, a.title, a.body, a.image, a.is_pinned, a.created_at, u.username AS posted_by_name
    FROM announcements a
    LEFT JOIN admins u ON a.posted_by = u.id
    ORDER BY a.is_pinned DESC, a.created_at DESC
");
$announcements = [];
while ($row = $result->fetch_assoc()) $announcements[] = $row;

// Side panel stats
$total_count  = count($announcements);
$pinned_count = count(array_filter($announcements, fn($a) => (bool)$a['is_pinned']));
$week_count   = count(array_filter($announcements, fn($a) => strtotime($a['created_at']) >= strtotime('-7 days')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Barangay San Roque</title>
    <link rel="stylesheet" href="admin-style.css">
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
        }
        * { box-sizing: border-box; margin:0; padding:0; font-family:'Poppins',sans-serif; }
        html, body { overflow-x: hidden; }
        body { background:#f0f2f5; }

        /* =============================================
           TOP BAR + SIDEBAR RAIL — copied from admin-dashboard.php
           (this chrome CSS lives inline there, not in admin-style.css)
           ============================================= */
        .sidebar {
            width: 76px; height: 100vh; background: #004d2c; position: fixed; top: 0; left: 0;
            display: flex; flex-direction: column; justify-content: space-between; align-items: center;
            padding: 20px 0; z-index: 9200; transform: translateX(0); transition: transform 0.3s ease;
            overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.25) transparent;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.25); border-radius: 4px; }
        .sidebar-top, .sidebar-bottom { display: flex; flex-direction: column; align-items: center; gap: 6px; width: 100%; }
        .nav-item {
            width: 46px; height: 46px; display: flex; justify-content: center; align-items: center;
            cursor: pointer; transition: background 0.2s, opacity 0.2s; opacity: 0.65; border-radius: 12px;
            text-decoration: none; margin-bottom: 4px;
        }
        .nav-item.active, .nav-item:hover { opacity: 1; background-color: rgba(255,255,255,0.14); }
        .nav-item img { width: 22px; height: 22px; filter: brightness(0) invert(1); }
        .sidebar-avatar {
            width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.14);
            border: 1.5px solid rgba(255,255,255,0.35); color: #fff; font-size: 0.72rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center; margin-bottom: 10px;
        }
        @media screen and (max-height: 700px) {
            .nav-item { width: 36px; height: 36px; }
            .nav-item img { width: 17px; height: 17px; }
            .sidebar-top, .sidebar-bottom { gap: 4px; }
            .sidebar { padding: 10px 0; }
        }
        .sidebar-close-btn {
            display: none; position: absolute; top: 14px; right: 14px; background: none; border: none;
            color: #fff; opacity: 0.75; width: 32px; height: 32px; align-items: center; justify-content: center;
            cursor: pointer; border-radius: 8px;
        }
        .sidebar-close-btn:hover { opacity: 1; background: rgba(255,255,255,0.14); }
        .sidebar-close-btn svg { width: 18px; height: 18px; }
        .app-topbar {
            position: fixed; top: 0; left: 76px; width: calc(100% - 76px); height: 64px; background: #fff;
            border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;
            padding: 0 28px; z-index: 200;
        }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .hamburger-btn {
            display: none; background: none; border: none; color: var(--green-900); width: 34px; height: 34px;
            align-items: center; justify-content: center; cursor: pointer; border-radius: 8px; flex-shrink: 0;
        }
        .hamburger-btn:hover { background: var(--green-100); }
        .hamburger-btn img { width: 20px; height: 20px; filter: invert(17%) sepia(35%) saturate(1352%) hue-rotate(115deg) brightness(94%) contrast(92%); }
        .topbar-titles { display: flex; flex-direction: column; line-height: 1.25; }
        .topbar-eyebrow { font-size: 0.66rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted); }
        .topbar-title { font-size: 1rem; font-weight: 600; color: var(--green-900); }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .topbar-avatar {
            width: 36px; height: 36px; border-radius: 50%; background: var(--green-700); color: #fff;
            font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .portal-content { margin-left: 76px; width: 100%; min-height: 100vh; }
        @media screen and (max-width: 992px) {
            .sidebar { transform: translateX(-100%); box-shadow: 6px 0 24px rgba(0,0,0,0.18); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-close-btn { display: flex; }
            .hamburger-btn { display: flex; }
            .app-topbar { left: 0; width: 100%; padding: 0 16px; }
            .portal-content { margin-left: 0; width: 100%; }
        }
        @media screen and (max-width: 768px) {
            .topbar-eyebrow { display: none; }
            .topbar-title { font-size: 0.95rem; }
            .portal-content { padding: 84px 12px 24px !important; }
        }

        .page-layout { max-width: 1180px; margin: 0 auto; display: flex; gap: 24px; align-items: flex-start; }
        .feed-wrap { flex: 1 1 0; max-width: 760px; min-width: 0; }
        .side-panel { width: 300px; flex-shrink: 0; display: flex; flex-direction: column; gap: 16px; position: sticky; top: 84px; }
        @media screen and (max-width: 1100px) {
            .side-panel { display: none; }
            .feed-wrap { max-width: 760px; margin: 0 auto; }
        }

        .side-card {
            background: #fff; border-radius: 10px; padding: 16px 18px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }
        .side-card h3 {
            font-size: 13px; font-weight: 700; color: var(--green-900);
            text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 12px;
        }
        .side-stat-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f2f5; }
        .side-stat-row:last-child { border-bottom: none; }
        .side-stat-label { font-size: 13px; color: #65676b; }
        .side-stat-value { font-size: 15px; font-weight: 700; color: #050505; }
        .side-link {
            display: flex; align-items: center; gap: 10px; padding: 9px 8px; border-radius: 8px;
            text-decoration: none; color: #050505; font-size: 13.5px; font-weight: 500; transition: background 0.15s;
        }
        .side-link:hover { background: #f0f2f5; }
        .side-link .icon { font-size: 16px; width: 20px; text-align: center; }
        .side-tip { font-size: 12.5px; color: #65676b; line-height: 1.6; }

        .ann-alert { display:none; font-size:13px; padding:10px 14px; border-radius:8px; margin-bottom:16px; }
        .ann-alert.show { display:block; }
        .ann-alert.error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .ann-alert.success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        
        /* Notification bell */
        .notif-wrapper{position:relative;display:flex;justify-content:center;align-items:center;padding:15px;cursor:pointer;opacity:0.6;transition:opacity 0.2s;}
        .notif-wrapper:hover{opacity:1;}
        .notif-badge{position:absolute;top:4px;right:4px;background:#e53e3e;color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:none;align-items:center;justify-content:center;padding:0 4px;border:2px solid #004d2c;line-height:1;}
        .notif-badge.has-notif{display:flex;}
        .notif-overlay{display:none;position:fixed;inset:0;z-index:9000;}
        .notif-overlay.show{display:block;}
        .notif-dropdown{position:fixed;top:0;left:-420px;width:360px;height:100vh;background:#fff;border-radius:0 16px 16px 0;box-shadow:6px 0 30px rgba(0,0,0,.15);z-index:9999;display:flex;flex-direction:column;transition:left .32s cubic-bezier(0.4,0,0.2,1);overflow:hidden;}
        .notif-dropdown.open{left:76px;}
        .notif-panel-header{background:#004d2c;color:#fff;padding:20px 22px 16px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;}
        .notif-panel-header h3{font-size:15px;font-weight:600;margin:0 0 3px;}
        .notif-panel-header small{font-size:11px;opacity:.75;display:block;}
        .mark-all-read-btn{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:11px;font-weight:600;padding:5px 12px;border-radius:20px;cursor:pointer;white-space:nowrap;}
        .notif-list{overflow-y:auto;flex:1;}
        .n-item{display:flex;gap:14px;padding:15px 18px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;transition:background .15s;}
        .n-item:hover{background:#f8fafc;}
        .n-item.unread{background:#f0fdf4;border-left:3px solid #2D6A4F;}
        .n-dot{width:36px;height:36px;border-radius:50%;background:#e8f5e9;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .n-dot img{width:16px;filter:invert(29%) sepia(61%) saturate(446%) hue-rotate(105deg);}
        .n-item.unread .n-dot{background:#2D6A4F;}
        .n-item.unread .n-dot img{filter:brightness(0) invert(1);}
        .n-body strong{display:block;font-size:13px;color:#1a202c;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;max-width:240px;}
        .n-body p{font-size:12px;color:#64748b;margin:0 0 3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
        .n-body time{font-size:11px;color:#94a3b8;}
        .n-empty{text-align:center;padding:50px 20px;color:#94a3b8;}
        .n-empty img{width:40px;opacity:.28;display:block;margin:0 auto 10px;}

        /* =============================================
           FACEBOOK-STYLE COMPOSER TRIGGER BAR
           ============================================= */
        .fb-avatar {
            width: 40px; height: 40px; border-radius: 50%; background: var(--green-700); color: #fff;
            font-size: 0.8rem; font-weight: 700; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .fb-composer-bar {
            background:#fff; border-radius:12px; padding:14px 16px; margin-bottom:16px;
            box-shadow:0 1px 2px rgba(0,0,0,0.08); display:flex; flex-direction:column; gap:12px;
        }
        .fb-composer-top { display:flex; align-items:center; gap:10px; }
        .fb-composer-fake-input {
            flex:1; background:#f0f2f5; border:none; border-radius:24px; padding:11px 16px;
            font-size:14px; color:#65676b; text-align:left; cursor:pointer; transition:background 0.15s;
        }
        .fb-composer-fake-input:hover { background:#e4e6e9; }
        .fb-composer-divider { height:1px; background:#e4e6e9; }
        .fb-composer-bottom { display:flex; }
        .fb-photo-shortcut {
            display:flex; align-items:center; justify-content:center; gap:8px; flex:1;
            background:none; border:none; border-radius:8px; padding:8px; cursor:pointer;
            font-size:13.5px; font-weight:600; color:#65676b; transition:background 0.15s;
        }
        .fb-photo-shortcut:hover { background:#f2f2f2; }
        .fb-photo-shortcut .icon { font-size:18px; }

        /* =============================================
           COMPOSER MODAL (create + edit)
           ============================================= */
        .fb-modal-overlay {
            display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:99998;
            justify-content:center; align-items:flex-start; padding:40px 16px; overflow-y:auto;
        }
        .fb-modal-overlay.show { display:flex; }
        .fb-modal-box {
            background:#fff; border-radius:12px; width:100%; max-width:520px;
            box-shadow:0 12px 40px rgba(0,0,0,0.25); display:flex; flex-direction:column; max-height:90vh;
        }
        .fb-modal-header {
            display:flex; align-items:center; justify-content:center; position:relative;
            padding:14px 16px; border-bottom:1px solid #e4e6e9;
        }
        .fb-modal-header h2 { font-size:17px; font-weight:700; color:#050505; }
        .fb-modal-close {
            position:absolute; right:14px; top:50%; transform:translateY(-50%);
            width:32px; height:32px; border-radius:50%; border:none; background:#e4e6e9;
            color:#050505; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center;
        }
        .fb-modal-close:hover { background:#d8dade; }
        .fb-modal-body { padding:14px 16px; overflow-y:auto; flex:1; }
        .fb-modal-user-row { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
        .fb-modal-user-name { font-size:14.5px; font-weight:600; color:#050505; }
        .fb-modal-audience-pill {
            display:inline-flex; align-items:center; gap:4px; background:#f0f2f5; border-radius:4px;
            padding:2px 8px; font-size:11.5px; font-weight:600; color:#65676b; margin-top:2px; width:fit-content;
        }
        .fb-title-input {
            width:100%; border:none; outline:none; font-size:16px; font-weight:700; color:#050505;
            padding:6px 0; font-family:'Poppins',sans-serif;
        }
        .fb-title-input::placeholder { color:#8a8d91; font-weight:600; }
        .fb-body-textarea {
            width:100%; border:none; outline:none; resize:none; font-size:15px; color:#050505;
            padding:6px 0; min-height:90px; font-family:'Poppins',sans-serif; line-height:1.5;
        }
        .fb-body-textarea::placeholder { color:#8a8d91; }

        .fb-image-drop {
            position:relative; border:1px dashed #ccd0d5; border-radius:10px; margin-top:8px;
            display:flex; align-items:center; justify-content:center; padding:26px 16px; cursor:pointer;
            transition:background 0.15s, border-color 0.15s; text-align:center;
        }
        .fb-image-drop:hover, .fb-image-drop.dragover { background:#f7f8fa; border-color:var(--green-700); }
        .fb-image-drop input[type="file"] { display:none; }
        .fb-image-drop .icon { font-size:26px; display:block; margin-bottom:6px; }
        .fb-image-drop .txt { font-size:13px; color:#65676b; }
        .fb-image-drop .txt strong { color:var(--green-700); }

        .fb-image-preview { display:none; position:relative; margin-top:8px; border-radius:10px; overflow:hidden; }
        .fb-image-preview img { display:block; width:100%; max-height:320px; object-fit:cover; }
        .fb-image-remove-btn {
            position:absolute; top:8px; right:8px; width:32px; height:32px; border-radius:50%;
            background:rgba(0,0,0,0.55); color:#fff; border:none; font-size:16px; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
        }
        .fb-image-remove-btn:hover { background:rgba(0,0,0,0.75); }

        .fb-add-to-post-row {
            display:flex; align-items:center; justify-content:space-between; margin-top:14px;
            border:1px solid #dddfe2; border-radius:10px; padding:10px 14px;
        }
        .fb-add-to-post-label { font-size:13.5px; font-weight:600; color:#050505; }
        .fb-add-to-post-icons { display:flex; align-items:center; gap:6px; }
        .fb-add-icon-btn {
            width:36px; height:36px; border-radius:50%; border:none; background:none; cursor:pointer;
            display:flex; align-items:center; justify-content:center; font-size:18px; transition:background 0.15s;
        }
        .fb-add-icon-btn:hover { background:#f0f2f5; }
        .fb-add-icon-btn.pinned-active { background:var(--green-100); }

        .fb-modal-footer { padding:12px 16px 16px; }
        .fb-post-submit-btn {
            width:100%; background:var(--green-700); color:#fff; border:none; padding:11px;
            border-radius:8px; font-size:15px; font-weight:700; cursor:pointer; transition:background 0.15s;
            font-family:'Poppins',sans-serif;
        }
        .fb-post-submit-btn:hover { background:var(--green-900); }
        .fb-post-submit-btn:disabled { opacity:0.6; cursor:not-allowed; }

        /* =============================================
           FEED — posted announcements
           ============================================= */
        .fb-post {
            background:#fff; border-radius:10px; margin-bottom:16px; padding:14px 16px 6px;
            box-shadow:0 1px 2px rgba(0,0,0,0.08); position:relative;
        }
        .fb-pinned-tag {
            display:flex; align-items:center; gap:6px; font-size:12.5px; font-weight:600;
            color:var(--green-700); margin-bottom:8px;
        }
        .fb-post-header { display:flex; align-items:flex-start; gap:10px; }
        .fb-post-headinfo { flex:1; min-width:0; }
        .fb-post-name { font-size:14.5px; font-weight:600; color:#050505; }
        .fb-post-time { font-size:12.5px; color:#65676b; }

        .fb-post-kebab-wrap { position:relative; }
        .fb-post-kebab {
            width:34px; height:34px; border-radius:50%; border:none; background:none;
            cursor:pointer; font-size:18px; color:#65676b; display:flex; align-items:center; justify-content:center;
        }
        .fb-post-kebab:hover { background:#f0f2f5; }
        .fb-post-dropdown {
            display:none; position:absolute; right:0; top:38px; background:#fff; border-radius:10px;
            box-shadow:0 6px 20px rgba(0,0,0,0.18); min-width:170px; z-index:50; overflow:hidden;
            border:1px solid #eee;
        }
        .fb-post-dropdown.open { display:block; }
        .fb-post-dropdown button {
            display:flex; align-items:center; gap:10px; width:100%; text-align:left; background:none;
            border:none; padding:11px 14px; font-size:13.5px; font-weight:500; color:#050505; cursor:pointer;
            font-family:'Poppins',sans-serif;
        }
        .fb-post-dropdown button:hover { background:#f0f2f5; }
        .fb-post-dropdown button.danger-item { color:#e0245e; }
        .fb-post-dropdown button.danger-item:hover { background:#fef2f2; }

        .fb-post-title { font-weight:700; font-size:15px; color:#050505; margin-top:10px; }
        .fb-post-body { font-size:14.5px; color:#050505; line-height:1.5; white-space:pre-line; margin-top:4px; padding-bottom:12px; }
        .fb-post-image { display:block; width:calc(100% + 32px); margin-left:-16px; max-height:420px; object-fit:cover; }

        .ann-empty { color:var(--muted); font-size:13.5px; text-align:center; padding:40px 0; }

        /* Notification panel content — copied from admin-dashboard.php */
        .mark-all-read-btn{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:11px;font-weight:600;padding:5px 12px;border-radius:20px;cursor:pointer;white-space:nowrap;}
        .n-item{display:flex;gap:14px;padding:15px 18px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;transition:background .15s;}
        .n-item:hover{background:#f8fafc;}
        .n-item.unread{background:#f0fdf4;border-left:3px solid #2D6A4F;}
        .n-dot{width:36px;height:36px;border-radius:50%;background:#e8f5e9;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .n-dot img{width:16px;filter:invert(29%) sepia(61%) saturate(446%) hue-rotate(105deg);}
        .n-item.unread .n-dot{background:#2D6A4F;}
        .n-item.unread .n-dot img{filter:brightness(0) invert(1);}
        .n-body strong{display:block;font-size:13px;color:#1a202c;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;max-width:240px;}
        .n-body p{font-size:12px;color:#64748b;margin:0 0 3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
        .n-body time{font-size:11px;color:#94a3b8;}
        .n-empty{text-align:center;padding:50px 20px;color:#94a3b8;}
        .n-empty img{width:40px;opacity:.28;display:block;margin:0 auto 10px;}

        /* ── Delete confirmation modal ── */
        .ann-modal-overlay {
            display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:99999;
            justify-content:center; align-items:center; padding:20px; backdrop-filter:blur(3px);
        }
        .ann-modal-overlay.show { display:flex; }
        .ann-modal-box {
            background:#fff; border-radius:18px; padding:36px 32px 28px; width:100%; max-width:380px;
            text-align:center; box-shadow:0 24px 60px rgba(0,0,0,0.18); position:relative;
        }
        .ann-modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer; }
        .ann-modal-icon { width:64px; height:64px; border-radius:50%; background:#fee2e2; display:flex; align-items:center; justify-content:center; margin:0 auto 18px; font-size:28px; }
        .ann-modal-title { font-size:18px; font-weight:700; color:#1a202c; margin:0 0 8px; }
        .ann-modal-sub { font-size:13px; color:#64748b; margin:0 0 28px; line-height:1.6; }
        .ann-modal-danger-btn {
            display:block; width:100%; background:linear-gradient(135deg,#dc2626,#991b1b); color:#fff;
            padding:13px; border:none; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer;
            font-family:'Poppins',sans-serif;
        }
        .btn-secondary {
            background:#f8fafc; color:#374151; border:1.5px solid #e2e8f0; padding:11px 20px; border-radius:9px;
            font-size:13px; font-weight:600; cursor:pointer;
        }
    </style>
</head>
<body class="admin-dashboard-layout">

    <!-- Top bar -->
    <header class="app-topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                <img src="menu.png" alt="Menu">
            </button>
            <div class="topbar-titles">
                <span class="topbar-eyebrow">Admin Panel</span>
                <span class="topbar-title">Announcements</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-avatar" title="<?php echo htmlspecialchars($admin_display_name); ?>">
                <?php echo htmlspecialchars($admin_initials); ?>
            </div>
        </div>
    </header>

    <!-- Sidebar drawer backdrop (mobile only) -->
    <div class="res-notif-overlay" id="sidebarDrawerOverlay" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.25);"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebarNav">
        <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close menu">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        <div class="sidebar-top">
            <a href="admin-dashboard.php"           class="nav-item" title="Dashboard"><img src="dashboard.png"></a>
            <a href="admin-lupon-assignments.php"   class="nav-item" title="Lupon Assignments"><img src="people.png"></a>
            <a href="admin-committee.php"           class="nav-item" title="Committee"><img src="committee.png"></a>
            <a href="admin-calendar.php"            class="nav-item" title="Calendar"><img src="calendar.png"></a>
            <a href="admin-announcements.php"       class="nav-item active" title="Announcements"><img src="announcements.png"></a>
            <a href="admin-file-maintenance.php"    class="nav-item" title="File Maintenance"><img src="file.png"></a>
            <a href="admin-audit-trail.php"          class="nav-item" title="Audit Trail"><img src="audit.png"></a>
            <div class="nav-item notif-wrapper" id="bellBtn">
                <img src="bell.png" id="bellIcon">
                <span class="notif-badge" id="notifBadge"></span>
            </div>
            <a href="#" onclick="openLogoutModal(); return false;" class="nav-item logout-item" title="Logout">
                <img src="logout.png">
            </a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-avatar" title="<?php echo htmlspecialchars($admin_display_name); ?>">
                <?php echo htmlspecialchars($admin_initials); ?>
            </div>
        </div>
    </nav>

    <!-- Notification panel -->
    <div class="notif-overlay" id="notifOverlay"></div>
    <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-panel-header">
            <div><h3>Notifications</h3><small id="nLabel">Loading…</small></div>
            <button class="mark-all-read-btn" id="markReadBtn">Mark all read</button>
        </div>
        <div class="notif-list" id="nList">
            <div class="n-empty"><img src="bell.png"><p>No notifications yet.</p></div>
        </div>
    </div>

    <main class="portal-content" style="padding:84px 20px 30px;">
    <div class="page-layout">
    <div class="feed-wrap">

        <div class="ann-alert" id="alertBox"></div>

        <!-- ── Facebook-style composer trigger bar ── -->
        <div class="fb-composer-bar">
            <div class="fb-composer-top">
                <div class="fb-avatar"><?php echo htmlspecialchars($admin_initials); ?></div>
                <button class="fb-composer-fake-input" onclick="openComposer('new')">
                    What's the announcement, <?php echo htmlspecialchars($admin_display_name); ?>?
                </button>
            </div>
            <div class="fb-composer-divider"></div>
            <div class="fb-composer-bottom">
                <button class="fb-photo-shortcut" onclick="openComposer('new', true)">
                    <span class="icon">🖼️</span> Photo
                </button>
            </div>
        </div>

        <!-- ── Feed of posted announcements ── -->
        <div id="annList">
        <?php if (empty($announcements)): ?>
            <p class="ann-empty" id="annEmptyMsg">No announcements posted yet.</p>
        <?php else: foreach ($announcements as $row): ?>
            <div class="fb-post" id="ann-<?php echo $row['id']; ?>">
                <?php if ($row['is_pinned']): ?>
                    <div class="fb-pinned-tag">📌 Pinned Announcement</div>
                <?php endif; ?>
                <div class="fb-post-header">
                    <div class="fb-avatar"><?php echo htmlspecialchars($admin_initials); ?></div>
                    <div class="fb-post-headinfo">
                        <div class="fb-post-name"><?php echo htmlspecialchars($row['posted_by_name'] ?? 'Admin'); ?></div>
                        <div class="fb-post-time"><?php echo date('M j, Y g:i A', strtotime($row['created_at'])); ?></div>
                    </div>
                    <div class="fb-post-kebab-wrap">
                        <button class="fb-post-kebab" onclick="toggleKebab(<?php echo $row['id']; ?>)">⋯</button>
                        <div class="fb-post-dropdown" id="kebab-<?php echo $row['id']; ?>">
                            <button onclick='editAnnouncement(<?php echo htmlspecialchars(json_encode([
                                'id' => $row['id'],
                                'title' => $row['title'],
                                'body' => $row['body'],
                                'image' => $row['image'],
                                'is_pinned' => (bool)$row['is_pinned'],
                            ]), ENT_QUOTES); ?>)'>✏️ Edit announcement</button>
                            <button class="danger-item" onclick="openDeleteModal(<?php echo $row['id']; ?>)">🗑️ Delete announcement</button>
                        </div>
                    </div>
                </div>
                <div class="fb-post-title"><?php echo htmlspecialchars($row['title']); ?></div>
                <div class="fb-post-body"><?php echo nl2br(htmlspecialchars($row['body'])); ?></div>
                <?php if (!empty($row['image'])): ?>
                    <img src="<?php echo htmlspecialchars($row['image']); ?>" class="fb-post-image" alt="">
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
        </div>

    </div>

    <!-- ── Side panel — utilizes the wide desktop space ── -->
    <div class="side-panel">
        <div class="side-card">
            <h3>Bulletin Stats</h3>
            <div class="side-stat-row">
                <span class="side-stat-label">Total announcements</span>
                <span class="side-stat-value"><?php echo $total_count; ?></span>
            </div>
            <div class="side-stat-row">
                <span class="side-stat-label">Pinned</span>
                <span class="side-stat-value"><?php echo $pinned_count; ?></span>
            </div>
            <div class="side-stat-row">
                <span class="side-stat-label">Posted this week</span>
                <span class="side-stat-value"><?php echo $week_count; ?></span>
            </div>
        </div>

        <div class="side-card">
            <h3>Quick Links</h3>
            <a href="admin-dashboard.php" class="side-link"><span class="icon">🏠</span> Dashboard</a>
            <a href="admin-lupon-assignments.php" class="side-link"><span class="icon">⚖️</span> Lupon Assignments</a>
            <a href="admin-committee.php" class="side-link"><span class="icon">👥</span> Committee</a>
            <a href="admin-calendar.php" class="side-link"><span class="icon">📅</span> Calendar</a>
            <a href="admin-file-maintenance.php" class="side-link"><span class="icon">📁</span> File Maintenance</a>
            <a href="admin-audit-trail.php" class="side-link"><span class="icon">🕒</span> Audit Trail</a>
        </div>

        <div class="side-card">
            <h3>Posting Tips</h3>
            <p class="side-tip">
                Pin time-sensitive notices (advisories, schedule changes) so residents see them first.
                Keep titles short and specific — residents scan the bulletin quickly.
            </p>
        </div>
    </div>
    </div>
    </main>

    <!-- ── Composer modal (create + edit) ── -->
    <div id="composerOverlay" class="fb-modal-overlay" onclick="if(event.target===this)closeComposer()">
        <div class="fb-modal-box">
            <div class="fb-modal-header">
                <h2 id="composerHeaderTitle">Create Announcement</h2>
                <button class="fb-modal-close" onclick="closeComposer()">×</button>
            </div>
            <div class="fb-modal-body">
                <input type="hidden" id="editId" value="">
                <input type="hidden" id="existingImage" value="">

                <div class="fb-modal-user-row">
                    <div class="fb-avatar"><?php echo htmlspecialchars($admin_initials); ?></div>
                    <div>
                        <div class="fb-modal-user-name"><?php echo htmlspecialchars($admin_display_name); ?></div>
                        <div class="fb-modal-audience-pill">🌐 Residents · Bulletin board</div>
                    </div>
                </div>

                <input type="text" id="title" class="fb-title-input" placeholder="Announcement title">
                <textarea id="body" class="fb-body-textarea" placeholder="What would you like to announce?"></textarea>

                <div class="fb-image-drop" id="uploadArea">
                    <input type="file" id="imageInput" accept="image/jpeg,image/png,image/webp">
                    <div id="uploadPrompt">
                        <span class="icon">🖼️</span>
                        <div class="txt"><strong>Click to add a photo</strong> or drag one here<br>JPG, PNG or WEBP, up to 5MB</div>
                    </div>
                </div>
                <div id="previewWrap" class="fb-image-preview">
                    <img id="previewImg" src="">
                    <button type="button" id="removeImageBtn" class="fb-image-remove-btn">×</button>
                </div>

                <div class="fb-add-to-post-row">
                    <span class="fb-add-to-post-label">Add to your announcement</span>
                    <div class="fb-add-to-post-icons">
                        <button type="button" class="fb-add-icon-btn" title="Add photo" onclick="document.getElementById('imageInput').click()">🖼️</button>
                        <button type="button" class="fb-add-icon-btn" id="pinToggleBtn" title="Pin to top" onclick="togglePin()">📌</button>
                    </div>
                </div>
                <input type="checkbox" id="isPinned" style="display:none;">
            </div>
            <div class="fb-modal-footer">
                <button class="fb-post-submit-btn" id="submitBtn" onclick="saveAnnouncement()">Post</button>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div id="deleteOverlay" class="ann-modal-overlay" onclick="if(event.target===this)closeDeleteModal()">
        <div class="ann-modal-box">
            <button onclick="closeDeleteModal()" class="ann-modal-close">×</button>
            <div class="ann-modal-icon">🗑️</div>
            <h2 class="ann-modal-title">Delete this announcement?</h2>
            <p class="ann-modal-sub">This will remove it from the resident bulletin board immediately. This cannot be undone.</p>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <button onclick="confirmDelete()" class="ann-modal-danger-btn">Yes, delete it</button>
                <button onclick="closeDeleteModal()" class="btn-secondary" style="width:100%;">Cancel</button>
            </div>
        </div>
    </div>

<!-- Logout confirmation modal -->
<div id="logoutOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99999;justify-content:center;align-items:center;padding:20px;backdrop-filter:blur(3px);" onclick="if(event.target===this)closeLogoutModal()">
    <div style="background:#fff;border-radius:18px;padding:36px 32px 28px;width:100%;max-width:380px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,0.18);position:relative;">
        <button onclick="closeLogoutModal()" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">×</button>
        <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
            <img src="logout.png" style="width:28px;height:28px;filter:invert(29%) sepia(91%) saturate(1500%) hue-rotate(330deg) brightness(90%);">
        </div>
        <h2 style="font-size:18px;font-weight:700;color:#1a202c;margin:0 0 8px;">Log out of your account?</h2>
        <p style="font-size:13px;color:#64748b;margin:0 0 28px;line-height:1.6;">You will be returned to the login page.<br>Any unsaved changes will be lost.</p>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <a href="logout.php" style="display:block;background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;padding:13px;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">Yes, log me out</a>
            <button onclick="closeLogoutModal()" style="background:#f8fafc;color:#374151;border:1.5px solid #e2e8f0;padding:13px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel, stay logged in</button>
        </div>
    </div>
</div>

<script>
function openLogoutModal()  { document.getElementById('logoutOverlay').style.display = 'flex'; }
function closeLogoutModal() { document.getElementById('logoutOverlay').style.display = 'none'; }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLogoutModal(); });

// Sidebar drawer (mobile)
(function() {
    const sidebar   = document.getElementById('sidebarNav');
    const hamburger = document.getElementById('hamburgerBtn');
    const closeBtn  = document.getElementById('sidebarCloseBtn');
    const drawerOv  = document.getElementById('sidebarDrawerOverlay');
    function openDrawer()  { sidebar.classList.add('open'); drawerOv.style.display = 'block'; }
    function closeDrawer() { sidebar.classList.remove('open'); drawerOv.style.display = 'none'; }
    if (hamburger) hamburger.addEventListener('click', openDrawer);
    if (closeBtn)  closeBtn.addEventListener('click', closeDrawer);
    drawerOv.addEventListener('click', closeDrawer);
})();

// Notification bell — copied from admin-dashboard.php
const bellBtn=document.getElementById('bellBtn'),dropdown=document.getElementById('notifDropdown'),overlay=document.getElementById('notifOverlay'),badge=document.getElementById('notifBadge'),nList=document.getElementById('nList'),nLabel=document.getElementById('nLabel'),markBtn=document.getElementById('markReadBtn');
let bellOpen=false;
function taAgo(d){const s=Math.floor((new Date()-new Date(d))/1000);if(s<60)return'just now';if(s<3600)return Math.floor(s/60)+'m ago';if(s<86400)return Math.floor(s/3600)+'h ago';return Math.floor(s/86400)+'d ago';}
function escN(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function renderBell({notifications,unread_count}){
    if(unread_count>0){badge.textContent=unread_count>99?'99+':unread_count;badge.classList.add('has-notif');}
    else badge.classList.remove('has-notif');
    nLabel.textContent=unread_count>0?`${unread_count} unread`:'All caught up!';
    nList.innerHTML=notifications.length?notifications.map(n=>`<a class="n-item ${n.is_read==0?'unread':''}" href="${n.type === 'id_verification' ? 'admin-review-id.php?resident_id=' + n.resident_id : (n.type === 'new_appointment' || n.type === 'unassigned_warning') ? 'admin-lupon-assignments.php?complaint_id=' + n.complaint_id : 'admin-view-complaint.php?id=' + n.complaint_id}">
<div class="n-dot"><img src="${n.type === 'id_verification' ? 'people.png' : 'file.png'}"></div><div class="n-body"><strong>${escN(n.subject)}</strong><p>${escN(n.message)}</p><time>${taAgo(n.created_at)}</time></div></a>`).join(''):`<div class="n-empty"><img src="bell.png"><p>No notifications.</p></div>`;
}
function fetchBell(){fetch('get_notifications.php?action=fetch').then(r=>r.json()).then(renderBell).catch(()=>{});}
bellBtn.addEventListener('click',()=>{if(bellOpen){dropdown.classList.remove('open');overlay.classList.remove('show');bellOpen=false;}else{dropdown.classList.add('open');overlay.classList.add('show');bellOpen=true;}});
overlay.addEventListener('click',()=>{dropdown.classList.remove('open');overlay.classList.remove('show');bellOpen=false;});
markBtn.addEventListener('click',()=>fetch('get_notifications.php?action=mark_read').then(r=>r.json()).then(()=>fetchBell()));
fetchBell();

function showAlert(msg, type) {
    const box = document.getElementById('alertBox');
    box.textContent = msg;
    box.className = 'ann-alert show ' + type;
    setTimeout(() => box.classList.remove('show'), 4000);
}

function escAnn(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function escAttrJson(obj) {
    return JSON.stringify(obj)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Composer modal open/close ──
function openComposer(mode, focusPhoto) {
    if (mode === 'new') resetForm();
    document.getElementById('composerOverlay').classList.add('show');
    if (focusPhoto) setTimeout(() => document.getElementById('imageInput').click(), 150);
    document.body.style.overflow = 'hidden';
}
function closeComposer() {
    document.getElementById('composerOverlay').classList.remove('show');
    document.body.style.overflow = '';
}

// ── Pin toggle icon ──
function togglePin() {
    const cb  = document.getElementById('isPinned');
    cb.checked = !cb.checked;
    document.getElementById('pinToggleBtn').classList.toggle('pinned-active', cb.checked);
}

// ── Image upload area ──
const uploadArea      = document.getElementById('uploadArea');
const imageInput      = document.getElementById('imageInput');
const uploadPrompt    = document.getElementById('uploadPrompt');
const previewWrap     = document.getElementById('previewWrap');
const previewImg      = document.getElementById('previewImg');
const removeImageBtn  = document.getElementById('removeImageBtn');
let imageWasRemoved   = false; // true only if user explicitly removed an existing image during an edit

function showImagePreview(src) {
    previewImg.src = src;
    previewWrap.style.display = 'block';
    uploadArea.style.display = 'none';
}
function clearImagePreview() {
    imageInput.value = '';
    previewImg.src = '';
    previewWrap.style.display = 'none';
    uploadArea.style.display = 'flex';
}

uploadArea.addEventListener('click', () => imageInput.click());
imageInput.addEventListener('change', () => {
    const file = imageInput.files[0];
    if (!file) return;
    imageWasRemoved = false;
    const reader = new FileReader();
    reader.onload = e => showImagePreview(e.target.result);
    reader.readAsDataURL(file);
});
uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
uploadArea.addEventListener('drop', e => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) { imageInput.files = e.dataTransfer.files; imageInput.dispatchEvent(new Event('change')); }
});
removeImageBtn.addEventListener('click', e => {
    e.stopPropagation();
    clearImagePreview();
    imageWasRemoved = true; // tells the backend to drop the existing image on update
});

async function saveAnnouncement() {
    const id       = document.getElementById('editId').value;
    const title    = document.getElementById('title').value.trim();
    const body     = document.getElementById('body').value.trim();
    const isPinned = document.getElementById('isPinned').checked ? '1' : '0';

    if (!title || !body) { showAlert('Title and message are required.', 'error'); return; }

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    const originalLabel = submitBtn.textContent;
    submitBtn.textContent = 'Posting…';

    const fd = new FormData();
    if (id) fd.append('id', id);
    fd.append('title', title);
    fd.append('body', body);
    fd.append('is_pinned', isPinned);
    if (imageInput.files[0]) fd.append('image', imageInput.files[0]);
    if (imageWasRemoved) fd.append('remove_image', '1');

    try {
        const res  = await fetch('save_announcement.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAlert(id ? 'Announcement updated.' : 'Announcement posted.', 'success');
            closeComposer();
            resetForm();
            await refreshList();
        } else {
            showAlert(data.error || 'Failed to save announcement.', 'error');
        }
    } catch {
        showAlert('Network error. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalLabel;
    }
}

function editAnnouncement(a) {
    closeAllKebabs();
    document.getElementById('editId').value = a.id;
    document.getElementById('title').value = a.title;
    document.getElementById('body').value = a.body;
    document.getElementById('isPinned').checked = a.is_pinned;
    document.getElementById('pinToggleBtn').classList.toggle('pinned-active', a.is_pinned);
    document.getElementById('existingImage').value = a.image || '';
    imageWasRemoved = false;
    if (a.image) { showImagePreview(a.image); } else { clearImagePreview(); }
    document.getElementById('composerHeaderTitle').textContent = 'Edit Announcement';
    document.getElementById('submitBtn').textContent = 'Save Changes';
    document.getElementById('composerOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function resetForm() {
    document.getElementById('editId').value = '';
    document.getElementById('title').value = '';
    document.getElementById('body').value = '';
    document.getElementById('isPinned').checked = false;
    document.getElementById('pinToggleBtn').classList.remove('pinned-active');
    document.getElementById('existingImage').value = '';
    clearImagePreview();
    imageWasRemoved = false;
    document.getElementById('composerHeaderTitle').textContent = 'Create Announcement';
    document.getElementById('submitBtn').textContent = 'Post';
}

// ── Kebab (⋯) dropdown menus ──
function closeAllKebabs() {
    document.querySelectorAll('.fb-post-dropdown.open').forEach(d => d.classList.remove('open'));
}
function toggleKebab(id) {
    const dropdown = document.getElementById('kebab-' + id);
    const wasOpen = dropdown.classList.contains('open');
    closeAllKebabs();
    if (!wasOpen) dropdown.classList.add('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('.fb-post-kebab-wrap')) closeAllKebabs();
});

// ── Delete confirmation modal ──
let pendingDeleteId = null;

function openDeleteModal(id) {
    closeAllKebabs();
    pendingDeleteId = id;
    document.getElementById('deleteOverlay').classList.add('show');
}
function closeDeleteModal() {
    pendingDeleteId = null;
    document.getElementById('deleteOverlay').classList.remove('show');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDeleteModal(); });

async function confirmDelete() {
    if (!pendingDeleteId) return;
    const id = pendingDeleteId;
    closeDeleteModal();
    try {
        const fd = new FormData();
        fd.append('id', id);
        const res  = await fetch('delete_announcement.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAlert('Announcement deleted.', 'success');
            await refreshList();
        } else {
            showAlert(data.error || 'Failed to delete.', 'error');
        }
    } catch {
        showAlert('Network error. Please try again.', 'error');
    }
}

// ── Dynamic list refresh (no full page reload) ──
function renderPost(a) {
    return `
        <div class="fb-post" id="ann-${a.id}">
            ${a.is_pinned ? '<div class="fb-pinned-tag">📌 Pinned Announcement</div>' : ''}
            <div class="fb-post-header">
                <div class="fb-avatar"><?php echo htmlspecialchars($admin_initials); ?></div>
                <div class="fb-post-headinfo">
                    <div class="fb-post-name">${escAnn(a.posted_by)}</div>
                    <div class="fb-post-time">${a.created_at}</div>
                </div>
                <div class="fb-post-kebab-wrap">
                    <button class="fb-post-kebab" onclick="toggleKebab(${a.id})">⋯</button>
                    <div class="fb-post-dropdown" id="kebab-${a.id}">
                        <button onclick='editAnnouncement(${escAttrJson(a)})'>✏️ Edit announcement</button>
                        <button class="danger-item" onclick="openDeleteModal(${a.id})">🗑️ Delete announcement</button>
                    </div>
                </div>
            </div>
            <div class="fb-post-title">${escAnn(a.title)}</div>
            <div class="fb-post-body">${escAnn(a.body).replace(/\n/g,'<br>')}</div>
            ${a.image ? `<img src="${a.image}" class="fb-post-image" alt="">` : ''}
        </div>
    `;
}

async function refreshList() {
    const list = document.getElementById('annList');
    try {
        const res  = await fetch('list_announcements.php');
        const data = await res.json();
        if (!data.success) return;

        updateSideStats(data.announcements);

        if (!data.announcements.length) {
            list.innerHTML = '<p class="ann-empty" id="annEmptyMsg">No announcements posted yet.</p>';
            return;
        }

        list.innerHTML = data.announcements.map(renderPost).join('');
    } catch {
        // leave existing list as-is if refresh fails
    }
}

function updateSideStats(announcements) {
    const statEls = document.querySelectorAll('.side-stat-value');
    if (statEls.length < 3) return;
    const total  = announcements.length;
    const pinned = announcements.filter(a => a.is_pinned).length;
    const weekMs = Date.now() - 7 * 24 * 60 * 60 * 1000;
    const week   = announcements.filter(a => new Date(a.created_at).getTime() >= weekMs).length;
    statEls[0].textContent = total;
    statEls[1].textContent = pinned;
    statEls[2].textContent = week;
}
</script>

</body>
</html>
