<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Nos Partenaires — EGEE</title>
    <link rel="icon" type="image/png" href="assets/image/favicon.png">
    <link rel="stylesheet" href="newcss.css">
    <style>
        /* Styles spécifiques pour le carrousel qui n'étaient pas dans le global */
        .carrousel-container { position: relative; overflow: hidden; padding: 2rem 0; background: #fff; }
        .carrousel { display: flex; transition: transform 0.5s ease-in-out; gap: 20px; }
        .carrousel a { min-width: calc(25% - 20px); flex-shrink: 0; display: flex; justify-content: center; }
        .carrousel img { max-height: 80px; width: auto; filter: grayscale(100%); transition: 0.3s; opacity: 0.7; }
        .carrousel img:hover { filter: grayscale(0%); opacity: 1; }
        .carrousel-btn { position: absolute; top: 50%; transform: translateY(-50%); background: var(--blue); color: white; border: none; padding: 10px; cursor: pointer; z-index: 10; border-radius: 50%; width: 40px; height: 40px; }
        .prev { left: 10px; } .next { right: 10px; }
        .carrousel-dots { text-align: center; margin-top: 1rem; }
        .carrousel-dots span { display: inline-block; width: 10px; height: 10px; background: #ccc; margin: 0 5px; border-radius: 50%; cursor: pointer; }
        .carrousel-dots span.active { background: var(--orange); }
    </style>
</head>

<body>
<?php
$pageTitle = "Accueil - EGEE"; // Optionnel : titre dynamique
include('header.php');
?>

<section class="hero">
    <div class="container">
        <div class="hero-tag">Réseau & Collaboration</div>
        <h1>Nos Partenaires</h1>
        <p class="hero-subtitle">L’expérience des seniors au service des jeunes et des entreprises.</p>
    </div>
</section>

<div class="bandeau">
    Qui sont nos partenaires ?
</div>

<section class="mission">
    <div class="container mission-inner">
        <div class="mission-text">
            <h2>La coopération au cœur de notre action</h2>
            <p>Nos missions sont fondées sur une logique de <strong>complémentarité avec l’action des organismes institutionnels ou privés</strong>.</p>
            <p>Grâce à nos <strong>1 800 conseillers</strong>, nous faisons vivre nos partenariats avec les acteurs socio-économiques des territoires grâce à des relations de proximité.</p>
        </div>
        <div class="mission-why" style="margin-left: 0;"> <span class="label">Engagement</span>
            <p>Proximité, confiance et bienveillance au profit des porteurs de projets.</p>
        </div>
    </div>
</section>

<section class="actions">
    <div class="container">
        <div class="cards-grid">
            <article class="card">
                <div class="card-label">Institutionnel</div>
                <h3>Partenaires prescripteurs</h3>
                <p>Intervention auprès des bénéficiaires du RSA, demandeurs d’emploi et jeunes en insertion.</p>
            </article>
            <article class="card">
                <div class="card-label">Opérationnel</div>
                <h3>Partenaires opérateurs</h3>
                <p>Appui pour mentorer ou suivre des entreprises en démarrage (CCI, CMA, France Active).</p>
            </article>
            <article class="card">
                <div class="card-label">Économique</div>
                <h3>Coopérations</h3>
                <p>Animation des Groupements de prévention (GPA) avec la CPME, Urssaf et Banque de France.</p>
            </article>
        </div>
    </div>
</section>

<section class="videos"> <div class="container">
    <h2 style="text-align: center; margin-bottom: 2rem;">Ils nous font confiance</h2>
    <div class="carrousel-container">
        <div class="carrousel">
            <a href="https://www.afpa.fr" target="_blank"><img src="assets/image/logo-partenaires/AFPA-1.png" alt="AFPA"></a>
            <a href="https://www.apec.fr" target="_blank"><img src="assets/image/logo-partenaires/APEC.jpg" alt="APEC"></a>
            <a href="https://www.banque-france.fr" target="_blank"><img src="assets/image/logo-partenaires/BANQUE-DE-FRANCE-1-1.jpg" alt="Banque de France"></a>
            <a href="https://www.bpifrance.fr" target="_blank"><img src="assets/image/logo-partenaires/BPI-FRANCE-1.png" alt="BPI France"></a>
            <a href="https://www.cci.fr" target="_blank"><img src="assets/image/logo-partenaires/CCI-FRANCE-1.png" alt="CCI France"></a>
            <a href="https://www.francetravail.fr" target="_blank"><img src="assets/image/logo-partenaires/Capture-decran-2025-10-21-182252.png" alt="France Travail"></a>
        </div>
        <button class="carrousel-btn prev">&#10094;</button>
        <button class="carrousel-btn next">&#10095;</button>
        <div class="carrousel-dots"></div>
    </div>
</div>
</section>
<?php include('footer.php'); ?>
<script src = "menuBuger.js"> </script>
</body>
</html>