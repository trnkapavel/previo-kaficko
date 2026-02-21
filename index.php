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
    <link rel="stylesheet" href="style.css">
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