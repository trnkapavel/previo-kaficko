<?php
// Samostatná stránka pro dopolední akci (ráno)
$landing_slot = 'morning';
$landing_config = [
    'variant' => 'morning',
    'hero_cta_slot' => 'morning',
    'page_title_suffix' => ' | Dopolední MeetUp',
];

// případné předvyplnění typu účasti na registrační stránce
$prefill_slot = 'morning';

// Debug mode - změňte na false v produkci
$debug = false;

// 1. Načtení dat
$json_data = file_get_contents('data-rano.json');
$data = json_decode($json_data, true);

// Fallback (výchozí data pro jistotu)
if (!$data) {
    $data = [
        'city' => 'Liberec', 'date' => '24. března 2026', 'time' => '09:30',
        'venue' => 'Pytloun Grand Hotel Imperial', 'capacity' => 50, 'registered' => 10,
        'promo_title' => 'Jarní roadshow: Restartujte své ubytování.',
        'promo_text' => 'Přicházíme do vašeho regionu...',
        'program' => []
    ];
}

// 2. Ranní kontext
$landing_variant = 'morning';
$hero_cta_slot = $landing_config['hero_cta_slot'] ?? 'morning';
$page_title_suffix = $landing_config['page_title_suffix'] ?? ' | Dopolední MeetUp';

// Program: dopolední blok (Connect)
$program_single = $data['program'] ?? [];
$program_subtitle = 'Dopoledne: Connect (pro neklienty)';
$fixed_type = 'connect';

// 3. Hero texty – fallback na promo texty
$hero_title = !empty($data['hero_title']) ? $data['hero_title'] : (!empty($data['promo_title']) ? $data['promo_title'] : 'Ranní blok: Restartujte své ubytování');
$hero_text  = !empty($data['hero_text'])  ? $data['hero_text']  : (!empty($data['promo_text']) ? $data['promo_text'] : 'Pro neklienty Previa. Praktický přehled trendů, legislativy a technologií, které vám pomohou nastartovat přímé rezervace.');

// 4. Hero obrázky
$hero_images = [];
$gallery = $data['hero_images'] ?? [];
if (!is_array($gallery) && !empty($gallery)) {
    $gallery = [$gallery];
}
foreach ($gallery as $img) {
    if (strpos($img, 'img/') !== 0 && strpos($img, '/') === false) {
        $img = 'img/' . $img;
    }
    $hero_images[] = $img;
}
if (!$hero_images) {
    $hero_images = ['img/cover1.jpg', 'img/cover2.jpg', 'img/cover3.jpg'];
}

// 5. Základní údaje pro titulky a pill‑bar
$city  = $data['city']  ?? '';
$date  = $data['date']  ?? '';
$venue = $data['venue'] ?? '';
$capacity   = (int)($data['capacity']   ?? 0);
$registered = (int)($data['registered'] ?? 0);
$percent = $capacity > 0 ? min(100, round($registered / $capacity * 100)) : 0;
$free_spots = max(0, $capacity - $registered);

// Slot pro přesměrování na /registrace
$slot_param = $landing_slot ?? $hero_cta_slot;

$locations = isset($data['locations']) && is_array($data['locations']) ? $data['locations'] : (isset($data['city']) ? [$data['city']] : []);
$stops = $data['stops'] ?? [];
if (empty($stops) && !empty($data['city'])) {
    $stops = [['date' => $data['date'] ?? '', 'city' => $data['city'] ?? '', 'time_from' => $data['time'] ?? '09:00', 'time_to' => '15:00', 'title' => $data['promo_title'] ?? ('Místo konání: ' . $data['city']), 'badges' => ['#roadshow', '#previo'], 'description' => 'Akce se koná v ' . ($data['venue'] ?? $data['city']) . '.']];
}

// Nejbližší nadcházející zastávka pro pill-bar
function parseCzechDateRano(string $s): int {
    static $m = ['ledna'=>1,'února'=>2,'března'=>3,'dubna'=>4,'května'=>5,'června'=>6,'července'=>7,'srpna'=>8,'září'=>9,'října'=>10,'listopadu'=>11,'prosince'=>12];
    return preg_match('/(\d+)\.\s*(\S+)\s+(\d{4})/', $s, $p) ? mktime(0,0,0,$m[strtolower($p[2])]??0,(int)$p[1],(int)$p[3]) : 0;
}
$today_ts = strtotime('today');
$nearest_stop = null;
foreach ($stops as $s) {
    $ts = parseCzechDateRano($s['date'] ?? '');
    if ($ts >= $today_ts && (!$nearest_stop || $ts < parseCzechDateRano($nearest_stop['date'] ?? ''))) {
        $nearest_stop = $s;
    }
}
if (!$nearest_stop) $nearest_stop = $stops[0] ?? null;
$pill_city = $nearest_stop['city'] ?? $nearest_stop['title'] ?? $city;
$pill_date = $nearest_stop['date'] ?? $date;
$pill_time = $nearest_stop['time_from'] ?? '';
$past_events = isset($data['past_events']) && is_array($data['past_events']) ? $data['past_events'] : [];
$history_section_title = $data['history_section_title'] ?? 'Proběhlé akce';
require __DIR__ . '/inc-page-content.php';
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previo MeetUp | <?= htmlspecialchars($city) ?><?= htmlspecialchars($page_title_suffix) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- reCAPTCHA v3 – nahraď YOUR_SITE_KEY svým veřejným klíčem z console.cloud.google.com -->
    <script src="https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY" async defer></script>
</head>
<body>

    <?php require __DIR__ . '/inc-nav.php'; ?>

    <header class="hero">
        <div class="hero-bg">
            <?php foreach ($hero_images as $index => $heroImg): ?>
                <div class="slide <?= $index === 0 ? 'active' : '' ?>" style="background-image: url('<?= htmlspecialchars($heroImg) ?>');"></div>
            <?php endforeach; ?>
        </div>

        <div class="hero-content reveal active">
            <h1><?= htmlspecialchars($hero_title) ?></h1>
            <p><?= htmlspecialchars($hero_text) ?></p>
            <div class="hero-actions">
                <a href="registrace.php<?= $slot_param ? ('?slot=' . urlencode($slot_param)) : '' ?>" class="btn-main" style="background: var(--primary); box-shadow: 0 10px 24px rgba(181, 0, 0, 0.24);">Rezervovat místo</a>
                <a href="#program" class="btn-secondary">Zobrazit program</a>
            </div>
        </div>
    </header>

    <div class="stats-section reveal">
        <p class="stats-intro"><?= htmlspecialchars($stats_intro) ?></p>
        <div class="stats-grid">
            <?php foreach ($stats_items as $i => $stat): ?>
            <div class="reveal stagger-<?= $i + 1 ?>"><span class="stat-number"><?= htmlspecialchars($stat['number'] ?? '') ?></span><span class="stat-label"><?= htmlspecialchars($stat['label'] ?? '') ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>

    <section id="proc" class="container">
        <span class="section-tag reveal"><?= htmlspecialchars($content_tag) ?></span>
        <h2 class="section-title reveal"><?= htmlspecialchars($content_title) ?></h2>
        <div class="intro-grid reveal stagger-1">
            <?php foreach ($intro_items as $idx => $it): ?>
            <div class="intro-item">
                <span class="intro-item-number"><?= sprintf('%02d', $idx + 1) ?></span>
                <div class="intro-item-icon-wrap">
                    <div class="intro-item-icon-ring"></div>
                    <div class="intro-item-icon-bg" aria-hidden="true">
                        <i data-lucide="<?= htmlspecialchars($it['icon'] ?? 'star') ?>"></i>
                    </div>
                </div>
                <h3><?= htmlspecialchars($it['title'] ?? '') ?></h3>
                <p><?= nl2br(htmlspecialchars($it['text'] ?? '')) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="content-grid reveal">
            <?php foreach ($content_items as $item): ?>
            <article class="content-item">
                <div class="content-item-icon" aria-hidden="true"><i data-lucide="<?= htmlspecialchars($item['icon'] ?? 'circle') ?>"></i></div>
                <h3><?= htmlspecialchars($item['title'] ?? '') ?></h3>
                <p><?= nl2br(htmlspecialchars($item['text'] ?? '')) ?></p>
                <?php if (!empty($item['bullets'])): ?><ul><?php foreach ((array)$item['bullets'] as $b): ?><li><?= htmlspecialchars($b) ?></li><?php endforeach; ?></ul><?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="automation-section reveal">
            <span class="section-tag"><?= htmlspecialchars($automation_tag) ?></span>
            <div class="hw-block">
                <div class="hw-text">
                    <span style="color: var(--primary); font-weight: 800; text-transform: uppercase;"><?= htmlspecialchars($automation_subtitle) ?></span>
                    <h3 style="font-size: 2.5rem; font-family: 'Source Sans 3'; margin: 15px 0;"><?= htmlspecialchars($automation_title) ?></h3>
                    <p style="margin-bottom: 30px;"><?= nl2br(htmlspecialchars($automation_text)) ?></p>
                    <ul style="list-style: none;"><?php foreach ($automation_bullets as $bl): ?><li>✓ <?= htmlspecialchars($bl) ?></li><?php endforeach; ?></ul>
                </div>
                <div class="hw-image" style="background-image: url('<?= htmlspecialchars($automation_image) ?>');"></div>
            </div>
        </div>
    </section>

    <section id="program" style="background: var(--gray-light);">
        <div class="container">
            <span class="section-tag reveal">Harmonogram</span>
            <h2 class="section-title reveal"><?= htmlspecialchars($program_subtitle) ?></h2>

            <div class="program-list reveal">
                <?php foreach ($program_single as $item): ?>
                <div class="program-item">
                    <div class="time"><?= htmlspecialchars($item['time'] ?? '') ?></div>
                    <div class="program-desc">
                        <h4><?= htmlspecialchars($item['title'] ?? '') ?></h4>
                        <p><?= htmlspecialchars($item['desc'] ?? '') ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="lokalita" class="stops-section">
        <div class="container">
            <span class="section-tag reveal" style="text-align: center;">Příští zastávky</span>
            <div class="stops-list">
                <?php foreach ($stops as $i => $stop):
                    $is_nearest = ($nearest_stop && ($stop['date'] ?? '') === ($nearest_stop['date'] ?? '') && ($stop['city'] ?? '') === ($nearest_stop['city'] ?? ''));
                ?>
                <div class="stop-strip reveal <?= $i > 0 ? 'stagger-1' : '' ?><?= $is_nearest ? ' stop-strip--nearest' : '' ?>">
                    <?php if ($is_nearest): ?><span class="stop-nearest-badge">Nejbližší</span><?php endif; ?>
                    <div class="stop-date-block">
                        <time class="stop-date" datetime="<?= htmlspecialchars($stop['date'] ?? '') ?>"><?= htmlspecialchars($stop['date'] ?? '') ?></time>
                        <p class="stop-time-range"><?= htmlspecialchars($stop['time_from'] ?? '') ?> – <?= htmlspecialchars($stop['time_to'] ?? '') ?></p>
                    </div>
                    <div class="stop-content">
                        <h2 class="stop-title"><?= htmlspecialchars($stop['title'] ?? '') ?></h2>
                        <?php if (!empty($stop['badges'])): ?>
                        <div class="stop-badges">
                            <?php foreach ((array)$stop['badges'] as $badge): ?>
                            <span class="stop-badge"><?= htmlspecialchars($badge) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($stop['description'])): ?>
                        <p class="stop-desc"><?= nl2br(htmlspecialchars($stop['description'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="stop-cta">
                        <a href="registrace.php<?= $slot_param ? ('?slot=' . urlencode($slot_param)) : '' ?>" class="btn-main stop-reg-btn">Zajistit místo</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="lektori" class="container">
        <span class="section-tag reveal"><?= htmlspecialchars($speakers_tag) ?></span>
        <h2 class="section-title reveal"><?= htmlspecialchars($speakers_title) ?></h2>
        <?php foreach ($speakers as $sidx => $sp): ?>
        <div class="speaker-profile reveal <?= $sidx > 0 ? 'stagger-' . $sidx : '' ?>">
            <?php $photo = $sp['photo'] ?? ''; if ($photo && strpos($photo, 'img/') !== 0 && strpos($photo, '/') === false) $photo = 'img/' . $photo; ?>
            <div class="speaker-photo" style="background-image: url('<?= htmlspecialchars($photo ?: 'img/logo-previo-white.svg') ?>');"></div>
            <div class="speaker-info">
                <span class="speaker-role"><?= htmlspecialchars($sp['role'] ?? '') ?></span>
                <h3 style="font-family: 'Source Sans 3'; font-size: 2rem;"><?= htmlspecialchars($sp['name'] ?? '') ?></h3>
                <p class="speaker-bio"><?= nl2br(htmlspecialchars($sp['bio'] ?? '')) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <section id="registrace" class="reg-section">
        <div class="container reveal">
            <span class="section-tag" style="color: #6b7280;"><?= htmlspecialchars($reg_tag) ?></span>
            <h2 class="section-title" style="color: var(--text-main);"><?= htmlspecialchars($reg_title) ?></h2>
            <div class="process-steps reveal stagger-1">
                <div class="process-line"></div>
                <?php foreach ($reg_steps as $step): ?>
                <div class="step">
                    <div class="step-icon" aria-hidden="true"><i data-lucide="<?= htmlspecialchars($step['icon'] ?? 'check') ?>"></i></div>
                    <h4 style="font-size: 1.2rem;"><?= htmlspecialchars($step['title'] ?? '') ?></h4>
                    <p style="opacity: 0.7;"><?= htmlspecialchars($step['text'] ?? '') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="reg-form-container reveal stagger-2">
                <h3 style="font-size: 1.8rem; margin-bottom: 10px; text-align: center;"><?= htmlspecialchars($reg_form_title) ?></h3>
                <p style="text-align: center; margin-bottom: 30px; opacity: 0.7;"><?= htmlspecialchars($reg_form_desc) ?></p>
                <form id="regForm" action="process_registration.php" method="POST">
                    <input type="hidden" name="event_details" value="<?= htmlspecialchars(($data['city'] ?? '') . ' (' . ($data['date'] ?? '') . ')') ?>">
                    <input type="hidden" name="location" value="<?= htmlspecialchars($data['city'] ?? '') ?>">
                    <div class="reg-form-grid">
                        <input type="text" name="name" placeholder="Jméno a příjmení" required>
                        <input type="text" name="hotel" placeholder="Název ubytování" required>
                        <input type="email" name="email" placeholder="E-mail" required>
                        <input type="tel" name="phone" placeholder="Telefon (pro SMS připomínku)">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($fixed_type) ?>">
                        <textarea name="question" rows="3" placeholder="Vaše dotazy nebo témata, která chcete na akci řešit..." class="full-width"></textarea>
                        <button type="submit" class="btn-main full-width" style="margin-top: 10px; background: var(--primary); box-shadow: 0 10px 24px rgba(181, 0, 0, 0.24);">Odeslat závaznou registraci</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="container reveal">
        <div class="faq-container">
            <span class="section-tag"><?= htmlspecialchars($faq_tag) ?></span>
            <h2 class="section-title" style="margin-bottom: 50px;"><?= htmlspecialchars($faq_title) ?></h2>
            <?php foreach ($faq_items as $faq): ?>
            <div class="faq-item"><button class="faq-question" type="button"><?= htmlspecialchars($faq['q'] ?? '') ?></button><p class="faq-answer"><?= nl2br(htmlspecialchars($faq['a'] ?? '')) ?></p></div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!empty($past_events)): ?>
    <section id="historie" class="history-cards-section reveal">
        <div class="container">
            <span class="section-tag"><?= htmlspecialchars($history_section_title) ?></span>
            <h2 class="section-title"><?= htmlspecialchars($history_section_title) ?></h2>
            <div class="history-cards-grid">
                <?php foreach ($past_events as $ev): ?>
                <?php
                    $img = $ev['image'] ?? '';
                    if ($img && strpos($img, 'img/') !== 0 && strpos($img, '/') === false) {
                        $img = 'img/' . $img;
                    }
                    if (!$img) $img = 'img/cover1.jpg';
                ?>
                <article class="history-card">
                    <div class="history-card-image" style="background-image: url('<?= htmlspecialchars($img) ?>');"></div>
                    <span class="history-card-badge" aria-hidden="true">Proběhlo</span>
                    <div class="history-card-body">
                        <time class="history-card-date"><?= htmlspecialchars($ev['date'] ?? '') ?></time>
                        <p class="history-card-place"><?= htmlspecialchars($ev['place'] ?? '') ?></p>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <div class="pill-bar">
        <div>
            <span style="font-size: 0.75rem; font-weight: 800; color: var(--primary); letter-spacing: 1px; text-transform: uppercase;">Nejbližší zastávka</span>
            <strong style="display: block; font-size: 1.1rem; color: var(--text-main);">
                <?= htmlspecialchars($pill_city) ?> &ndash; <?= htmlspecialchars($pill_date) ?><?= $pill_time ? ', ' . htmlspecialchars($pill_time) : '' ?>
            </strong>
        </div>
        <div class="progress-box">
            <span style="font-size: 0.9rem; font-weight: 600; color: #555;">Zbývá <?= $free_spots ?> míst</span>
            <div class="progress-bg"><div class="progress-fill" style="width: <?= $percent ?>%;"></div></div>
            <a href="registrace.php<?= $slot_param ? ('?slot=' . urlencode($slot_param)) : '' ?>" class="btn-main" style="padding: 12px 30px; font-size: 1rem; background: var(--primary); box-shadow: 0 10px 24px rgba(181, 0, 0, 0.24);">Zajistit místo</a>
        </div>
    </div>

    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
    <script>
        lucide.createIcons();

        // Navbar scroll
        const mainNav = document.querySelector('nav');
        function updateNavOnScroll() { if (mainNav) mainNav.classList.toggle('nav-scrolled', window.scrollY > 24); }
        window.addEventListener('scroll', updateNavOnScroll);
        updateNavOnScroll();

        // Desktop dropdowns
        const dropdownToggles = [...document.querySelectorAll('.nav-dropdown-toggle')];
        function getDropdownPanel(btn) { return document.getElementById(btn.getAttribute('aria-controls')); }
        function getDropdownLinks(btn) { const p = getDropdownPanel(btn); return p ? [...p.querySelectorAll('a')] : []; }
        function setDropdownState(btn, isOpen) {
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            getDropdownPanel(btn)?.closest('.dropdown')?.classList.toggle('open', isOpen);
        }
        function closeDesktopDropdowns(except = null) {
            dropdownToggles.forEach(b => { if (b !== except) setDropdownState(b, false); });
        }
        function openDesktopDropdown(btn, focus = null) {
            closeDesktopDropdowns(btn); setDropdownState(btn, true);
            const links = getDropdownLinks(btn);
            if (links.length && focus === 'first') links[0].focus();
            if (links.length && focus === 'last') links[links.length - 1].focus();
        }
        dropdownToggles.forEach(btn => {
            btn.addEventListener('click', () => {
                btn.getAttribute('aria-expanded') === 'true' ? setDropdownState(btn, false) : openDesktopDropdown(btn);
            });
            btn.addEventListener('keydown', e => {
                if (e.key === 'ArrowDown') { e.preventDefault(); openDesktopDropdown(btn, 'first'); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); openDesktopDropdown(btn, 'last'); }
                else if (e.key === 'Escape') { e.preventDefault(); setDropdownState(btn, false); btn.focus(); }
            });
            const panel = getDropdownPanel(btn);
            if (!panel) return;
            panel.addEventListener('keydown', e => {
                const links = getDropdownLinks(btn); const idx = links.indexOf(document.activeElement);
                if (idx === -1) return;
                if (e.key === 'ArrowDown') { e.preventDefault(); links[(idx + 1) % links.length].focus(); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); links[(idx - 1 + links.length) % links.length].focus(); }
                else if (e.key === 'Escape') { e.preventDefault(); setDropdownState(btn, false); btn.focus(); }
            });
            panel.addEventListener('focusout', e => {
                if (!e.relatedTarget || !panel.closest('.dropdown')?.contains(e.relatedTarget)) setDropdownState(btn, false);
            });
        });
        document.addEventListener('click', e => { if (!e.target.closest('.nav-item.dropdown')) closeDesktopDropdowns(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDesktopDropdowns(); });

        // Mobile menu
        const menuToggle = document.querySelector('.menu-toggle');
        const mobileMenu = document.getElementById('mobileMenu');
        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener('click', () => {
                const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
                menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                mobileMenu.hidden = expanded; mobileMenu.classList.toggle('open', !expanded);
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
                link.addEventListener('click', () => { menuToggle.setAttribute('aria-expanded', 'false'); mobileMenu.hidden = true; mobileMenu.classList.remove('open'); });
            });
        }

        // FAQ accordion
        document.querySelectorAll('.faq-question').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var item = this.closest('.faq-item');
                var isOpen = item.classList.contains('open');
                document.querySelectorAll('.faq-item.open').forEach(function(el) { el.classList.remove('open'); });
                if (!isOpen) { item.classList.add('open'); }
            });
        });

        // Scroll-reveal animace
        function revealAnimations() {
            document.querySelectorAll('.reveal').forEach(function(el) {
                if (el.getBoundingClientRect().top < window.innerHeight - 120) {
                    el.classList.add('active');
                }
            });
        }
        window.addEventListener('scroll', revealAnimations);
        requestAnimationFrame(function() { requestAnimationFrame(revealAnimations); });

        // Hero slider
        const slides = document.querySelectorAll('.slide');
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (slides.length > 1 && !prefersReducedMotion) {
            let currentSlide = 0;
            setInterval(function() {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }, 5000);
        }

        // Pokud stránka byla načtena přes /registrace, posuň se na sekci #registrace
        <?php if (!empty($scroll_to_registration)): ?>
        document.addEventListener('DOMContentLoaded', function () {
            var reg = document.getElementById('registrace');
            if (reg) {
                reg.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
        <?php endif; ?>

        // Formulář – AJAX odeslání s reCAPTCHA v3
        var regForm = document.getElementById('regForm');
        if (regForm) {
            regForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = this.querySelector('button[type="submit"]');
                var originalText = btn.innerText;
                btn.innerText = 'Odesílám...'; btn.disabled = true;
                var form = e.target;
                // Získej reCAPTCHA token a přidej ho do FormData
                var submitForm = function(token) {
                    var fd = new FormData(form);
                    fd.append('recaptcha_token', token || '');
                    fetch('process_registration.php', { method: 'POST', body: fd })
                    .then(function(res) { return res.text(); })
                    .then(function(text) {
                        try {
                            var data = JSON.parse(text);
                            if (data.success) {
                                document.getElementById('successModal').style.display = 'flex';
                                form.reset();
                            } else { alert('Chyba: ' + data.message); }
                        } catch(err) { alert('Chyba serveru.'); console.log(text); }
                    })
                    .catch(function() { alert('Chyba připojení.'); })
                    .finally(function() { btn.innerText = originalText; btn.disabled = false; });
                };
                // Pokud je reCAPTCHA načtena, získej token; jinak odešli bez něj
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.ready(function() {
                        grecaptcha.execute('YOUR_SITE_KEY', { action: 'register' }).then(submitForm);
                    });
                } else {
                    submitForm('');
                }
            });
        }
        function closeModal() { document.getElementById('successModal').style.display = 'none'; }
    </script>

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

    <div id="successModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div class="check-icon">✓</div>
            <h2 style="color: #333;">Registrace přijata!</h2>
            <p style="margin: 20px 0; color: #666;">Potvrzení a detaily jsme vám odeslali na e-mail.</p>
            <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; margin: 25px 0;">
                <p style="font-weight: 700; margin-bottom: 15px; color: var(--primary);">Uložte si termín do kalendáře:</p>
                <div>
                    <a href="download_ics.php" class="btn-cal outlook">Outlook / iCal</a>
                </div>
            </div>
            <button onclick="closeModal()" class="btn-main" style="width: 100%;">Zavřít okno</button>
        </div>
    </div>
</body>
</html>

