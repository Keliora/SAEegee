<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EGEE – Accompagner, Former, Transmettre</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="newcss.css">
    <link rel="icon" type="image/png" href="img/favicon.png">
</head>

<body>

<?php
$pageTitle = "Accueil - EGEE"; // Optionnel : titre dynamique
include('header.php');
?>

<main>

    <!-- HERO -->
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-text">
                <p class="hero-tag">EGEE – Bénévolat de compétences</p>
                <h1>Accompagner, Former,<br> Transmettre.</h1>
                <p class="hero-subtitle">
                    L’expérience des seniors bénévoles au service des jeunes, des écoles et des entreprises.
                </p>
                <div class="hero-buttons">
                    <a href="#mission" class="btn btn-primary">Nous découvrir</a>
                    <a href="#don" class="btn btn-secondary">Soutenir nos actions</a>
                </div>
                <div class="hero-meta">
                    <span>+ de 40 ans d’engagement</span>
                    <span>Présent sur tout le territoire</span>
                </div>
            </div>

            <div class="hero-card">
                <h2>EGEE en un coup d’œil</h2>
                <ul>
                    <li><strong>35 000</strong> jeunes accompagnés chaque année</li>
                    <li><strong>2 000</strong> bénévoles engagés</li>
                    <li><strong>1 500</strong> établissements partenaires</li>
                </ul>
                <p class="hero-note">
                    Ensemble, nous facilitons l’insertion professionnelle et la réussite des jeunes.
                </p>
            </div>
        </div>
    </section>

    <!-- BANDEAU NOUS CONNAÎTRE -->
    <section class="bandeau" id="mission">
        <div class="container">
            <span>— Nous connaître —</span>
        </div>
    </section>

    <!-- NOTRE MISSION -->
    <section class="mission">
        <div class="container mission-inner">
            <div class="mission-text">
                <h2>Notre mission</h2>
                <p>
                    EGEE met en lien l’expérience des seniors bénévoles avec les besoins
                    des jeunes, des écoles et des entreprises. Notre mission est de favoriser
                    l’insertion professionnelle, l’accompagnement et la transmission de savoirs
                    à travers des actions concrètes sur le terrain.
                </p>
                <div class="mission-points">
                    <div class="mission-item">
                        <h3>Accompagner les jeunes</h3>
                        <p>Coaching CV, simulation d’entretiens, aide à l’orientation.</p>
                    </div>
                    <div class="mission-item">
                        <h3>Soutenir les établissements</h3>
                        <p>Interventions en classe, ateliers thématiques, projets tutorés.</p>
                    </div>
                    <div class="mission-item">
                        <h3>Transmettre l’expérience</h3>
                        <p>Partage de parcours, expertise métier, témoignages inspirants.</p>
                    </div>
                </div>
            </div>

            <div class="mission-why">
                <p class="label">Pourquoi EGEE ?</p>
                <p>
                    Parce que nous croyons que chaque expérience compte. Nos bénévoles
                    mettent leur carrière, leurs erreurs, leurs réussites et leurs compétences
                    au service des générations futures.
                </p>
            </div>
        </div>
    </section>

    <!-- ACTIONS & CHIFFRES -->
    <section class="actions" id="actions">
        <div class="container">
            <h2>Nos actions en chiffres</h2>
            <p class="section-intro">
                Derrière chaque chiffre, il y a une rencontre, un échange, un projet qui a pris vie.
            </p>
            <div class="cards-grid">
                <article class="card">
                    <p class="card-label">Pour les jeunes</p>
                    <h3>Ateliers & accompagnements</h3>
                    <p>
                        Des ateliers pratiques pour préparer les examens, les entretiens
                        et les premières expériences professionnelles.
                    </p>
                </article>
                <article class="card">
                    <p class="card-label">Pour les établissements</p>
                    <h3>Partenariats durables</h3>
                    <p>
                        Des interventions régulières construites avec les équipes pédagogiques
                        pour répondre aux besoins du terrain.
                    </p>
                </article>
                <article class="card">
                    <p class="card-label">Pour les bénévoles</p>
                    <h3>Un engagement utile</h3>
                    <p>
                        La possibilité de rester actif, de transmettre son savoir-faire
                        et de donner du sens à son temps.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <!-- VIDEOS -->
    <section class="videos">
        <div class="container">
            <h2>Vidéos EGEE</h2>
            <p class="section-intro">
                Découvrez quelques témoignages et actions de nos bénévoles sur le terrain.
            </p>
            <div class="video-grid">
                <div class="video-placeholder">
                    <video controls>
                        <source src="https://www.egee.asso.fr/wp-content/uploads/2025/08/EGEE_Clip_Presentation.mp4?_=1" type="video/mp4">
                    </video>

                    <span>Vidéo présentation</span>
                </div>
                <div class="video-placeholder">
                    <video controls>
                        <source src="https://www.egee.asso.fr/wp-content/uploads/2025/08/Les-Racines-dEGEE.mp4?_=2" type="video/mp4">
                    </video>

                    <span>Témoignage d’un bénévole</span>
                </div>
            </div>
        </div>
    </section>

    <!-- RAPPORT D’ACTIVITÉ -->
    <section class="rapport">
        <div class="container rapport-inner">
            <div>
                <h2>Rapport d’activité</h2>
                <p>
                    Consultez notre dernier rapport d’activité qui met en lumière nos actions,
                    les résultats obtenus et les perspectives pour les années à venir.
                </p>
            </div>
            <div class="rapport-buttons">
                <a href="assets/rapports/Rapport-Annuel-2024.pdf" class="btn btn-primary" >Rapport 2024</a>
                <a href="assets/rapports/RA-2023-VD.pdf"  class="btn btn-outline" >Rapport 2023</a>
                <a href="assets/rapports/EGEE-RA2022_V12-1.pdf" class="btn btn-outline"  >Rapport 2022</a>
            </div>
        </div>
    </section>

    <!-- NOS ENGAGEMENTS -->
    <section class="engagements">
        <div class="container">
            <h2>Nos engagements</h2>
            <p class="section-intro">
                Nous portons des valeurs fortes qui guident chacune de nos actions.
            </p>
            <div class="cards-grid">
                <article class="card">
                    <h3>Transparence</h3>
                    <p>Une gestion rigoureuse et transparente des dons et des partenariats.</p>
                </article>
                <article class="card">
                    <h3>Proximité</h3>
                    <p>Des équipes bénévoles ancrées dans les régions, au plus près des besoins.</p>
                </article>
                <article class="card">
                    <h3>Respect</h3>
                    <p>Un accompagnement bienveillant, basé sur l’écoute et la confiance.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- APPEL AU DON -->
    <section class="don" id="don">
        <div class="container don-inner">
            <div class="don-text">
                <h2>Faites un don, faites la différence</h2>
                <p>
                    Votre soutien nous permet de multiplier les interventions, de former
                    davantage de bénévoles et d’accompagner toujours plus de jeunes.
                </p>
            </div>
            <a href="#" class="btn btn-donate btn-large">Je fais un don</a>
        </div>
    </section>

</main>

<script src = "menuBuger.js"> </script>

<?php include('footer.php'); ?>



</body>
</html>
