<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EGEE – Faire un Don</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="newcss.css">
    <link rel="icon" type="image/png" href="assets/image/favicon.png">
</head>

<body>

<?php
$pageTitle = "Faire un don - EGEE";
include('header.php');
?>

<main>

    <!-- HERO -->
    <section class="hero">
        <div class="container hero-inner hero-single">
            <div class="hero-text">
                <span class="hero-tag">Soutenir EGEE</span>
                <h1>Faire un don</h1>
                <p class="hero-subtitle">
                    Votre soutien permet à EGEE de renforcer son action : accompagner des jeunes,
                    des porteurs de projet et des entreprises grâce à l’expérience des seniors.
                </p>

                <div class="hero-buttons">
                    <a class="btn btn-donate btn-large"
                       href="https://www.helloasso.com/associations/egee/formulaires/1"
                       target="_blank" rel="noopener">
                        Donner via HelloAsso
                    </a>
                    <a class="btn btn-outline btn-large" href="page_contact.php">Nous contacter</a>
                </div>

                <div class="trust-badges">
                    <span class="trust-badge">Paiement sécurisé</span>
                    <span class="trust-badge">Don en ligne (HelloAsso)</span>
                    <span class="trust-badge">Reçu fiscal selon conditions</span>
                </div>
            </div>
        </div>
    </section>

    <!-- IMPACT (3 cartes) -->
    <section class="don-section">
        <div class="container">
            <div class="section-head">
                <h2>À quoi sert votre don ?</h2>
                <p>Des actions concrètes, mesurables, et utiles sur le terrain.</p>
            </div>

            <div class="impact-grid">
                <article class="impact-card">
                    <div class="impact-icon">🎓</div>
                    <h3>Accompagnement des jeunes</h3>
                    <p>Mentorat, aide à l’orientation, préparation aux entretiens, conseils CV.</p>
                </article>

                <article class="impact-card">
                    <div class="impact-icon">🚀</div>
                    <h3>Soutien aux porteurs de projet</h3>
                    <p>Structuration, stratégie, pitch, réseau, retours d’expérience concrets.</p>
                </article>

                <article class="impact-card">
                    <div class="impact-icon">🏢</div>
                    <h3>Appui aux entreprises</h3>
                    <p>Diagnostic, recommandations, expertise senior au service de la performance.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- CTA CARD -->
    <section class="don-page">
        <div class="container">
            <div class="don-page-card">
                <h2>Accéder au formulaire HelloAsso</h2>
                <p class="don-page-text">
                    Le don se fait directement sur le formulaire officiel d’EGEE.
                    Cela prend moins d’une minute.
                </p>

                <div class="don-page-actions">
                    <a class="btn btn-donate btn-large"
                       href="https://www.helloasso.com/associations/egee/formulaires/1"
                       target="_blank" rel="noopener">
                        Ouvrir le formulaire
                    </a>
                </div>

                <div class="don-microcopy">

                </div>
            </div>
        </div>
    </section>

    <!-- TRANSPARENCE -->
    <section class="don-section">
        <div class="container">
            <div class="split">
                <div class="split-left">
                    <h2>Transparence</h2>
                    <p>
                        Nous souhaitons que chaque donateur comprenne clairement l’utilité de son geste.
                        Votre contribution aide à financer les actions, la logistique et l’organisation des programmes.
                    </p>

                    <ul class="check-list">
                        <li>Suivi des actions et retours terrain</li>
                        <li>Coordination et outils de gestion</li>
                        <li>Événements & ateliers d’accompagnement</li>
                    </ul>
                </div>

                <div class="split-right">
                    <div class="info-box">
                        <h3>Besoin d’un justificatif ?</h3>
                        <p>
                            Selon les conditions, un reçu peut être disponible. Pour toute question :
                        </p>
                        <a class="btn btn-outline" href="page_contact.php">Contacter EGEE</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="don-section faq">
        <div class="container">
            <div class="section-head">
                <h2>Questions fréquentes</h2>
                <p>Les réponses aux points les plus demandés.</p>
            </div>

            <div class="faq-list">
                <details class="faq-item">
                    <summary>Le don est-il sécurisé ?</summary>
                    <div class="faq-content">
                        Oui. Le paiement est géré par HelloAsso via une page sécurisée.
                    </div>
                </details>

                <details class="faq-item">
                    <summary>Je peux donner quand je veux ?</summary>
                    <div class="faq-content">
                        Oui, vous pouvez faire un don à tout moment via le formulaire.
                    </div>
                </details>

                <details class="faq-item">
                    <summary>Comment aider autrement qu’en donnant ?</summary>
                    <div class="faq-content">
                        Vous pouvez aussi relayer EGEE, participer à des événements, ou proposer votre aide / expertise.
                        Écrivez-nous via la page contact.
                    </div>
                </details>
            </div>
        </div>
    </section>

    <!-- AUTRES FAÇONS D’AIDER -->
    <section class="don-section">
        <div class="container">
            <div class="section-head">
                <h2>Vous pouvez aussi aider autrement</h2>
                <p>Chaque geste compte, même sans don financier.</p>
            </div>

            <div class="help-grid">
                <article class="help-card">
                    <h3>📣 Partager</h3>
                    <p>Parlez d’EGEE autour de vous : un simple partage peut aider énormément.</p>
                </article>

                <article class="help-card">
                    <h3>🤝 S’engager</h3>
                    <p>Vous souhaitez contribuer ? Écrivez-nous, on vous oriente selon vos disponibilités.</p>
                </article>

                <article class="help-card">
                    <h3>🎯 Participer</h3>
                    <p>Événements, ateliers, rencontres : rejoignez une action près de chez vous.</p>
                </article>
            </div>

            <div class="center-cta">
                <a class="btn btn-outline btn-large" href="page_contact.php">Proposer mon aide</a>
            </div>
        </div>
    </section>

</main>

<?php include('footer.php'); ?>

<script src="assets/js/menuBuger.js"></script>
</body>
</html>
