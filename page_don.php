<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EGEE – Faire un Don</title>
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

    <!-- HERO DON -->
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-text">
                <p class="hero-tag">Soutenir EGEE</p>
                <h1>Votre générosité transforme des vies.</h1>
                <p class="hero-subtitle">
                    Grâce à votre don, des milliers de jeunes bénéficient d’un accompagnement personnalisé,
                    assuré par nos bénévoles expérimentés.
                </p>

                <div class="hero-buttons">
                    <a href="#don-form" class="btn btn-primary">Faire un don maintenant</a>
                    <a href="#impact" class="btn btn-secondary">Voir l’impact</a>
                </div>

                <div class="hero-meta">
                    <span>Dons déductibles des impôts*</span>
                    <span>Un réseau partout en France</span>
                </div>
            </div>

            <div class="hero-card">
                <h2>À quoi sert votre don ?</h2>
                <ul>
                    <li><strong>Former</strong> nos bénévoles</li>
                    <li><strong>Multiplier</strong> nos interventions scolaires</li>
                    <li><strong>Créer</strong> des outils pour les jeunes</li>
                </ul>
                <p class="hero-note">
                    *Selon la législation fiscale en vigueur.
                </p>
            </div>
        </div>
    </section>


    <!-- IMPACT -->
    <section class="actions" id="impact">
        <div class="container">
            <h2>Votre impact concret</h2>
            <p class="section-intro">
                Chaque don est une action directe pour l'avenir des jeunes.
            </p>

            <div class="cards-grid">

                <article class="card">
                    <p class="card-label">Jeunes</p>
                    <h3>Accompagnement scolaire et professionnel</h3>
                    <p>
                        Simulation d’entretien, rédaction de CV, aide à l’orientation,
                        préparation aux projets professionnels.
                    </p>
                </article>

                <article class="card">
                    <p class="card-label">Établissements</p>
                    <h3>Interventions éducatives</h3>
                    <p>
                        Nos bénévoles interviennent dans les classes afin de transmettre
                        leur savoir et favoriser la réussite scolaire.
                    </p>
                </article>

                <article class="card">
                    <p class="card-label">Bénévoles</p>
                    <h3>Transmission d’expérience</h3>
                    <p>
                        Un espace pour partager leur carrière, leurs connaissances
                        et transmettre leur savoir-faire.
                    </p>
                </article>

            </div>
        </div>
    </section>


    <!-- CHOIX DU DON -->
    <section class="engagements" id="don-form">
        <div class="container">
            <h2>Choisissez votre type de don</h2>
            <p class="section-intro">
                Tous les dons contribuent directement à nos actions.
            </p>

            <div class="cards-grid">

                <!-- Don ponctuel -->
                <article class="card">
                    <h3>Don ponctuel</h3>
                    <p>
                        Soutenez EGEE une fois, au montant que vous souhaitez.
                    </p>
                    <p style="color:#6b7280;margin-top:0.6rem;">
                        Exemple : 20€, 50€, 100€...
                    </p>
                    <a href="#" class="btn btn-primary" style="margin-top:0.9rem;">Faire un don ponctuel</a>
                </article>

                <!-- Don mensuel -->
                <article class="card">
                    <h3>Don mensuel</h3>
                    <p>
                        Un soutien régulier permet de planifier nos actions sur le long terme.
                    </p>
                    <p style="color:#6b7280;margin-top:0.6rem;">
                        Exemple : 10€/mois, 20€/mois...
                    </p>
                    <a href="#" class="btn btn-outline" style="margin-top:0.9rem;">Faire un don mensuel</a>
                </article>

                <!-- Mécénat -->
                <article class="card">
                    <h3>Mécénat d’entreprise</h3>
                    <p>
                        Impliquez votre entreprise dans une démarche solidaire et engagée.
                    </p>
                    <p style="color:#6b7280;margin-top:0.6rem;">
                        Contact dédié pour les entreprises.
                    </p>
                    <a href="contact.html" class="btn btn-secondary" style="margin-top:0.9rem;">Nous contacter</a>
                </article>

            </div>
        </div>
    </section>


    <!-- TRANSPARENCE -->
    <section class="rapport">
        <div class="container rapport-inner">
            <div>
                <h2>Transparence & informations fiscales</h2>
                <p>
                    Chaque année, EGEE publie un rapport complet mettant en lumière l’utilisation des fonds,
                    les actions menées et les résultats obtenus.
                </p>
                <p style="margin-top:0.7rem;">
                    Vos dons peuvent être déductibles d’impôts selon votre situation personnelle.
                </p>
            </div>

            <div class="rapport-buttons">
                <a href="rapports/rapport-2024.pdf" class="btn btn-primary">Rapport 2024</a>
                <a href="contact.html" class="btn btn-outline">Poser une question</a>
            </div>
        </div>
    </section>

</main>
<?php include('footer.php'); ?>

<script src = "menuBuger.js"> </script>

</body>
</html>
