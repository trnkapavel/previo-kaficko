<?php
// Debug mode - změňte na false v produkci
$debug = false;

// 1. Načtení dat
$json_data = file_get_contents('data.json');
$data = json_decode($json_data, true);

// Debug info
if ($debug) {
    echo "<!-- DEBUG: JSON načten, velikost: " . strlen($json_data) . " bytů -->";
    echo "<!-- DEBUG: Data dekódována: " . (is_array($data) ? 'ANO' : 'NE') . " -->";
}

// Fallback (výchozí data pro jistotu)
if (!$data) {
    if ($debug) echo "<!-- DEBUG: Použita fallback data -->";
    $data = [
        'city' => 'Liberec', 'date' => '24. března 2026', 'time' => '09:30',
        'venue' => 'Pytloun Grand Hotel Imperial', 'capacity' => 50, 'registered' => 10,
        'promo_title' => 'Jarní roadshow: Restartujte své ubytování.',
        'promo_text' => 'Přicházíme do vašeho regionu...',
        'program_connect' => [], 'program_prolite' => []
    ];
}

// 2. Výpočet kapacity
$percent = 0;
if ($data['capacity'] > 0) {
    $percent = round(($data['registered'] / $data['capacity']) * 100);
    if ($percent > 100) $percent = 100;
}
$free_spots = max(0, $data['capacity'] - $data['registered']);
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previo MeetUp | <?= htmlspecialchars($data['city']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg: #ffffff;
            --color-section: #f5f7fa;
            --color-text: #1f2937;
            --color-border: #e5e7eb;
            --color-primary: #B50000;
            --color-primary-dark: #900000;

            --primary: var(--color-primary);
            --primary-dark: var(--color-primary-dark);
            --white: var(--color-bg);
            --dark: #111111;
            --gray-light: var(--color-section);
            --text-main: var(--color-text);

            --radius-sm: 12px;
            --radius-md: 18px;
            --radius-lg: 24px;
            --radius-pill: 999px;

            --space-section: 120px;
            --space-section-mobile: 88px;
            --space-card: 32px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Source Sans 3', sans-serif; color: var(--text-main); line-height: 1.7; background: var(--white); overflow-x: hidden; }
        h1, h2, h3, h4 { font-family: 'Source Sans 3', sans-serif; font-weight: 600; }

        /* --- ANIMACE --- */
        .reveal { opacity: 0; transform: translateY(30px); transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .reveal.active { opacity: 1; transform: translateY(0); }
        .stagger-1 { transition-delay: 0.2s; }
        .stagger-2 { transition-delay: 0.4s; }
        .stagger-3 { transition-delay: 0.6s; }

        nav { position: fixed; top: 0; width: 100%; z-index: 1000; padding: 15px 5%; display: flex; align-items: center; gap: 24px; background: transparent; backdrop-filter: none; border-bottom: 1px solid transparent; flex-wrap: wrap; transition: background 0.25s ease, border-color 0.25s ease; }
        nav.nav-scrolled { background: var(--primary); border-bottom-color: rgba(255,255,255,0.24); }
        .logo { display: inline-flex; align-items: center; gap: 14px; text-decoration: none; }
        .logo img { height: 50px; width: auto; display: block; }
        .logo-tagline { color: white; font-size: 1.05rem; font-weight: 600; letter-spacing: -0.01em; text-decoration: none; line-height: 1; }
        .nav-desktop { display: flex; align-items: center; gap: 22px; margin-left: auto; }
        .nav-item { position: relative; }
        .nav-link { color: white; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: 0.25s; background: transparent; border: none; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 6px; }
        .nav-link:hover { color: rgba(255,255,255,0.85); }
        .nav-link:focus-visible { outline: 2px solid var(--primary); outline-offset: 3px; border-radius: var(--radius-sm); }
        .nav-dropdown-toggle::after { content: '▾'; font-size: 0.72rem; opacity: 0.8; }
        .dropdown-menu { display: none; position: absolute; top: calc(100% + 12px); left: 0; min-width: 220px; background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 10px; box-shadow: 0 18px 34px rgba(15, 23, 42, 0.14); }
        .dropdown-menu a { display: block; color: var(--text-main); text-decoration: none; font-weight: 600; font-size: 0.95rem; padding: 10px 12px; border-radius: var(--radius-sm); }
        .dropdown-menu a:hover { background: var(--gray-light); color: var(--primary); }
        .dropdown-menu a:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        .nav-item.dropdown.open .dropdown-menu { display: block; }
        .nav-right { display: flex; align-items: center; gap: 16px; }
        .nav-cta { padding: 10px 24px; font-size: 0.95rem; box-shadow: none; height: 44px; display: inline-flex; align-items: center; justify-content: center; }
        .nav-login { color: white; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: 0.25s; padding: 10px 20px; border: 1px solid white; border-radius: 3px; height: 44px; display: inline-flex; align-items: center; justify-content: center; }
        .nav-login:hover { color: rgba(255,255,255,0.85); background: rgba(255,255,255,0.1); }

        .menu-toggle { display: none; margin-left: auto; background: transparent; border: 1px solid rgba(255,255,255,0.45); color: white; border-radius: 3px; width: 44px; height: 40px; font-size: 1.1rem; cursor: pointer; }
        .mobile-menu { display: none; width: 100%; background: #ffffff; border: 1px solid var(--color-border); border-radius: 3px; padding: 14px; }
        .mobile-menu.open { display: block; }
        .mobile-menu a { color: var(--text-main); text-decoration: none; font-weight: 600; display: block; padding: 10px 12px; border-radius: 3px; }
        .mobile-menu a:hover { background: var(--gray-light); }
        .mobile-accordion-toggle { width: 100%; text-align: left; background: transparent; border: none; color: var(--text-main); font-weight: 600; font-size: 0.98rem; padding: 10px 12px; border-radius: 3px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .mobile-accordion-toggle::after { content: '▾'; font-size: 0.72rem; transition: transform 0.2s ease; }
        .mobile-accordion-toggle[aria-expanded="true"]::after { transform: rotate(180deg); }
        .mobile-accordion-toggle:hover { background: var(--gray-light); }
        .mobile-accordion-panel { padding: 4px 0 8px 14px; }
        .mobile-menu .btn-main { width: 100%; text-align: center; margin-top: 10px; }
        .mobile-menu .nav-login { display: block; text-align: center; padding: 12px; }

        .hero { min-height: 100vh; display: flex; align-items: center; position: relative; color: white; padding: 130px 5% 110px; overflow: hidden; }
        .hero-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; }
        .slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; opacity: 0; transition: opacity 1.2s ease-in-out; transform: scale(1.02); }
        .slide.active { opacity: 1; }
        .hero-bg::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(130deg, rgba(17, 17, 17, 0.28) 0%, rgba(17, 17, 17, 0.18) 45%, rgba(17, 17, 17, 0.36) 100%); z-index: 1; }
        .hero-content { max-width: 860px; z-index: 2; position: relative; }
        .hero h1 { font-size: clamp(3.4rem, 8vw, 6.2rem); font-weight: 600; line-height: 1.02; margin-bottom: 24px; letter-spacing: -0.02em; }
        .hero p { font-size: clamp(1.15rem, 2.2vw, 1.5rem); max-width: 700px; margin-bottom: 36px; opacity: 0.96; font-weight: 400; line-height: 1.55; }
        .hero-actions { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .btn-secondary { color: white; border: 1px solid rgba(255,255,255,0.55); padding: 15px 30px; border-radius: 3px; text-decoration: none; font-weight: 600; font-size: 1.02rem; background: rgba(255,255,255,0.06); transition: all 0.25s ease; }
        .btn-secondary:hover { background: rgba(255,255,255,0.14); border-color: rgba(255,255,255,0.8); }

        .btn-main { background: #5aa5d7; color: white; padding: 16px 34px; border-radius: 3px; cursor: pointer; border: 1px solid transparent; text-decoration: none; font-weight: 700; font-size: 1.05rem; transition: all 0.25s ease; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 10px 24px rgba(90, 165, 215, 0.24); }
        .btn-main:hover { transform: translateY(-2px); box-shadow: 0 16px 30px rgba(90, 165, 215, 0.32); background: #4a95c7; }

        .stats-section { background: var(--gray-light); color: var(--text-main); padding: 100px 5%; position: relative; z-index: 10; box-shadow: none; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); }
        .stats-intro { text-align: center; margin-bottom: 60px; font-size: 1.5rem; font-weight: 300; opacity: 0.8; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 50px; text-align: center; max-width: 1200px; margin: 0 auto; }
        .stat-number { font-size: 3.5rem; font-weight: 700; color: var(--primary); line-height: 1; display: block; margin-bottom: 10px; }
        .stat-label { font-size: 1.1rem; font-weight: 600; letter-spacing: 1px; }

        section { padding: var(--space-section) 5%; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section-tag { color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 3px; display: block; margin-bottom: 20px; text-align: center; font-size: 0.9rem; }
        .section-title { font-size: 3rem; text-align: center; margin-bottom: 80px; }

        .intro-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 60px; margin-bottom: 100px; }
        .intro-item h3 { font-size: 1.8rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; line-height: 1.0; }
        .benefit-stack { position: relative; }
        .benefit-large-block { display: flex; align-items: center; gap: 64px; margin-bottom: 32px; background: var(--white); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 34px; }
        .benefit-card { position: sticky; top: 110px; z-index: 1; transition: transform 0.35s ease, box-shadow 0.35s ease, opacity 0.35s ease; }
        .benefit-card:nth-child(1) { z-index: 1; }
        .benefit-card:nth-child(2) { z-index: 2; }
        .benefit-card:nth-child(3) { z-index: 3; }
        .benefit-card.is-active { transform: translateY(0) scale(1); box-shadow: 0 20px 44px rgba(15, 23, 42, 0.12); opacity: 1; }
        .benefit-card:not(.is-active) { transform: translateY(6px) scale(0.985); opacity: 0.95; }
        .benefit-large-block.benefit-soft { background: var(--gray-light); }
        .benefit-image { flex: 1; height: 380px; min-height: 280px; background-color: #eef2f7; border-radius: var(--radius-md); border: 1px solid var(--color-border); background-size: cover; background-position: center; box-shadow: 0 18px 32px rgba(15, 23, 42, 0.06); transition: 0.3s ease; }
        .benefit-image.revenue { background-image: url('img/rust-trzeb.jpg'); }
        .benefit-image.legislation { background-image: url('img/legislativa-eturista.jpg'); }
        .benefit-image.ai { background-image: url('img/ai-automatizace.jpg'); }
        .benefit-image.revenue { background-position: center 42%; }
        .benefit-image.legislation { background-position: center 35%; }
        .benefit-image.ai { background-position: center 38%; }
        .benefit-large-block:hover .benefit-image { transform: translateY(-2px); box-shadow: 0 24px 44px rgba(15, 23, 42, 0.1); }
        .benefit-text { flex: 1; }
        .benefit-text h3 { font-size: 2.5rem; margin-bottom: 25px; line-height: 1.0; }
        .benefit-text ul { list-style: none; margin-top: 30px; }
        .benefit-text li { margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-weight: 600; }
        .benefit-text li::before { content: '✓'; color: var(--primary); font-weight: 900; }

        .hw-block { display: flex; background: #f8fafc; color: var(--text-main); border-radius: var(--radius-lg); overflow: hidden; margin-top: 100px; border: 1px solid var(--color-border); box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08); }
        .hw-text { padding: 80px; flex: 1; }
        .hw-image { flex: 0.5; min-height: 350px; background: url('img/chytre-zamky.png') center/contain no-repeat; }

        .tab-nav { display: flex; justify-content: center; gap: 20px; margin-bottom: 70px; flex-wrap: wrap; }
        .tab-btn { padding: 14px 28px; border-radius: 3px; border: 1px solid var(--color-border); background: white; font-weight: 700; cursor: pointer; transition: 0.25s ease; font-size: 1.05rem; font-family: inherit; }
        .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 10px 30px rgba(181,0,0,0.3); }
        .tab-content { display: none; max-width: 900px; margin: 0 auto; }
        .tab-content.active { display: block; animation: fadeIn 0.5s forwards; }
        .program-item { display: flex; padding: 35px 0; border-bottom: 1px solid var(--color-border); align-items: flex-start; transition: 0.25s ease; }
        .program-item:hover { background: var(--gray-light); padding-left: 20px; border-color: var(--primary); }
        .time { min-width: 150px; font-weight: 700; color: var(--primary); font-size: 1.3rem; font-family: inherit; }
        .program-desc h4 { font-size: 1.4rem; margin-bottom: 10px; }
        .program-desc p { color: #555; }

        .teaser-section { background: #f8fafc; color: var(--text-main); padding: 120px 5%; overflow: hidden; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); }
        .teaser-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 80px; align-items: center; max-width: 1200px; margin: 0 auto; }
        .teaser-map { height: 500px; background: #f1f5f9; border-radius: var(--radius-lg); position: relative; border: 1px solid var(--color-border); overflow: hidden; display: flex; align-items: center; justify-content: center; }
        .map-overlay { position: absolute; width: 100%; height: 100%; background: url('img/mapa.jpg') center/cover; filter: grayscale(0.1) brightness(0.92); opacity: 0.9; transition: 1s; }
        .teaser-map:hover .map-overlay { transform: scale(1.1); opacity: 0.8; }
        .pulse-point { width: 30px; height: 30px; background: var(--primary); border-radius: 50%; position: relative; z-index: 2; animation: pulse 2s infinite; }

        .speaker-profile { display: grid; grid-template-columns: 350px 1fr; gap: 50px; margin-bottom: 60px; background: white; border: 1px solid var(--color-border); border-radius: var(--radius-lg); overflow: hidden; transition: 0.3s ease; }
        .speaker-profile:hover { box-shadow: 0 40px 80px rgba(0,0,0,0.08); transform: translateY(-10px); border-color: var(--primary); }
        .speaker-photo { background-size: cover; background-position: center top; min-height: 400px; position: relative; }
        .speaker-info { padding: 60px; display: flex; flex-direction: column; justify-content: center; }
        .speaker-role { color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; display: block; }
        .speaker-bio { margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--color-border); color: #666; }

        .reg-section { background: #f8fafc; color: var(--text-main); text-align: center; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); }
        .process-steps { display: flex; justify-content: space-between; max-width: 900px; margin: 80px auto; position: relative; }
        .step { text-align: center; position: relative; z-index: 2; flex: 1; padding: 0 20px; }
        .step-icon { width: 100px; height: 100px; background: #ffffff; border: 2px solid var(--color-border); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 30px; color: var(--primary); transition: 0.4s; font-family: inherit; font-weight: 700; }
        .step:hover .step-icon { border-color: var(--primary); background: var(--primary); color: white; transform: scale(1.1); }
        .process-line { position: absolute; top: 50px; left: 10%; width: 80%; height: 2px; background: var(--color-border); z-index: 1; }

        .reg-form-container { background: #ffffff; padding: 60px; border-radius: var(--radius-lg); max-width: 800px; margin: 0 auto; border: 1px solid var(--color-border); text-align: left; box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06); }
        .reg-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .reg-form-container input, .reg-form-container select, .reg-form-container textarea { width: 100%; padding: 20px; border-radius: 3px; border: 1px solid var(--color-border); background: #ffffff; color: var(--text-main); font-size: 1rem; transition: 0.3s; font-family: inherit; }
        .reg-form-container input::placeholder, .reg-form-container textarea::placeholder { color: #9ca3af; }
        .reg-form-container input:focus, .reg-form-container select:focus, .reg-form-container textarea:focus { outline: none; border-color: var(--primary); background: #ffffff; }
        .reg-form-container select option { background: #ffffff; color: var(--text-main); }
        .full-width { grid-column: span 2; }

        .faq-container { max-width: 800px; margin: 0 auto; }
        .faq-item { border-bottom: 1px solid var(--color-border); padding: 30px 0; }
        .faq-question { font-weight: 700; font-size: 1.3rem; margin-bottom: 15px; display: block; font-family: inherit; }
        .faq-answer { color: #555; font-size: 1.1rem; }

        .newsletter-section { background: var(--primary); color: white; text-align: center; padding: 100px 5%; }
        .newsletter-form { display: flex; gap: 15px; justify-content: center; max-width: 600px; margin: 40px auto 0; }
        .newsletter-input { padding: 16px 24px; border-radius: var(--radius-pill); border: 1px solid var(--color-border); flex: 1; font-size: 1.05rem; outline: none; }
        .btn-dark { background: var(--dark); color: white; padding: 16px 30px; border-radius: 3px; border: 1px solid transparent; font-weight: 700; cursor: pointer; transition: all 0.25s ease; font-size: 1.05rem; }
        .btn-dark:hover { background: black; transform: translateY(-2px); }

        footer { padding: 72px 5% 26px; border-top: 1px solid var(--color-border); background: #fff; }
        .footer-wrap { max-width: 1200px; margin: 0 auto; }
        .footer-top { display: grid; grid-template-columns: 1.4fr repeat(4, 1fr) 1.2fr; gap: 34px; align-items: start; }
        .footer-brand-logo { display: inline-flex; margin-bottom: 18px; }
        .footer-brand-logo img { height: 42px; width: auto; filter: brightness(0) saturate(100%); }
        .footer-company { font-weight: 700; color: var(--text-main); margin-bottom: 6px; }
        .footer-address { color: #6b7280; font-size: 0.95rem; line-height: 1.55; }
        .footer-title { font-size: 0.95rem; font-weight: 700; margin-bottom: 14px; color: var(--text-main); }
        .footer-col ul { list-style: none; }
        .footer-col li { margin-bottom: 9px; }
        .footer-col a { text-decoration: none; color: #4b5563; font-size: 0.95rem; }
        .footer-col a:hover { color: var(--primary); }
        .footer-contact { border-left: 1px solid var(--color-border); padding-left: 24px; }
        .footer-contact-item { margin-bottom: 14px; }
        .footer-contact-label { font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; color: #9ca3af; display: block; margin-bottom: 4px; }
        .footer-contact-value { color: var(--text-main); font-weight: 600; text-decoration: none; }
        .footer-contact-meta { color: #6b7280; font-size: 0.9rem; margin-top: 4px; }

        .footer-partners { margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--color-border); }
        .footer-partners .footer-title { margin-bottom: 10px; font-size: 0.78rem; letter-spacing: 0.08em; text-transform: uppercase; color: #9ca3af; }
        .footer-partners-row { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; }
        .partner-link { text-decoration: none; color: #6b7280; font-size: 0.95rem; font-weight: 600; transition: color 0.2s ease, opacity 0.2s ease; opacity: 0.9; }
        .partner-link:hover { color: var(--primary); opacity: 1; }
        .partner-sep { color: #d1d5db; user-select: none; }

        .footer-bottom { margin-top: 28px; padding-top: 16px; border-top: 1px solid var(--color-border); color: #6b7280; font-size: 0.9rem; text-align: center; }

        .pill-bar { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); width: 90%; max-width: 850px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); padding: 15px 35px; border-radius: var(--radius-pill); display: flex; align-items: center; justify-content: space-between; color: var(--text-main); z-index: 10000; box-shadow: 0 10px 40px rgba(0,0,0,0.15); border: 1px solid rgba(0,0,0,0.1); }
        .progress-box { display: flex; align-items: center; gap: 20px; }
        .progress-bg { width: 150px; height: 8px; background: #e5e7eb; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--primary); border-radius: 10px; transition: width 1s ease; }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
        .modal-content { background: white; padding: 60px; border-radius: var(--radius-lg); text-align: center; max-width: 550px; width: 90%; position: relative; box-shadow: 0 40px 80px rgba(0,0,0,0.5); animation: popIn 0.4s; }
        .check-icon { width: 90px; height: 90px; background: #4BB543; color: white; font-size: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; }
        .close-modal { position: absolute; top: 25px; right: 30px; font-size: 30px; cursor: pointer; color: #999; transition: 0.3s; }
        .close-modal:hover { color: var(--primary); }
        .btn-cal { padding: 15px 25px; border-radius: 3px; text-decoration: none; font-weight: 700; font-size: 0.95rem; margin: 5px; display: inline-block; color: white; transition: 0.3s; }
        .btn-cal.google { background: #4285F4; } .btn-cal.outlook { background: #0078D4; }
        .btn-cal:hover { transform: translateY(-3px); opacity: 0.9; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(181, 0, 0, 0.7); } 70% { box-shadow: 0 0 0 30px rgba(181, 0, 0, 0); } 100% { box-shadow: 0 0 0 0 rgba(181, 0, 0, 0); } }

        @media (max-width: 992px) {
            nav { padding: 12px 5%; }
            .nav-desktop, .nav-right { display: none; }
            .menu-toggle { display: inline-flex; align-items: center; justify-content: center; }
            .hero h1 { font-size: 2.8rem; line-height: 1.1; letter-spacing: -0.01em; }
            .hero p { font-size: 1rem; line-height: 1.1; }
            .hero-actions { flex-direction: row; align-items: center; gap: 12px; }
            .section-title { font-size: 2rem; margin-bottom: 50px; line-height: 1.1; }
            .section-tag { font-size: 0.8rem; }
            .intro-item h3 { font-size: 1.3rem; line-height: 0.95; }
            .benefit-text h3 { font-size: 1.6rem; line-height: 0.95; margin-bottom: 15px; }
            .btn-main, .btn-secondary { padding: 12px 24px; font-size: 0.95rem; }
            .stats-grid { grid-template-columns: 1fr; gap: 40px; }
            .benefit-large-block, .benefit-large-block:nth-child(even) { flex-direction: column; gap: 40px; text-align: center; }
            .benefit-card { position: relative !important; top: auto !important; transform: none !important; opacity: 1 !important; box-shadow: none !important; z-index: auto !important; }
            .benefit-card:not(.is-active) { transform: none !important; }
            .benefit-image { height: 320px; width: 100%; visibility: visible !important; display: block !important; }
            .hw-block { flex-direction: column; }
            .hw-image { min-height: 300px; }
            .teaser-grid { grid-template-columns: 1fr; text-align: center; }
            .teaser-map { height: 400px; order: -1; }
            .speaker-profile { grid-template-columns: 1fr; text-align: center; }
            .process-steps { flex-direction: column; gap: 40px; }
            .process-line { display: none; }
            .reg-form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
            .newsletter-form { flex-direction: column; }
            section { padding: var(--space-section-mobile) 5%; }
            .footer-top { grid-template-columns: 1fr 1fr; gap: 26px; }
            .footer-contact { border-left: none; border-top: 1px solid var(--color-border); padding-left: 0; padding-top: 18px; }
        }
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.2rem; line-height: 1.1; }
            .benefit-text h3 { font-size: 1.4rem; line-height: 0.95; }
            .intro-item h3 { font-size: 1.15rem; line-height: 0.95; }
            .section-title { font-size: 1.6rem; line-height: 1.1; margin-bottom: 40px; }
            .benefit-card { position: static !important; transform: none !important; opacity: 1 !important; box-shadow: none !important; z-index: auto !important; }
            .benefit-card:not(.is-active) { transform: none !important; opacity: 1 !important; }
            .benefit-image { height: 280px !important; width: 100% !important; flex: 1 !important; display: block !important; visibility: visible !important; }
            .benefit-image.revenue { background-position: center 48%; }
            .benefit-image.legislation { background-position: center 40%; }
            .benefit-image.ai { background-position: center 32%; }
            .footer-top { grid-template-columns: 1fr; }
            .footer-partners-row { gap: 10px; }
            .partner-sep { display: none; }
            .pill-bar { display: none; }
        }
    </style>
</head>
<body>

    <nav>
        <a class="logo" href="#" aria-label="Previo domů">
            <img src="img/logo-previo-white.svg" alt="Previo">
            <span class="logo-tagline">Více hostů. Méně starostí.</span>
        </a>

        <div class="nav-desktop" aria-label="Hlavní navigace">
            <div class="nav-item dropdown">
                <button class="nav-link nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="dropdown-produkty">Produkty</button>
                <div id="dropdown-produkty" class="dropdown-menu">
                    <a href="#program">Program</a>
                    <a href="#proc">Roadshow</a>
                    <a href="#registrace">Registrace</a>
                </div>
            </div>

            <a class="nav-link" href="#">Reference</a>
            <a class="nav-link" href="#">Ceník</a>

            <div class="nav-item dropdown">
                <button class="nav-link nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="dropdown-onas">O nás</button>
                <div id="dropdown-onas" class="dropdown-menu">
                    <a href="#proc">O akci</a>
                    <a href="#lektori">Řečníci</a>
                    <a href="#lokalita">Kde to bude</a>
                </div>
            </div>

            <div class="nav-item dropdown">
                <button class="nav-link nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="dropdown-akademie">Akademie</button>
                <div id="dropdown-akademie" class="dropdown-menu">
                    <a href="#">Kurzy</a>
                    <a href="#">Webináře</a>
                    <a href="#">Materiály</a>
                </div>
            </div>

            <a class="nav-link" href="#registrace">Kontakty</a>
            <a class="nav-link" href="#">Blog</a>
        </div>

        <div class="nav-right">
            <a href="#registrace" class="btn-main nav-cta">Registrovat se</a>
            <a href="#" class="nav-login">Přihlásit</a>
        </div>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobileMenu" aria-label="Otevřít menu">☰</button>

        <div id="mobileMenu" class="mobile-menu" hidden>
            <div class="mobile-accordion-item">
                <button class="mobile-accordion-toggle" type="button" aria-expanded="false" aria-controls="mobile-produkty">Produkty</button>
                <div id="mobile-produkty" class="mobile-accordion-panel" hidden>
                    <a href="#program">Program</a>
                    <a href="#proc">Roadshow</a>
                    <a href="#registrace">Registrace</a>
                </div>
            </div>
            <a href="#">Reference</a>
            <a href="#">Ceník</a>

            <div class="mobile-accordion-item">
                <button class="mobile-accordion-toggle" type="button" aria-expanded="false" aria-controls="mobile-onas">O nás</button>
                <div id="mobile-onas" class="mobile-accordion-panel" hidden>
                    <a href="#proc">O akci</a>
                    <a href="#lektori">Řečníci</a>
                    <a href="#lokalita">Kde to bude</a>
                </div>
            </div>

            <div class="mobile-accordion-item">
                <button class="mobile-accordion-toggle" type="button" aria-expanded="false" aria-controls="mobile-akademie">Akademie</button>
                <div id="mobile-akademie" class="mobile-accordion-panel" hidden>
                    <a href="#">Kurzy</a>
                    <a href="#">Webináře</a>
                    <a href="#">Materiály</a>
                </div>
            </div>

            <a href="#registrace">Kontakty</a>
            <a href="#">Blog</a>
            <a href="#registrace" class="btn-main nav-cta">Registrovat se</a>
            <a href="#" class="nav-login">Přihlásit</a>
        </div>
    </nav>

    <header class="hero">
        <div class="hero-bg">
            <div class="slide active" style="background-image: url('img/cover1.jpg');"></div>
            <div class="slide" style="background-image: url('img/cover2.jpg');"></div>
            <div class="slide" style="background-image: url('img/cover3.jpg');"></div>
        </div>

        <div class="hero-content reveal">
            <h1><?= htmlspecialchars($data['promo_title']) ?></h1>
            <p><?= htmlspecialchars($data['promo_text']) ?></p>
            <div class="hero-actions">
                <a href="#registrace" class="btn-main" style="background: var(--primary); box-shadow: 0 10px 24px rgba(181, 0, 0, 0.24);">Rezervovat místo</a>
                <a href="#program" class="btn-secondary">Zobrazit program</a>
            </div>
        </div>
    </header>

    <div class="stats-section reveal">
        <p class="stats-intro">Tvoříme největší komunitu hoteliérů v Česku. Přidejte se.</p>
        <div class="stats-grid">
            <div class="reveal stagger-1"><span class="stat-number">120+</span><span class="stat-label">Zastávek za námi</span></div>
            <div class="reveal stagger-2"><span class="stat-number">1 500+</span><span class="stat-label">Vypitých káv s klienty ☕</span></div>
            <div class="reveal stagger-3"><span class="stat-number">98 %</span><span class="stat-label">Účastníků akci doporučuje</span></div>
        </div>
    </div>

    <section id="proc" class="container">
        <span class="section-tag reveal">Obsah akce</span>
        <h2 class="section-title reveal">Praktická témata pro růst ubytování</h2>

        <div class="intro-grid reveal stagger-1">
            <div class="intro-item"><h3>🚀 Pro koho je akce určena?</h3><p>Pro majitele, provozní a recepční hotelů i penzionů. Ať už Previo používáte (odpolední blok), nebo teprve hledáte inspiraci (dopolední blok).</p></div>
            <div class="intro-item"><h3>💡 Hlavní témata</h3><p>E-Turista bez stresu, Revenue Management v praxi, psychologie hosta na mobilu a reálné využití AI v hotelnictví.</p></div>
        </div>

        <div class="benefit-stack">
            <div class="benefit-large-block benefit-card reveal">
                <div class="benefit-text">
                    <h3>Růst tržeb & Práce s cenou</h3>
                    <p>Ukážeme vám, jak reagovat na lokální festivaly a události. Naučíme vás pracovat s minimální délkou pobytu a dynamickou cenotvorbou.</p>
                    <ul><li>Lokální statistiky a průměrné ceny v regionu.</li><li>Strategie pro zvýšení přímých rezervací.</li></ul>
                </div>
                <div class="benefit-image revenue" aria-hidden="true"></div>
            </div>

            <div class="benefit-large-block benefit-card benefit-soft reveal">
                <div class="benefit-text">
                    <h3>Legislativa bez vrásek (e-Turista)</h3>
                    <p>Legislativní změny mohou být náročné. Ukážeme vám e-Turistu jako příležitost k digitalizaci.</p>
                    <ul><li>Aktuální briefing: Co musíte splnit.</li><li>Automatizace hlášení: Jak to systém vyřeší za vás.</li></ul>
                </div>
                <div class="benefit-image legislation" aria-hidden="true"></div>
            </div>
 
            <div class="benefit-large-block benefit-card reveal">
                <div class="benefit-text">
                    <h3>Budoucnost s AI a automatizací</h3>
                    <p>Automatizujte rutinní agendu. Cesta hosta začíná na mobilu – ukážeme si, jak efektivně využít AI nástroje (ChatGPT, Ideogram).</p>
                    <ul><li>Psaní textů a reakcí na recenze pomocí AI.</li><li>Cesta moderního hosta: Od vyhledávání po check-out.</li></ul>
                </div>
                <div class="benefit-image ai" aria-hidden="true"></div>
            </div>
        </div>

        <div class="hw-block reveal">
            <div class="hw-text">
                <span style="color: var(--primary); font-weight: 800; text-transform: uppercase;">Vyzkoušejte naživo</span>
                <h3 style="font-size: 2.5rem; font-family: 'Source Sans 3'; margin: 15px 0;">Automatizace v praxi</h3>
                <p style="margin-bottom: 30px;">Součástí programu jsou praktické ukázky fungování <strong>chytrých klik a samoobslužných kiosků</strong>.</p>
                <ul style="list-style: none;"><li>✓ Integrace zámkových systémů</li><li>✓ Check-in proces bez recepčního</li></ul>
            </div>
            <div class="hw-image"></div>
        </div>
    </section>

    <section id="program" style="background: var(--gray-light);">
        <div class="container">
            <span class="section-tag reveal">Harmonogram</span>
            <h2 class="section-title reveal">Program nabitý praxí</h2>
            
            <div class="tab-nav reveal stagger-1">
                <button class="tab-btn active" onclick="openTab(event, 'connect')">Dopoledne: Connect (pro neklienty)</button>
                <button class="tab-btn" onclick="openTab(event, 'prolite')">Odpoledne: PRO/LITE (pro klienty)</button>
            </div>

            <div id="connect" class="tab-content active reveal">
                <?php foreach($data['program_connect'] as $item): ?>
                <div class="program-item">
                    <div class="time"><?= htmlspecialchars($item['time']) ?></div>
                    <div class="program-desc">
                        <h4><?= htmlspecialchars($item['title']) ?></h4>
                        <p><?= htmlspecialchars($item['desc']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div id="prolite" class="tab-content reveal">
                <?php foreach($data['program_prolite'] as $item): ?>
                <div class="program-item">
                    <div class="time"><?= htmlspecialchars($item['time']) ?></div>
                    <div class="program-desc">
                        <h4><?= htmlspecialchars($item['title']) ?></h4>
                        <p><?= htmlspecialchars($item['desc']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="lokalita" class="teaser-section">
        <div class="teaser-grid reveal">
            <div class="teaser-text">
                <span class="section-tag" style="text-align: left;">Aktuální zastávka</span>
                <h2 style="font-size: 3rem; font-family: 'Source Sans 3'; margin-bottom: 30px;">Místo konání: <br><span style="color: var(--primary);"><?= htmlspecialchars($data['city']) ?></span></h2>
                <p style="font-size: 1.2rem; opacity: 0.8; margin-bottom: 40px;">Akce se koná v hotelu <?= htmlspecialchars($data['venue']) ?>.</p>
                <div style="background: rgba(255,255,255,0.05); padding: 40px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); display: inline-block;">
                    <h4 style="margin-bottom: 10px; opacity: 0.7;">Zapište si termín:</h4>
                    <p style="font-size: 2.2rem; font-family: 'Source Sans 3'; font-weight: 700; margin: 0;"><?= htmlspecialchars($data['date']) ?></p>
                </div>
            </div>
            <div class="teaser-map reveal stagger-1">
                <div class="map-overlay"></div>
                <div style="position: relative; z-index: 2; text-align: center; color: white;">
                    <div class="pulse-point" style="margin: 0 auto 20px;"></div>
                    <h3 style="font-size: 2rem;"><?= htmlspecialchars($data['city']) ?></h3>
                    <p style="font-size: 1rem; opacity: 0.7;">Termín: <?= htmlspecialchars($data['date']) ?></p>
                </div>
            </div>
        </div>
    </section>

    <section id="lektori" class="container">
        <span class="section-tag reveal">Řečníci</span>
        <h2 class="section-title reveal">Experti s praxí v oboru</h2>
        <div class="speaker-profile reveal">
            <div class="speaker-photo" style="background-image: url('img/jiri-sindelar.jpeg');"></div>
            <div class="speaker-info">
                <span class="speaker-role">Strategie & Trendy</span>
                <h3 style="font-family: 'Source Sans 3'; font-size: 2rem;">Jiří Šindelář</h3>
                <p class="speaker-bio">Head of Growth. Provází ranní částí. Jiří má přes 10 let praxe v digitalizaci ubytování.</p>
            </div>
        </div>
        <div class="speaker-profile reveal stagger-1">
            <div class="speaker-photo" style="background-image: url('img/petr-mares.jpg');"></div>
            <div class="speaker-info">
                <span class="speaker-role">Inovace & Automatizace</span>
                <h3 style="font-family: 'Source Sans 3'; font-size: 2rem;">Petr Mareš</h3>
                <p class="speaker-bio">Odpoledne prezentuje novinky. Viziconář v oblasti zámkových systémů a integrace AI.</p>
            </div>
        </div>
        <div class="speaker-profile reveal stagger-2">
            <div class="speaker-photo" style="background-image: url('img/jana-vlkova.jpg');"></div>
            <div class="speaker-info">
                <span class="speaker-role">Konzultace & Workshopy</span>
                <h3 style="font-family: 'Source Sans 3'; font-size: 2rem;">Jana V.</h3>
                <p class="speaker-bio">K dispozici na odpolední individuální konzultace. Řeší konkrétní problémy v nastavení Previa.</p>
            </div>
        </div>
    </section>

    <section id="registrace" class="reg-section">
        <div class="container reveal">
            <span class="section-tag" style="color: #6b7280;">Jak to funguje?</span>
            <h2 class="section-title" style="color: var(--text-main);">Zajistěte si místo v <?= htmlspecialchars($data['city']) ?></h2>
            
            <div class="process-steps reveal stagger-1">
                <div class="process-line"></div>
                <div class="step"><div class="step-icon">1</div><h4 style="font-size: 1.2rem;">Vyplnění přihlášky</h4><p style="opacity: 0.7;">Vyplnění trvá přibližně jednu minutu.</p></div>
                <div class="step"><div class="step-icon">2</div><h4 style="font-size: 1.2rem;">Potvrzovací e-mail</h4><p style="opacity: 0.7;">Potvrzení obdržíte e-mailem.</p></div>
                <div class="step"><div class="step-icon">3</div><h4 style="font-size: 1.2rem;">Uložení do kalendáře</h4><p style="opacity: 0.7;">Pro snadné připomenutí termínu.</p></div>
            </div>

            <div class="reg-form-container reveal stagger-2">
                <h3 style="font-size: 1.8rem; margin-bottom: 10px; text-align: center;">Registrační formulář</h3>
                <p style="text-align: center; margin-bottom: 30px; opacity: 0.7;">Kapacita je omezena. Vyplňte prosím pečlivě všechny údaje.</p>
                
                <form id="regForm" action="process_registration.php" method="POST">
                    <input type="hidden" name="event_details" value="<?= htmlspecialchars($data['city']) . ' (' . htmlspecialchars($data['date']) . ')' ?>">

                    <div class="reg-form-grid">
                        <input type="text" name="name" placeholder="Jméno a příjmení" required>
                        <input type="text" name="hotel" placeholder="Název ubytování" required>
                        <input type="email" name="email" placeholder="E-mail" required>
                        <input type="tel" name="phone" placeholder="Telefon (pro SMS připomínku)">
                        <select name="type" required class="full-width">
                            <option value="">Vyberte typ účasti</option>
                            <option value="connect">Dopoledne: Connect (Nejsem klient Previa)</option>
                            <option value="prolite">Odpoledne: PRO/LITE (Jsem klient Previa)</option>
                            <option value="both">Celý den</option>
                        </select>
                        <input type="text" name="diet" placeholder="Dietní omezení (např. bezlepek)" class="full-width">
                        <textarea name="question" rows="3" placeholder="Vaše dotazy nebo témata, která chcete na akci řešit..." class="full-width"></textarea>
                        
                        <button type="submit" class="btn-main full-width" style="margin-top: 10px; background: var(--primary); box-shadow: 0 10px 24px rgba(181, 0, 0, 0.24);">Odeslat závaznou registraci</button>
                    </div>
                </form>
            </div>

        </div>
    </section>

    <section class="container reveal">
        <div class="faq-container">
            <span class="section-tag">Vše, co potřebujete vědět</span>
            <h2 class="section-title" style="margin-bottom: 50px;">Časté dotazy</h2>
            <div class="faq-item"><span class="faq-question">Bude z akce záznam?</span><p class="faq-answer">Akce probíhá prezenčně. Videozáznam neplánujeme, po akci však zašleme materiály.</p></div>
            <div class="faq-item"><span class="faq-question">Mohu vzít kolegu?</span><p class="faq-answer">Ano. Každou osobu prosíme zaregistrovat samostatně z důvodu kapacity.</p></div>
            <div class="faq-item"><span class="faq-question">Kolik stojí vstupenka?</span><p class="faq-answer">Pro všechny registrované účastníky je vstup <strong>zdarma</strong>.</p></div>
        </div>
    </section>

    <div class="newsletter-section">
        <div class="container reveal">
             <h2 style="font-family: 'Source Sans 3'; font-size: 2.5rem; margin-bottom: 20px;">Chcete dostávat novinky z oboru?</h2>
             <form class="newsletter-form">
                 <input type="email" placeholder="Váš e-mail" class="newsletter-input" required>
                 <button type="submit" class="btn-dark">Přihlásit odběr</button>
             </form>
        </div>
    </div>

    <footer>
        <div class="footer-wrap">
            <div class="footer-top">
                <div class="footer-brand">
                    <a href="#" class="footer-brand-logo" aria-label="Previo domů">
                        <img src="img/logo-previo-white.svg" alt="Previo">
                    </a>
                    <p class="footer-company">PREVIO s.r.o.</p>
                    <p class="footer-address">Milady Horákové 13<br>602 00 Brno<br>Česká republika</p>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title">Užitečné odkazy</h4>
                    <ul>
                        <li><a href="#program">Program</a></li>
                        <li><a href="#lokalita">Kde to bude</a></li>
                        <li><a href="#registrace">Registrace</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title">Produkty</h4>
                    <ul>
                        <li><a href="#">Hotelový systém</a></li>
                        <li><a href="#">Channel Manager</a></li>
                        <li><a href="#">Booking Engine</a></li>
                        <li><a href="#">Integrace</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title">Ceny</h4>
                    <ul>
                        <li><a href="#">Ceník řešení</a></li>
                        <li><a href="#">Demo ukázka</a></li>
                        <li><a href="#">Časté dotazy</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4 class="footer-title">O nás</h4>
                    <ul>
                        <li><a href="#proc">Naše mise</a></li>
                        <li><a href="#lektori">Tým expertů</a></li>
                        <li><a href="#">Kariéra</a></li>
                    </ul>
                </div>

                <div class="footer-contact">
                    <h4 class="footer-title">Kontakt</h4>
                    <div class="footer-contact-item">
                        <span class="footer-contact-label">Klientská linka</span>
                        <a class="footer-contact-value" href="tel:+420530331500">+420 530 331 500</a>
                        <p class="footer-contact-meta">Po–Pá: 8:00–17:00</p>
                    </div>
                    <div class="footer-contact-item">
                        <span class="footer-contact-label">E-mail</span>
                        <a class="footer-contact-value" href="mailto:info@previo.cz">info@previo.cz</a>
                    </div>
                </div>
            </div>

            <div class="footer-partners">
                <h4 class="footer-title">Hlavní partneři</h4>
                <div class="footer-partners-row">
                    <a href="https://www.hotel.cz" target="_blank" rel="noopener noreferrer" class="partner-link">Hotel.cz</a>
                    <span class="partner-sep">•</span>
                    <a href="https://www.spa.cz" target="_blank" rel="noopener noreferrer" class="partner-link">Spa.cz</a>
                    <span class="partner-sep">•</span>
                    <a href="https://www.penzion.cz" target="_blank" rel="noopener noreferrer" class="partner-link">Penzion.cz</a>
                    <span class="partner-sep">•</span>
                    <a href="https://www.hotely.cz" target="_blank" rel="noopener noreferrer" class="partner-link">Hotely.cz</a>
                    <span class="partner-sep">•</span>
                    <a href="https://www.slevomat.cz" target="_blank" rel="noopener noreferrer" class="partner-link">Slevomat</a>
                </div>
            </div>

            <div class="footer-bottom">© 2026 Previo. Všechna práva vyhrazena.</div>
        </div>
    </footer>

    <div class="pill-bar">
        <div>
            <span style="font-size: 0.75rem; font-weight: 800; color: var(--primary); letter-spacing: 1px;">AKTUÁLNĚ</span>
            <strong style="display: block; font-size: 1.1rem; color: var(--text-main);"><?= htmlspecialchars($data['city']) ?> (<?= htmlspecialchars($data['date']) ?>)</strong>
        </div>
        <div class="progress-box">
            <span style="font-size: 0.9rem; font-weight: 600; color: #555;">Zbývá <?= $free_spots ?> míst</span>
            <div class="progress-bg"><div class="progress-fill" style="width: <?= $percent ?>%;"></div></div>
            <a href="#registrace" class="btn-main" style="padding: 12px 30px; font-size: 1rem; background: var(--primary); box-shadow: 0 10px 24px rgba(181, 0, 0, 0.24);">Registrovat</a>
        </div>
    </div>

    <div id="successModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div class="check-icon">✓</div>
            <h2 style="color: #333;">Registrace přijata!</h2>
            <p style="margin: 20px 0; color: #666;">Potvrzení a detaily jsme vám odeslali na e-mail.</p>
            <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; margin: 25px 0;">
                <p style="font-weight: 700; margin-bottom: 15px; color: var(--primary);">Uložte si termín do kalendáře:</p>
                <div>
                    <a href="https://calendar.google.com/calendar/render?action=TEMPLATE&text=Previo+Roadshow&dates=20260412T093000Z/20260412T150000Z&details=Těšíme+se+na+vás.&location=<?= urlencode($data['city']) ?>" target="_blank" class="btn-cal google">Google Kalendář</a>
                    <a href="download_ics.php" class="btn-cal outlook">Outlook / iCal</a>
                </div>
            </div>
            <button onclick="closeModal()" class="btn-main" style="width: 100%;">Zavřít okno</button>
        </div>
    </div>

    <script>
        // Hero Slider Script
        const slides = document.querySelectorAll('.slide');
        if (slides.length > 1) {
            let currentSlide = 0;
            function nextSlide() {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }
            setInterval(nextSlide, 5000);
        }

        // Scroll Reveal
        function revealAnimations() {
            document.querySelectorAll('.reveal').forEach(el => {
                if(el.getBoundingClientRect().top < window.innerHeight - 120) el.classList.add('active');
            });
        }
        window.addEventListener('scroll', revealAnimations);
        revealAnimations();

        // Navbar color on scroll
        const mainNav = document.querySelector('nav');
        function updateNavOnScroll() {
            if (!mainNav) return;
            mainNav.classList.toggle('nav-scrolled', window.scrollY > 24);
        }
        window.addEventListener('scroll', updateNavOnScroll);
        updateNavOnScroll();

        // Sticky benefit cards focus animation
        const benefitCards = [...document.querySelectorAll('.benefit-card')];
        if (benefitCards.length) {
            const cardObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        benefitCards.forEach(card => card.classList.remove('is-active'));
                        entry.target.classList.add('is-active');
                    }
                });
            }, { threshold: 0.55 });

            benefitCards.forEach((card, index) => {
                if (index === 0) card.classList.add('is-active');
                cardObserver.observe(card);
            });
        }

        // Tabs
        function openTab(evt, tabName) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            evt.currentTarget.classList.add('active');
        }

        // Form Submit
        document.getElementById('regForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button');
            const originalText = btn.innerText;
            btn.innerText = "Odesílám data..."; btn.disabled = true;
            const formData = new FormData(e.target);
            fetch('process_registration.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        document.getElementById('successModal').style.display = 'flex';
                        e.target.reset();
                    } else { alert('Chyba: ' + data.message); }
                } catch(err) { alert('Chyba serveru.'); console.log(text); }
            })
            .catch(() => alert('Chyba připojení.'))
            .finally(() => { btn.innerText = originalText; btn.disabled = false; });
        });

        function closeModal() { document.getElementById('successModal').style.display = 'none'; }

        // Header dropdowns + mobile navigation
        const dropdownToggles = [...document.querySelectorAll('.nav-dropdown-toggle')];

        function getDropdownPanel(btn) {
            return document.getElementById(btn.getAttribute('aria-controls'));
        }

        function getDropdownLinks(btn) {
            const panel = getDropdownPanel(btn);
            return panel ? [...panel.querySelectorAll('a')] : [];
        }

        function setDropdownState(btn, isOpen) {
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            const panel = getDropdownPanel(btn);
            if (panel) panel.closest('.dropdown')?.classList.toggle('open', isOpen);
        }

        function closeDesktopDropdowns(exceptButton = null, returnFocus = false) {
            dropdownToggles.forEach(btn => {
                if (exceptButton && btn === exceptButton) return;
                const wasOpen = btn.getAttribute('aria-expanded') === 'true';
                setDropdownState(btn, false);
                if (returnFocus && wasOpen) btn.focus();
            });
        }

        function openDesktopDropdown(btn, focusPosition = null) {
            closeDesktopDropdowns(btn);
            setDropdownState(btn, true);
            const links = getDropdownLinks(btn);
            if (!links.length || focusPosition === null) return;
            if (focusPosition === 'first') links[0].focus();
            if (focusPosition === 'last') links[links.length - 1].focus();
        }

        dropdownToggles.forEach(btn => {
            btn.addEventListener('click', () => {
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                if (expanded) {
                    setDropdownState(btn, false);
                } else {
                    openDesktopDropdown(btn);
                }
            });

            btn.addEventListener('keydown', (event) => {
                const links = getDropdownLinks(btn);
                if (!links.length) return;

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    openDesktopDropdown(btn, 'first');
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    openDesktopDropdown(btn, 'last');
                } else if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    const expanded = btn.getAttribute('aria-expanded') === 'true';
                    if (expanded) {
                        setDropdownState(btn, false);
                    } else {
                        openDesktopDropdown(btn, 'first');
                    }
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    setDropdownState(btn, false);
                    btn.focus();
                }
            });

            const panel = getDropdownPanel(btn);
            if (!panel) return;

            panel.addEventListener('keydown', (event) => {
                const links = getDropdownLinks(btn);
                const currentIndex = links.indexOf(document.activeElement);
                if (currentIndex === -1) return;

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    links[(currentIndex + 1) % links.length].focus();
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    links[(currentIndex - 1 + links.length) % links.length].focus();
                } else if (event.key === 'Home') {
                    event.preventDefault();
                    links[0].focus();
                } else if (event.key === 'End') {
                    event.preventDefault();
                    links[links.length - 1].focus();
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    setDropdownState(btn, false);
                    btn.focus();
                }
            });

            panel.addEventListener('focusout', (event) => {
                const nextTarget = event.relatedTarget;
                if (!nextTarget || !panel.closest('.dropdown')?.contains(nextTarget)) {
                    setDropdownState(btn, false);
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.nav-item.dropdown')) closeDesktopDropdowns();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeDesktopDropdowns();
        });

        const menuToggle = document.querySelector('.menu-toggle');
        const mobileMenu = document.getElementById('mobileMenu');
        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener('click', () => {
                const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
                menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                mobileMenu.hidden = expanded;
                mobileMenu.classList.toggle('open', !expanded);
            });

            mobileMenu.querySelectorAll('.mobile-accordion-toggle').forEach(btn => {
                btn.addEventListener('click', () => {
                    const expanded = btn.getAttribute('aria-expanded') === 'true';
                    btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    const panel = document.getElementById(btn.getAttribute('aria-controls'));
                    if (panel) panel.hidden = expanded;
                });
            });

            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    menuToggle.setAttribute('aria-expanded', 'false');
                    mobileMenu.hidden = true;
                    mobileMenu.classList.remove('open');
                });
            });
        }
    </script>

</body>
</html>