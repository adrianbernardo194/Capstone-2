<?php
require_once 'session_check_resident.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Portal - Barangay San Roque</title>
    <link rel="stylesheet" href="portal-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'resident-sidebar.php'; ?>
<main class="portal-content">
    <header class="portal-header">
        <img src="logo.png" alt="Logo" class="mini-logo">
        <h1>Welcome, <?php echo htmlspecialchars($session_resident_name); ?>!</h1>
        <p>Barangay San Roque, Marikina City</p>
        <small>Office of the Sangguniang Barangay - KP Form No. 9 (SUMBONG)</small>
    </header>

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
        <a href="complaint-form.php" style="text-decoration:none;">
            <button class="action-btn"><img src="file.png" class="icon-white"> File New Complaint</button>
        </a>
        <p>* All fields marked with * are required</p>
    </div>
</main>
</body>
</html>