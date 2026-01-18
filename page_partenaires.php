<?php
require_once __DIR__ . '/init.php';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Nos Partenaires — EGEE</title>
    <link rel="icon" type="image/png" href="assets/image/favicon.png">
    <link rel="stylesheet" href="newcss.css">
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
        <div class="mission-why prtnr-why">
            <span class="label">Engagement</span>
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

<!-- CARROUSEL -->
<div class="carrousel-container">
    <div class="carrousel">
        <a href="https://www.afpa.fr" target="_blank"><img src="assets/image/logo-partenaires/AFPA-1.png" alt="AFPA"></a>
        <a href="https://www.apec.fr" target="_blank"><img src="assets/image/logo-partenaires/APEC.jpg" alt="APEC"></a>
        <a href="https://www.banque-france.fr" target="_blank"><img src="assets/image/logo-partenaires/BANQUE-DE-FRANCE-1-1.jpg" alt="Banque de France"></a>
        <a href="https://www.bpifrance.fr" target="_blank"><img src="assets/image/logo-partenaires/BPI-FRANCE-1.png" alt="BPI France"></a>
        <a href="https://www.cci.fr" target="_blank"><img src="assets/image/logo-partenaires/CCI-FRANCE-1.png" alt="CCI France"></a>
        <a href="https://www.cip-idf.org" target="_blank"><img src="assets/image/logo-partenaires/CIP-1.jpg" alt="CIP"></a>
        <a href="https://www.cpme.fr" target="_blank"><img src="assets/image/logo-partenaires/CPME.jpg" alt="CPME"></a>
        <a href="https://www.energiejeunes.fr" target="_blank"><img src="assets/image/logo-partenaires/Energie-Jeunes.jpg" alt="Énergie Jeunes"></a>
        <a href="https://www.epide.fr" target="_blank"><img src="assets/image/logo-partenaires/EPIDE.jpg" alt="EPIDE"></a>
        <a href="https://www.deuxiemechance.org/" target="_blank"><img src="assets/image/logo-partenaires/Fondation-de-la-2eme-Chance.png" alt="Fondation de la 2e Chance"></a>
        <a href="https://www.franceactive.org" target="_blank"><img src="assets/image/logo-partenaires/France-ACTIVE-.png" alt="France Active"></a>
        <a href="https://www.francebenevolat.org" target="_blank"><img src="assets/image/logo-partenaires/France-Benevolat.png" alt="France Bénévolat"></a>
        <a href="https://www.francetravail.fr" target="_blank"><img src="assets/image/logo-partenaires/Capture-decran-2025-10-21-182252.png" alt="France Travail"></a>
        <a href="https://www.ag2rlamondiale.fr" target="_blank"><img src="assets/image/logo-partenaires/Logo-AG2R-LA-MONDIALE-scaled.jpg" alt="AG2R La Mondiale"></a>
        <a href="https://lementorat.fr/" target="_blank"><img src="assets/image/logo-partenaires/Logo-Collectif-du-Mentorat-1.png" alt="Collectif du Mentorat"></a>
        <a href="https://www.sports.gouv.fr" target="_blank"><img src="assets/image/logo-partenaires/MINISTERE-DES-SPORTS-ET-DE-LA-JEUNESSE.jpg" alt="Ministère des Sports et de la Jeunesse"></a>
        <a href="https://www.unml.info" target="_blank"><img src="assets/image/logo-partenaires/Reseaux-missions-locales.jpg" alt="Réseau des Missions Locales"></a>
        <a href="https://www.education.gouv.fr/" target="_blank"><img src="assets/image/logo-partenaires/Capture-decran-2025-10-21-182019.png" alt="Ministère de l'Éducation nationale"></a>
        <a href="https://www.egalite-femmes-hommes.gouv.fr/" target="_blank"><img src="assets/image/logo-partenaires/Capture-decran-2025-10-21-182149.png" alt="Égalité Femmes-Hommes"></a>
    </div>

    <!-- Boutons -->
    <button class="carrousel-btn prev">&#10094;</button>
    <button class="carrousel-btn next">&#10095;</button>

    <!-- Points -->
    <div class="carrousel-dots"></div>
</div>

<?php include 'footer.php'; ?>

<script src="assets/js/carrousel.js"></script>
</body>
</html>
