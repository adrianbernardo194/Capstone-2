<?php
require_once 'session_check_resident.php';

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
    <title>Resident Portal - Barangay San Roque</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
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
        body { background: var(--paper); display: flex; min-height: 100vh; opacity: 0; animation: pageFadeIn 0.45s ease forwards; }

        @keyframes pageFadeIn { from { opacity: 0; } to { opacity: 1; } }
        body.page-leaving { animation: pageFadeOut 0.22s ease forwards; }
        @keyframes pageFadeOut { to { opacity: 0; } }

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
            animation: sidebarSlideIn 0.45s ease both;
        }

        @keyframes sidebarSlideIn {
            from { opacity: 0; transform: translateX(-16px); }
            to   { opacity: 1; transform: translateX(0); }
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
            animation: topbarFadeIn 0.45s ease 0.05s both;
        }

        @keyframes topbarFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
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
            animation: contentFadeUp 0.5s ease 0.1s both;
        }

        @keyframes contentFadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .portal-header { text-align: center; margin-bottom: 40px; }
        .mini-logo { width: 80px; margin-bottom: 10px; }
        .portal-header h1 { color: #1B4332; font-size: 2rem; }

        /* Verification status banner */
        .verify-banner {
            width: 100%;
            max-width: 1000px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 0.86rem;
            line-height: 1.55;
        }
        .verify-banner strong { display: block; margin-bottom: 2px; font-size: 0.92rem; }
        .verify-banner .vb-icon { font-size: 1.15rem; flex-shrink: 0; line-height: 1; margin-top: 1px; }

        .verify-banner.status-unverified { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
        .verify-banner.status-pending    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .verify-banner.status-rejected   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

        .verify-banner-body { flex: 1; }
        .get-verified-btn {
            margin-top: 10px;
            background: #1B4332;
            color: #fff;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }
        .get-verified-btn:hover { background: #2D6A4F; transform: translateY(-1px); }

        /* Get-verified modal */
        .verify-field-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #1B4332;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 16px 0 6px;
        }
        .verify-select {
            width: 100%;
            padding: 11px 13px;
            border: 1.5px solid #d7e4dc;
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            color: #374151;
            background: #fdfdfd;
        }
        .verify-select:focus { outline: none; border-color: #2D6A4F; background: #fff; }

        .verify-upload-area {
            position: relative;
            background: #fdfdfd;
            border: 2px dashed #c6d9ce;
            border-radius: 10px;
            padding: 22px 16px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
        }
        .verify-upload-area:hover, .verify-upload-area.dragover { border-color: #2D6A4F; background: #f0faf5; }
        .verify-upload-area input[type="file"] { display: none; }
        .verify-upload-icon { font-size: 1.8rem; display: block; margin-bottom: 6px; }
        .verify-upload-text { font-size: 0.82rem; color: #52796f; }
        .verify-upload-text strong { color: #2D6A4F; }

        .verify-preview-wrap { display: none; }
        .verify-preview-wrap img { max-width: 100%; max-height: 160px; border-radius: 8px; display: block; margin: 0 auto 10px; }
        .verify-remove-btn { background: none; border: none; color: #94a3b8; font-size: 0.78rem; cursor: pointer; text-decoration: underline; }
        .verify-remove-btn:hover { color: #e53e3e; }

        .verify-alert {
            display: none;
            font-size: 0.8rem;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 14px;
        }
        .verify-alert.show { display: block; }
        .verify-alert.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .verify-alert.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }

        /* =============================================
           DASHBOARD / ANNOUNCEMENT TAB SWITCHER
           ============================================= */
        .portal-tabs {
            display: flex;
            gap: 6px;
            background: #fff;
            border-radius: 30px;
            padding: 6px;
            width: fit-content;
            margin: 0 auto 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
        }
        .tab-btn {
            border: none;
            background: transparent;
            padding: 10px 26px;
            border-radius: 24px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .tab-btn:hover { background: var(--green-050); }
        .tab-btn.active { background: var(--green-700); color: #fff; }

        .tab-panel { display: none; width: 100%; }
        .tab-panel.active { display: flex; flex-direction: column; align-items: center; width: 100%; }

        /* Bulletin board (Announcement tab) */
        .bulletin-board {
            width: 100%;
            max-width: 1000px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 26px 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            margin-bottom: 40px;
        }
        .bulletin-list { display: flex; flex-direction: column; gap: 14px; }
        .bulletin-loading, .bulletin-empty { color: var(--muted); font-size: 0.85rem; text-align: center; padding: 20px 0; }
        .bulletin-post {
            background: var(--green-050);
            border: 1px solid var(--border);
            border-left: 4px solid var(--green-300);
            border-radius: 10px;
            padding: 16px 20px;
        }
        .bulletin-post.pinned { border-left-color: var(--green-700); background: var(--green-100); }
        .bulletin-pin-tag {
            display: inline-block;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: var(--green-700);
            margin-bottom: 4px;
        }
        .bulletin-post-title { font-weight: 700; font-size: 0.95rem; color: var(--green-900); margin-bottom: 4px; }
        .bulletin-post-image { display: block; max-width: 100%; max-height: 320px; border-radius: 8px; margin-bottom: 10px; object-fit: cover; }
        .bulletin-post-body { font-size: 0.86rem; color: var(--text); white-space: pre-line; margin-bottom: 8px; line-height: 1.6; }
        .bulletin-post-meta { font-size: 0.72rem; color: var(--muted); }

        .info-cards {
            display: flex;
            gap: 20px;
            width: 100%;
            max-width: 1000px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            flex: 1;
            opacity: 0;
            animation: cardEnter 0.55s ease both;
        }

        @keyframes cardEnter {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .info-cards .card:nth-child(1) { animation-delay: 0.22s; }
        .info-cards .card:nth-child(2) { animation-delay: 0.32s; }
        .info-cards .card:nth-child(3) { animation-delay: 0.42s; }

        .icon-bg {
            background: #e8f5e9;
            width: 50px; height: 50px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
        }

        .icon-bg img { width: 22px; filter: contrast(0.5); }

        /* =============================================
           MISSION & VISION SECTION
           ============================================= */

        .mv-section {
            width: 100%;
            max-width: 1000px;
            margin-bottom: 40px;
            animation: fadeInUp 0.7s ease both;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .mv-header { text-align: center; margin-bottom: 30px; }

        .mv-badge {
            display: inline-block;
            background: #1B4332;
            color: #d4edda;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            padding: 5px 18px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        .mv-title {
            color: #1B4332;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .mv-subtitle {
            color: #52796f;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        .mv-cards {
            display: flex;
            gap: 0;
            align-items: stretch;
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(27, 67, 50, 0.10);
            overflow: hidden;
            border: 1px solid #e0ede5;
        }

        .mv-card {
            flex: 1;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0;
            opacity: 0;
            animation: cardEnter 0.55s ease both;
        }

        .mission-card { animation-delay: 0.5s; }
        .vision-card { animation-delay: 0.6s; }

        .mv-card-accent { height: 5px; width: 100%; }
        .mission-card .mv-card-accent { background: linear-gradient(90deg, #1B4332, #2D6A4F); }
        .vision-card .mv-card-accent { background: linear-gradient(90deg, #2D6A4F, #40916c); }

        .mv-card-inner {
            padding: 28px 32px 28px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .mv-icon-wrap {
            width: 46px;
            height: 46px;
            background: #e8f5e9;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            color: #2D6A4F;
        }

        .mv-icon-wrap svg { width: 24px; height: 24px; }

        .mv-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.2em;
            color: #52796f;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .mv-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1B4332;
            margin-bottom: 12px;
        }

        .mv-divider {
            width: 36px;
            height: 3px;
            background: linear-gradient(90deg, #2D6A4F, #74c69d);
            border-radius: 2px;
            margin-bottom: 14px;
        }

        .mv-text {
            font-size: 0.86rem;
            line-height: 1.75;
            color: #374151;
            flex: 1;
            margin-bottom: 18px;
        }

        .mv-text em { font-style: normal; font-weight: 600; color: #1B4332; }

        .mv-pillars { display: flex; flex-wrap: wrap; gap: 8px; }

        .pillar {
            background: #e8f5e9;
            color: #2D6A4F;
            font-size: 0.72rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            letter-spacing: 0.04em;
            border: 1px solid #b7e4c7;
        }

        .mv-center-divider {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 10px;
            background: #f6fbf7;
            border-left: 1px solid #e0ede5;
            border-right: 1px solid #e0ede5;
        }

        .mv-center-line {
            flex: 1;
            width: 1px;
            background: linear-gradient(to bottom, transparent, #b7e4c7, transparent);
            min-height: 40px;
        }

        .mv-center-emblem { padding: 6px 0; }
        .mv-center-emblem svg { width: 36px; height: 36px; }

        .mv-quote-banner {
            margin-top: 22px;
            background: linear-gradient(135deg, #1B4332 0%, #2D6A4F 100%);
            border-radius: 12px;
            padding: 22px 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            position: relative;
            overflow: hidden;
        }

        .mv-quote-banner::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .mv-quote {
            font-size: 0.88rem;
            font-style: italic;
            color: #d4edda;
            text-align: center;
            line-height: 1.7;
            font-weight: 400;
            max-width: 700px;
            position: relative;
        }

        .mv-quote-leaves { font-size: 1.5rem; color: #52b788; flex-shrink: 0; position: relative; }
        .mv-leaves-right { transform: scaleX(-1); display: inline-block; }

        @media (max-width: 720px) {
            .mv-cards { flex-direction: column; }
            .mv-center-divider {
                flex-direction: row;
                padding: 10px 20px;
                border-left: none;
                border-right: none;
                border-top: 1px solid #e0ede5;
                border-bottom: 1px solid #e0ede5;
            }
            .mv-center-line {
                flex: 1;
                width: auto;
                height: 1px;
                min-height: unset;
                background: linear-gradient(to right, transparent, #b7e4c7, transparent);
            }
            .mv-quote-banner { flex-direction: column; gap: 8px; padding: 18px 20px; }
        }

        /* =============================================
           HOW-TO + ACTION BUTTON
           ============================================= */

        .how-to {
            background: white;
            width: 100%;
            max-width: 1000px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        }

        .how-to h2 { color: #1B4332; margin-bottom: 30px; text-align: center; }

        .step { display: flex; gap: 20px; margin-bottom: 20px; align-items: flex-start; opacity: 0; animation: stepEnter 0.5s ease both; }

        @keyframes stepEnter {
            from { opacity: 0; transform: translateX(-14px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .step:nth-child(2) { animation-delay: 0.62s; }
        .step:nth-child(3) { animation-delay: 0.7s; }
        .step:nth-child(4) { animation-delay: 0.78s; }
        .step:nth-child(5) { animation-delay: 0.86s; }
        .step-num {
            background: #2D6A4F; color: white;
            width: 32px; height: 32px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; font-weight: bold;
        }

        .action-btn {
            background: #1B4332; color: white; border: none;
            padding: 15px 35px; border-radius: 8px;
            font-weight: 600; cursor: pointer; font-size: 0.95rem;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px; margin: 30px 0 10px;
            transition: background 0.2s, transform 0.15s;
        }

        .action-btn:hover { background: #2D6A4F; transform: translateY(-1px); }
        .action-btn.disabled-btn {
            background: #cbd5c9;
            cursor: not-allowed;
            pointer-events: none;
        }
        .icon-white { width: 18px; filter: brightness(0) invert(1); }

        .footer-action { width: 100%; max-width: 1000px; text-align: center; }
        .footer-action p { color: var(--muted); font-size: 0.85rem; }
        .footer-action .gate-note { color: #9a3412; font-weight: 600; }

        /* =============================================
           MODALS
           ============================================= */

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

        .success-icon img {
            width: 40px;
            filter: invert(36%) sepia(85%) saturate(452%) hue-rotate(105deg) brightness(91%) contrast(87%);
        }

        .privacy-modal {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.65);
            display: none; justify-content: center; align-items: center;
            z-index: 9999; padding: 20px;
        }
        .privacy-modal.show { display: flex; }

        .privacy-content {
            width: 100%; max-width: 700px; max-height: 90vh; overflow-y: auto;
            background: #fff; padding: 25px; border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,.25);
        }

        .privacy-content h2 { color: #2d6a4f; margin-bottom: 15px; }

        .privacy-text {
            max-height: 300px; overflow-y: auto; line-height: 1.7;
            text-align: justify; padding-right: 10px;
        }
        .privacy-text p { margin-bottom: 12px; }
        .privacy-text p:last-child { margin-bottom: 0; }

        .privacy-check { margin-top: 20px; display: flex; align-items: flex-start; gap: 10px; }
        .privacy-check input { margin-top: 5px; flex-shrink: 0; }
        .privacy-check label { font-size: 0.9rem; line-height: 1.5; }

        #agreeBtn {
            margin-top: 20px; background: #2d6a4f; color: white; border: none;
            padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; width: 100%;
        }
        #agreeBtn:disabled { background: #b5b5b5; cursor: not-allowed; }

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

        /* ===================================
           RESPONSIVE DESIGN
           =================================== */

        @media screen and (max-width: 992px) {
            .sidebar { transform: translateX(-100%); box-shadow: 6px 0 24px rgba(0,0,0,0.18); animation: none; }
            .sidebar.open { transform: translateX(0); }
            .sidebar-close-btn { display: flex; }
            .hamburger-btn { display: flex; }

            .app-topbar { left: 0; width: 100%; padding: 0 16px; }

            .portal-content { margin-left: 0; width: 100%; padding: 84px 20px 40px; }

            .res-notif-panel.open { left: 0; }

            .info-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
            .mv-cards { display: flex; flex-direction: column; gap: 20px; }
            .mv-center-divider { display: none; }
            .portal-header h1 { font-size: 2rem; }
        }

        @media screen and (max-width: 768px) {
            .portal-content { padding: 78px 16px 32px; }

            .topbar-eyebrow { display: none; }
            .topbar-title { font-size: 0.95rem; }

            .portal-header { text-align: center; }
            .portal-header h1 { font-size: 1.5rem; line-height: 1.3; }
            .portal-header p { font-size: .95rem; }
            .portal-header small { font-size: .8rem; }

            .mini-logo { width: 64px; height: 64px; }

            .info-cards { grid-template-columns: 1fr; gap: 14px; }
            .card { width: 100%; padding: 22px; }

            .mv-card-inner { padding: 20px; }
            .mv-title { font-size: 1.35rem; }
            .mv-text { font-size: .9rem; line-height: 1.7; }

            .how-to { padding: 22px; }
            .step { flex-direction: row; text-align: left; }

            .action-btn { width: 100%; font-size: 1rem; }

            .res-notif-panel { width: 100%; max-width: 100vw; }
        }

        @media screen and (max-width: 480px) {
            .portal-content { padding: 74px 12px 28px; }
            .app-topbar { height: 58px; padding: 0 12px; }

            .portal-header h1 { font-size: 1.25rem; }
            .portal-header p { font-size: .82rem; }
            .portal-header small { font-size: .72rem; }

            .card { padding: 18px; }

            .mv-card-inner { padding: 15px; }
            .mv-title { font-size: 1.2rem; }
            .mv-text { font-size: .85rem; }

            .pillar { font-size: .7rem; padding: 3px 10px; }
            .action-btn { padding: 13px; }
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
            <span class="topbar-title">Dashboard</span>
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
        <a href="resident-portal.php" class="nav-item active" title="Dashboard">
            <img src="dashboard.png" alt="Dashboard">
        </a>
        <a href="complaint-form.php" class="nav-item" title="File Complaint">
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
            <img src="logout.png" alt="Logout">
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

<!-- Data Privacy Modal -->
<div id="privacyModal" class="privacy-modal">
    <div class="privacy-content">
        <h2>Data Privacy Notice</h2>

        <div class="privacy-text">
            <p>
                In accordance with Republic Act No. 10173, otherwise known as the
                Data Privacy Act of 2012, Barangay San Roque is committed to protecting
                your personal information.
            </p>

            <p>
                By using this system, you acknowledge that the information you provide,
                including personal details and complaint records, will be collected,
                processed, stored, and used solely for the purpose of complaint handling,
                mediation, case monitoring, and other official barangay functions.
            </p>

            <p>
                Your information will only be accessible to authorized barangay officials
                and personnel involved in the resolution of your complaint. The barangay
                shall implement reasonable organizational, physical, and technical
                security measures to protect your data from unauthorized access,
                disclosure, alteration, or misuse.
            </p>

            <p>
                By proceeding, you confirm that the information you provide is accurate
                and that you voluntarily consent to the collection and processing of
                your personal data for the purposes stated above.
            </p>
        </div>

        <div class="privacy-check">
            <input type="checkbox" id="agreeCheckbox">
            <label for="agreeCheckbox">
                I have read, understood, and agree to the Data Privacy Terms and Conditions.
            </label>
        </div>

        <button id="agreeBtn" disabled>I Agree and Continue</button>
    </div>
</div>

<!-- Get Verified Modal -->
<div id="verifyModal" class="privacy-modal" style="display:none;">
    <div class="privacy-content" style="max-width:480px;">
        <h2>Submit Your ID for Verification</h2>
        <p style="font-size:.85rem;color:#52796f;margin-bottom:16px;line-height:1.6;">
            Upload a clear photo of a valid government-issued ID. An official will review it before you can file complaints online.
        </p>

        <div class="verify-alert" id="verifyAlert"></div>

        <label class="verify-field-label">ID Type</label>
        <select class="verify-select" id="verifyIdType">
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

        <label class="verify-field-label">Photo of ID <span style="font-weight:400;text-transform:none;color:#94a3b8;">(JPG or PNG, max 5MB)</span></label>
        <div class="verify-upload-area" id="verifyUploadArea">
            <input type="file" id="verifyIdPhoto" accept="image/jpeg,image/png,image/jpg">
            <div id="verifyUploadPrompt">
                <span class="verify-upload-icon">🪪</span>
                <div class="verify-upload-text"><strong>Click to upload</strong> or drag and drop<br>Make sure all details are clear and readable</div>
            </div>
            <div class="verify-preview-wrap" id="verifyPreviewWrap">
                <img id="verifyPreviewImg" alt="ID preview">
                <button type="button" class="verify-remove-btn" id="verifyRemoveBtn">Remove photo</button>
            </div>
        </div>

        <div class="modal-choices">
            <button class="view-btn" id="verifySubmitBtn">Submit for Verification</button>
            <button class="return-btn" id="verifyCancelBtn">Cancel</button>
        </div>
    </div>
</div>

<main class="portal-content">
    <header class="portal-header">
        <img src="logo.png" alt="Logo" class="mini-logo">
        <h1>Welcome, <?php echo htmlspecialchars($session_resident_name); ?>!</h1>
        <p>Barangay San Roque, Marikina City</p>
        <small>Office of the Sangguniang Barangay - KP Form No. 9 (SUMBONG)</small>
    </header>

    <?php if ($session_id_verification_status === 'unverified'): ?>
    <div class="verify-banner status-unverified">
        <span class="vb-icon">⚠️</span>
        <div class="verify-banner-body">
            <strong>ID verification required to file a complaint</strong>
            You haven't submitted a valid ID yet, so complaint filing is locked for now. Submit a photo of your ID and an official will review it.
            <br><button type="button" class="get-verified-btn" id="openVerifyModalBtn">Get Verified</button>
        </div>
    </div>
    <?php elseif ($session_id_verification_status === 'pending'): ?>
    <div class="verify-banner status-pending">
        <span class="vb-icon">🕓</span>
        <div>
            <strong>Your ID is under review</strong>
            Thanks for submitting your ID. An official is reviewing it — you'll be able to file a complaint once it's verified.
        </div>
    </div>
    <?php elseif ($session_id_verification_status === 'rejected'): ?>
    <div class="verify-banner status-rejected">
        <span class="vb-icon">✕</span>
        <div class="verify-banner-body">
            <strong>Your ID could not be verified</strong>
            Please resubmit a clear photo of a valid ID before filing a complaint.
            <br><button type="button" class="get-verified-btn" id="openVerifyModalBtn2">Get Verified</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="portal-tabs">
        <button class="tab-btn active" id="tabBtnDashboard" onclick="switchPortalTab('dashboard')">Dashboard</button>
        <button class="tab-btn" id="tabBtnAnnouncement" onclick="switchPortalTab('announcement')">Announcement</button>
    </div>

    <div class="tab-panel active" id="panelDashboard">

    <section class="info-cards">
        <div class="card">
            <div class="icon-bg"><img src="file.png"></div>
            <h3>Easy Filing</h3>
            <p>Submit your complaint online using our digital SUMBONG form. No need to visit the office.</p>
        </div>
        <div class="card">
            <div class="icon-bg"><img src="secure.png"></div>
            <h3>Secure & Private</h3>
            <p>Your information is kept confidential. Only authorized officials can view your complaint.</p>
        </div>
        <div class="card">
            <div class="icon-bg"><img src="people.png"></div>
            <h3>Track Progress</h3>
            <p>Receive notifications and updates about your complaint status through this portal.</p>
        </div>
    </section>

    <!-- Mission & Vision Section -->
    <section class="mv-section">
        <div class="mv-header">
            <div class="mv-badge">Lupon Tagapamayapa</div>
            <h2 class="mv-title">Aming Misyon at Bisyon</h2>
            <p class="mv-subtitle">Katarungang Pambarangay · Barangay San Roque · Marikina City</p>
        </div>

        <div class="mv-cards">
            <!-- Mission Card -->
            <div class="mv-card mission-card">
                <div class="mv-card-accent"></div>
                <div class="mv-card-inner">
                    <div class="mv-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L3 7V12C3 16.55 6.84 20.74 12 22C17.16 20.74 21 16.55 21 12V7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M9 12L11 14L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="mv-label">MISYON</div>
                    <h3 class="mv-card-title">Mission</h3>
                    <div class="mv-divider"></div>
                    <p class="mv-text">
                        Upang itaguyod ang pagkakaisa, pagbubuklod, at mapayapang paglutas ng mga hidwaan sa loob ng ating barangay, ang Katarungang Pambarangay - Lupon Tagapamayapa ay nakatuon sa pagbibigay ng <em>accessible, patas at epektibong serbisyo</em> sa mediasyon. Layunin naming palakasin ang ating mga miyembro ng komunidad sa pamamagitan ng pagpapalaganap ng kultura ng diyalogo, pang-unawa, at kooperasyon, sa gayon ay lumikha ng mas ligtas at mas makabuluhang kapaligiran para sa pag-unlad at paglago.
                    </p>
                    <div class="mv-pillars">
                        <span class="pillar">Pagkakaisa</span>
                        <span class="pillar">Mediasyon</span>
                        <span class="pillar">Kooperasyon</span>
                    </div>
                </div>
            </div>

            <!-- Center Divider -->
            <div class="mv-center-divider">
                <div class="mv-center-line"></div>
                <div class="mv-center-emblem">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="20" cy="20" r="18" stroke="#2D6A4F" stroke-width="2"/>
                        <path d="M20 10C14.48 10 10 14.48 10 20C10 25.52 14.48 30 20 30C25.52 30 30 25.52 30 20C30 14.48 25.52 10 20 10Z" fill="#e8f5e9"/>
                        <path d="M20 14V20L24 22" stroke="#2D6A4F" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="mv-center-line"></div>
            </div>

            <!-- Vision Card -->
            <div class="mv-card vision-card">
                <div class="mv-card-accent"></div>
                <div class="mv-card-inner">
                    <div class="mv-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 12C1 12 5 4 12 4C19 4 23 12 23 12C23 12 19 20 12 20C5 20 1 12 1 12Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="mv-label">BISYON</div>
                    <h3 class="mv-card-title">Vision</h3>
                    <div class="mv-divider"></div>
                    <p class="mv-text">
                        Minimithi naming magkaroon ng isang Sangguinang Pambarangay kung saan ang mga hidwaan ay maaayos nang magalang, kung saan bawat ka-barangay ay naririnig at nirerespeto. Layunin ng Barangay San Roque - Katarungang Pambarangay - Lupon Tagapamayapa na maging isang <em>tanglaw ng kahusayan sa mediasyon</em>, kilala sa integridad, walang kinikilingan, at pagmamahal sa katarungan. Sa pamamagitan ng aming pagsisikap, hangarin naming itayo ang matagalang kapayapaan, matibay na ugnayan, at isang nagkakaisang komunidad.
                    </p>
                    <div class="mv-pillars">
                        <span class="pillar">Integridad</span>
                        <span class="pillar">Katarungan</span>
                        <span class="pillar">Kapayapaan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom banner quote -->
        <div class="mv-quote-banner">
            <div class="mv-quote-leaves mv-leaves-left">❧</div>
            <blockquote class="mv-quote">
                "Ang kapayapaan ay hindi lamang kawalan ng giyera — ito ay isang kultura ng diyalogo, pang-unawa, at paggalang sa isa't isa."
            </blockquote>
            <div class="mv-quote-leaves mv-leaves-right">❧</div>
        </div>
    </section>

    <section class="how-to">
        <h2>How to File a Complaint</h2>
        <div class="step"><div class="step-num">1</div><div><strong>Click "File New Complaint"</strong><p>Start by clicking the button below.</p></div></div>
        <div class="step"><div class="step-num">2</div><div><strong>Fill Out the Form</strong><p>Provide information about yourself and the respondent.</p></div></div>
        <div class="step"><div class="step-num">3</div><div><strong>Upload Supporting Documents</strong><p>Attach photos or relevant materials.</p></div></div>
        <div class="step"><div class="step-num">4</div><div><strong>Submit and Wait for Review</strong><p>Officials will review and notify you of updates.</p></div></div>
    </section>

    <div class="footer-action">
        <?php if ($session_id_verified): ?>
            <a href="complaint-form.php" style="text-decoration:none;">
                <button class="action-btn"><img src="file.png" class="icon-white"> File New Complaint</button>
            </a>
            <p>* All fields marked with * are required</p>
        <?php else: ?>
            <button class="action-btn disabled-btn" disabled><img src="file.png" class="icon-white"> File New Complaint</button>
            <p class="gate-note">Complaint filing is locked until your ID is verified.</p>
        <?php endif; ?>
    </div>

    </div><!-- /panelDashboard -->

    <div class="tab-panel" id="panelAnnouncement">
        <div class="bulletin-board">
            <div id="bulletinList" class="bulletin-list">
                <p class="bulletin-loading">Loading announcements…</p>
            </div>
        </div>
    </div><!-- /panelAnnouncement -->

</main>

<script>
// Data privacy modal — only show once per browser, not on every visit
const PRIVACY_KEY = "brgySanRoquePrivacyAgreed";
const checkbox = document.getElementById("agreeCheckbox");
const agreeBtn = document.getElementById("agreeBtn");
const privacyModal = document.getElementById("privacyModal");

if (localStorage.getItem(PRIVACY_KEY) !== "true") {
    privacyModal.classList.add("show");
}

checkbox.addEventListener("change", function () {
    agreeBtn.disabled = !this.checked;
});

agreeBtn.addEventListener("click", function () {
    localStorage.setItem(PRIVACY_KEY, "true");
    privacyModal.classList.remove("show");
});

// Get Verified modal
(function() {
    const modal        = document.getElementById('verifyModal');
    const openBtns      = [document.getElementById('openVerifyModalBtn'), document.getElementById('openVerifyModalBtn2')].filter(Boolean);
    const cancelBtn     = document.getElementById('verifyCancelBtn');
    const submitBtn     = document.getElementById('verifySubmitBtn');
    const idTypeSelect  = document.getElementById('verifyIdType');
    const uploadArea    = document.getElementById('verifyUploadArea');
    const fileInput     = document.getElementById('verifyIdPhoto');
    const uploadPrompt  = document.getElementById('verifyUploadPrompt');
    const previewWrap   = document.getElementById('verifyPreviewWrap');
    const previewImg    = document.getElementById('verifyPreviewImg');
    const removeBtn     = document.getElementById('verifyRemoveBtn');
    const alertBox      = document.getElementById('verifyAlert');

    if (!modal || openBtns.length === 0) return;

    function showAlert(msg, type) {
        alertBox.textContent = msg;
        alertBox.className = 'verify-alert show ' + type;
    }
    function hideAlert() { alertBox.className = 'verify-alert'; }

    function resetModal() {
        idTypeSelect.value = '';
        fileInput.value = '';
        previewImg.src = '';
        previewWrap.style.display = 'none';
        uploadPrompt.style.display = 'block';
        hideAlert();
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit for Verification';
    }

    function openModal() { resetModal(); modal.style.display = 'flex'; }
    function closeModal() { modal.style.display = 'none'; }

    openBtns.forEach(btn => btn.addEventListener('click', openModal));
    cancelBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });

    function handleFile(file) {
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
            showAlert('Please upload a JPG or PNG image.', 'error');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showAlert('File is too large. Maximum size is 5MB.', 'error');
            return;
        }
        hideAlert();
        const reader = new FileReader();
        reader.onload = e => {
            previewImg.src = e.target.result;
            previewWrap.style.display = 'block';
            uploadPrompt.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }

    uploadArea.addEventListener('click', e => { if (e.target !== fileInput && previewWrap.style.display !== 'block') fileInput.click(); });
    fileInput.addEventListener('change', () => handleFile(fileInput.files[0]));
    uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
    uploadArea.addEventListener('drop', e => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file) { fileInput.files = e.dataTransfer.files; handleFile(file); }
    });
    removeBtn.addEventListener('click', e => {
        e.stopPropagation();
        fileInput.value = '';
        previewImg.src = '';
        previewWrap.style.display = 'none';
        uploadPrompt.style.display = 'block';
    });

    submitBtn.addEventListener('click', async function() {
        hideAlert();
        if (!idTypeSelect.value) { showAlert('Please select your ID type.', 'error'); return; }
        if (!fileInput.files[0]) { showAlert('Please upload a photo of your ID.', 'error'); return; }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting…';

        const fd = new FormData();
        fd.append('id_type', idTypeSelect.value);
        fd.append('id_photo', fileInput.files[0]);

        try {
            const res = await fetch('submit_id_verification.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                showAlert('ID submitted! Refreshing your dashboard…', 'success');
                setTimeout(() => window.location.reload(), 1100);
            } else {
                showAlert(data.error || 'Something went wrong. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit for Verification';
            }
        } catch {
            showAlert('Network error. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit for Verification';
        }
    });
})();

// Dashboard / Announcement tab switcher
let bulletinLoaded = false;
function switchPortalTab(tab) {
    document.getElementById('tabBtnDashboard').classList.toggle('active', tab === 'dashboard');
    document.getElementById('tabBtnAnnouncement').classList.toggle('active', tab === 'announcement');
    document.getElementById('panelDashboard').classList.toggle('active', tab === 'dashboard');
    document.getElementById('panelAnnouncement').classList.toggle('active', tab === 'announcement');

    if (tab === 'announcement' && !bulletinLoaded) {
        loadBulletin();
        bulletinLoaded = true;
    }
}

function escapeHtmlBulletin(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

async function loadBulletin() {
    const list = document.getElementById('bulletinList');
    try {
        const res  = await fetch('get_announcements.php');
        const data = await res.json();

        if (!data.success || !data.announcements.length) {
            list.innerHTML = '<p class="bulletin-empty">No announcements posted yet.</p>';
            return;
        }

        list.innerHTML = data.announcements.map(a => `
            <div class="bulletin-post ${a.is_pinned ? 'pinned' : ''}">
                ${a.is_pinned ? '<div class="bulletin-pin-tag">📌 PINNED</div>' : ''}
                <div class="bulletin-post-title">${escapeHtmlBulletin(a.title)}</div>
                ${a.image ? `<img src="${a.image}" class="bulletin-post-image" alt="Announcement image">` : ''}
                <div class="bulletin-post-body">${escapeHtmlBulletin(a.body)}</div>
                <div class="bulletin-post-meta">Posted by ${escapeHtmlBulletin(a.posted_by)} · ${a.created_at}</div>
            </div>
        `).join('');
    } catch {
        list.innerHTML = '<p class="bulletin-empty">Could not load announcements.</p>';
    }
}

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

// Page-to-page fade transition
(function() {
    document.querySelectorAll('a[href]').forEach(function(link) {
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || link.target === '_blank') return;

        link.addEventListener('click', function(e) {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return; // let new-tab clicks through untouched
            e.preventDefault();
            document.body.classList.add('page-leaving');
            setTimeout(function() { window.location.href = href; }, 220);
        });
    });
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
