<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Actualités — EGEE</title>
    <link rel="icon" type="image/png" href="assets/image/favicon.png">
    <link rel="stylesheet" href="newcss.css">
    </head>
<body>

<?php
$pageTitle = "Accueil - EGEE";
include('header.php');
?>

<main>
    <section class="hero">
        <div class="container hero-inner" style="grid-template-columns: 1fr;">
            <div class="hero-text">
                <h1>Actualités d’EGEE</h1>
                <p class="hero-subtitle">
                    L’expérience des seniors au service de jeunes et des entreprises.
                </p>
            </div>
        </div>
    </section>

    <section class="bandeau">
        <div class="container">
            <span>— Actualités —</span>
        </div>
    </section>

    <div class="container" style="padding: 2rem 0;">
        <div class="recherche_article" style="display: flex; gap: 0.5rem; margin-bottom: 2rem;">
            <input placeholder="Rechercher..." style="padding: 0.5rem; border: 1px solid var(--grey); border-radius: 4px; flex-grow: 1;">
            <button class="btn btn-secondary">🔍</button>
            <button class="btn btn-outline">▼</button>
        </div>



        <section class="large_article_container cards-grid" style="grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            <article class="card large" style="grid-column: span 2; display: flex; align-items: flex-start; gap: 1rem;">
                <img src="assets/image/article1.png" alt="Article 1" style="max-width: 250px; border-radius: 12px;"/>
                <div class="corps_article1">
                    <h3>«Orientation, recherche d’un job d’été… À Tinténiac, EGEE coache les lycéens»</h3>
                    <p>
                        <i>15 Janvier 2025</i> - Les élèves des classes de terminale du lycée professionnel Jeanne-Jugan de Tinténiac (Ille-et-Vilaine) bénéficient des ...<br>
                        <a href="https://www.ouest-france.fr" style="color: var(--text-muted); font-size: 0.85rem;">PHOTO : www.ouest-france.fr</a><br>
                        <a href="https://rennes.maville.com/actu/actudet_-orientation-recherche-d-un-job-d-ete...-a-tinteniac-cette-association-coache-les-lyceens-_dep-6635075_actu.Htm" class="btn btn-outline btn-small" style="margin-top: 0.5rem; display: inline-block;">Lire l'article</a>
                    </p>
                </div>
            </article>
            <article class="card medium" style="display: flex; align-items: flex-start; gap: 1rem;">
                <img src="assets/image/article2.png" alt="Article 2" style="max-width: 150px; border-radius: 12px;"/>
                <div class="corps_article2">
                    <h3>«BTS de Saint-Gabriel Pont-l’Abbé – Privilégier la cohésion »</h3>
                    <p>
                        <i>26 septembre 2024</i> - Pour les deux sections BTS de l'ensemble scolaire Saint-Gabriel, à Pont-l'Abbé (Finistère), la cohésion fait partie des ...<br>
                        <a href="https://www.ouest-france.fr" style="color: var(--text-muted); font-size: 0.85rem;">PHOTO : www.ouest-france.fr</a><br>
                        <a href="https://www.ouest-france.fr/bretagne/pont-labbe-29120/dans-les-bts-de-saint-gabriel-a-pont-labbe-on-souhaite-privilegier-la-cohesion-ba866d8c-7b17-11ef-977d-be93a24a1048" class="btn btn-outline btn-small" style="margin-top: 0.5rem; display: inline-block;">Lire l'article</a>
                    </p>
                </div>
            </article>
            <article class="card small1" style="display: flex; align-items: flex-start; gap: 1rem;">
                <img src="assets/image/article3.png" alt="Article 3" style="max-width: 150px; border-radius: 12px;"/>
                <div class="corps_article3">
                    <h3>«Saint-Amant-de-Boixe : les collégiens de 4e préparent déjà leur stage en entreprise»</h3>
                    <p>
                        <i>31 Mars 2025</i> - Les élèves de 4e du collège de Saint-Amant-de-Boixe ...<br>
                        <a href="https://www.charentelibre.fr" style="color: var(--text-muted); font-size: 0.85rem;">PHOTO:www.charentelibre.fr</a><br>
                        <a href="https://www.charentelibre.fr/charente/saint-amant-de-boixe/saint-amant-de-boixe-les-collegiens-de-4e-preparent-deja-leur-stage-en-entreprise-23815255.php" class="btn btn-outline btn-small" style="margin-top: 0.5rem; display: inline-block;">Lire l'article</a>
                    </p>
                </div>
            </article>
            <article class="card small2" style="grid-column: span 2; display: flex; align-items: flex-start; gap: 1rem;">
                <img src="assets/image/article4.png" alt="Article 4" style="max-width: 250px; border-radius: 12px;"/>
                <div class="corps_article4">
                    <h3>«Monswiller se prépare face aux crues !»</h3>
                    <p>
                        <i>30 Avril 2025</i> - Cet exercice a permis de tester l’efficacité de la coordination interservices...<br>
                        <a href="https://www.dna.fr" style="color: var(--text-muted); font-size: 0.85rem;">PHOTO : www.dna.fr</a><br>
                        <a href="https://www.dna.fr/environnement/2025/04/30/la-commune-teste-sa-capacite-de-reaction-face-aux-crues-lors-d-un-exercice-d-ampleur" class="btn btn-outline btn-small" style="margin-top: 0.5rem; display: inline-block;">Lire l'article</a>
                    </p>
                </div>
            </article>
        </section>
    </div>
</main>
<?php include('footer.php'); ?>

<script src = "menuBuger.js"> </script>
</body>
</html>