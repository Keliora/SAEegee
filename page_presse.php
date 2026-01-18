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
require_once __DIR__ . "/init.php"; // important pour $pdo
$pageTitle = "Actualités - EGEE";
include('header.php');

// Sécurité HTML
function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$st = $pdo->query("
    SELECT
        IdPresse,
        TitrePresse,
        ResumePresse,
        AuteurPresse,
        DateHeurePublication,
        LienSource,
        Fichier
    FROM Presse
    ORDER BY COALESCE(DateHeurePublication,'1970-01-01 00:00:00') DESC, IdPresse DESC
");
$articles = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>

<main>
    <section class="hero">
        <div class="container hero-inner hero-single">
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

    <div class="container press-page">
        <!-- Barre recherche + filtres -->
        <div class="recherche_article">
            <input class="recherche_input" placeholder="Rechercher..." aria-label="Rechercher un article">
            <button class="btn btn-secondary" type="button" aria-label="Rechercher">🔍</button>

            <select id="filtre-select" class="btn btn-outline filtre_select">
                <option value="default" disabled selected>▼ Filtrer</option>
                <option value="az">Alphabétique (A-Z)</option>
                <option value="za">Alphabétique (Z-A)</option>
                <option value="date-desc">Plus récents</option>
                <option value="vues-desc">Plus vus</option>
            </select>
        </div>

        <!-- ARTICLES -->
        <section class="large_article_container">

            <?php if (!$articles): ?>
                <p>Aucun article n’a encore été publié.</p>
            <?php else: ?>

                <?php foreach ($articles as $a):

                    // date pour le JS
                    $dataDate = '';
                    if (!empty($a['DateHeurePublication'])) {
                        $dataDate = substr($a['DateHeurePublication'], 0, 10);
                    }

                    // vues (si tu ajoutes la colonne plus tard)
                    $vues = 0;

                    // image
                    $image = !empty($a['Fichier'])
                            ? h($a['Fichier'])
                            : "assets/image/article_placeholder.png";
                    ?>

                    <article
                            class="card press-article"
                            data-date="<?= h($dataDate) ?>"
                            data-vues="<?= (int)$vues ?>"
                    >
                        <img
                                src="<?= $image ?>"
                                alt="<?= h($a['TitrePresse']) ?>"
                                class="press-img"
                        >

                        <div class="press-body">
                            <h3><?= h($a['TitrePresse']) ?></h3>

                            <p class="press-desc">
                                <?php if (!empty($a['DateHeurePublication'])): ?>
                                    <i><?= h(date('d/m/Y', strtotime($a['DateHeurePublication']))) ?></i> –
                                <?php endif; ?>

                                <?= h($a['ResumePresse']) ?>
                            </p>

                            <?php if (!empty($a['LienSource'])): ?>
                                <a
                                        href="<?= h($a['LienSource']) ?>"
                                        class="btn btn-outline btn-small press-btn"
                                        target="_blank"
                                        rel="noopener"
                                >
                                    Lire l’article
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>
</main>


<?php include('footer.php'); ?>

<script src="assets/js/menuBuger.js"></script>
<script src="assets/js/rechercheArticle.js"></script>
<script src="assets/js/filtre.js"></script>
</body>
</html>
