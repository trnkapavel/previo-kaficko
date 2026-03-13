<?php
// Homepage – rozcestník pro testování (po otestování stránka zmizí)
$json_data = @file_get_contents(__DIR__ . '/data.json');
$data = $json_data ? json_decode($json_data, true) : [];
if (!is_array($data)) {
    $data = [];
}

$city = $data['city'] ?? 'MeetUp';
$hero_images = [];
foreach (['hero_morning_images', 'hero_afternoon_images'] as $key) {
    $gallery = $data[$key] ?? [];
    if (!is_array($gallery) && !empty($gallery)) $gallery = [$gallery];
    foreach ($gallery as $img) {
        if ($img && strpos($img, 'img/') !== 0 && strpos($img, '/') === false) $img = 'img/' . $img;
        if ($img) $hero_images[] = $img;
    }
}
if (empty($hero_images)) {
    $hero_images = ['img/cover1.jpg', 'img/cover2.jpg', 'img/cover3.jpg'];
}
$hero_image = $hero_images[0];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previo MeetUp | <?= htmlspecialchars($city) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .home-hero { min-height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative; }
        .home-hero .hero-bg { position: absolute; inset: 0; }
        .home-hero .hero-bg .slide { position: absolute; inset: 0; background-size: cover; background-position: center; }
        .home-fork { position: relative; z-index: 5; text-align: center; padding: 2rem; }
        .home-fork-buttons { display: flex; flex-wrap: wrap; gap: 24px; justify-content: center; margin-top: 2rem; }
        .home-fork-btn { display: inline-block; padding: 28px 48px; font-size: 1.35rem; font-weight: 700; border-radius: 12px; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; font-family: 'Source Sans 3', sans-serif; min-width: 260px; text-align: center; }
        .home-fork-btn:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.2); }
        .home-fork-btn--morning { background: #fff; color: var(--primary, #b50000); border: 3px solid #fff; box-shadow: 0 10px 32px rgba(0,0,0,0.15); }
        .home-fork-btn--afternoon { background: var(--primary, #b50000); color: #fff; border: 3px solid #fff; box-shadow: 0 10px 32px rgba(181,0,0,0.35); }
        .home-fork-label { color: rgba(255,255,255,0.95); font-size: 1.1rem; font-weight: 600; text-shadow: 0 1px 4px rgba(0,0,0,0.3); }
        @media (max-width: 640px) { .home-fork-buttons { flex-direction: column; } .home-fork-btn { min-width: 100%; } }
    </style>
</head>
<body>

    <nav>
        <a class="logo" href="/" aria-label="Previo domů">
            <img src="img/logo-previo-white.svg" alt="Previo">
            <span class="logo-tagline">Více hostů. Méně starostí.</span>
        </a>
    </nav>

    <header class="hero home-hero">
        <div class="hero-bg">
            <div class="slide active" style="background-image: url('<?= htmlspecialchars($hero_image) ?>');"></div>
        </div>
        <div class="home-fork">
            <p class="home-fork-label">Vyberte typ akce</p>
            <div class="home-fork-buttons">
                <a href="akce-rano.php" class="home-fork-btn home-fork-btn--morning">Dopolední MeetUp<br><small style="font-size: 0.75em; font-weight: 600; opacity: 0.9;">Connect – pro neklienty</small></a>
                <a href="akce-odpoledne.php" class="home-fork-btn home-fork-btn--afternoon">Odpolední MeetUp<br><small style="font-size: 0.75em; font-weight: 600; opacity: 0.9;">PRO/LITE – pro klienty</small></a>
            </div>
        </div>
    </header>

</body>
</html>
