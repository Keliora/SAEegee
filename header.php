<?php
// On sait jamais vous voulez rajouter des variables pour le header mettez les là

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$auth = $_SESSION['auth'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'EGEE – Accompagner, Former, Transmettre'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="newcss.css">
    <link rel="icon" type="image/png" href="img/favicon.png">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">
            <img src="assets/image/logo_EGEE.png" alt="EGEE">
        </a>

        <nav class="main-nav">
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li><a href="page_presse.php">La Presse</a></li>
                <li><a href="page_partenaires.php">Nos partenaires</a></li>
                <li><a href="page_contact.php">Contact</a></li>
                <li><a href="page_don.php">Faire un Don</a></li>
            </ul>
        </nav>

        <div class="header-cta">


            <?php if (!$auth): ?>
                <a href="login.php" class="btn btn-connect">Se connecter</a>
                <a href="page_don.php" class="btn btn-donate">Faire un don</a>
            <?php else: ?>
                <span class="header-user">
            Connecté : <strong><?= htmlspecialchars(trim(($auth['prenom'] ?? '') . ' ' . ($auth['nom'] ?? '')), ENT_QUOTES, 'UTF-8') ?></strong>
        </span>

                <?php if (($auth['role'] ?? 'USER') === 'ADMIN'): ?>
                    <a href="admin/dashboard.php" class="btn btn-connect">Dashboard</a>
                <?php endif; ?>
                <?php if (($auth['role'] ?? 'USER') === 'USER'): ?>
                    <a href="user/dashboard.php" class="btn btn-connect">Dashboard</a>
                <?php endif; ?>

                <a href="logout.php" class="btn btn-connect">Se déconnecter</a>
            <?php endif; ?>
        </div>


        <button class="burger" aria-label="Ouvrir le menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>