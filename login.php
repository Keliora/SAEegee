<?php
require_once "db.php";
session_start();


if (!empty($_SESSION['auth'])) {
    // Déjà connecté
    header('Location: dashboard.php');
    exit;
}

$err = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion</title>
    <link rel="stylesheet" href="newcss.css">


</head>

<body>

<?php
$pageTitle = "Accueil - EGEE"; // Optionnel : titre dynamique
include('header.php');
?>

<section class="hero">
    <div class="container hero-inner">
        <div>
            <div class="hero-tag">Accès sécurisé</div>
            <h1>Connexion</h1>
            <p class="hero-subtitle">Choisis ton espace : <strong>Utilisateur</strong> ou <strong>Administrateur</strong>. Tes identifiants sont vérifiés côté serveur.</p>

            <div class="hero-meta">
                <span>PHP + Sessions</span>
                <span>Style cohérent</span>
                <span>Deux formulaires</span>
            </div>
        </div>

        <div class="hero-card">
            <h2>Bon à savoir</h2>
            <ul>
                <li>✅ Redirection après connexion</li>
                <li>✅ Gestion d’erreur propre</li>
                <li>✅ Compatible mobile</li>
            </ul>
            <p class="hero-note">Astuce : tu peux brancher une base de données ensuite (PDO).</p>
        </div>
    </div>
</section>

<div class="bandeau">Connexion utilisateur & administrateur</div>

<section class="login-wrap">
    <div class="container">
        <div class="login-header">
            <h1>Se connecter</h1>
            <p class="split-note">Remplis le bon formulaire selon ton rôle.</p>
        </div>

        <?php if ($err): ?>
            <div class="alert"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="login-grid">

            <!-- Utilisateur -->
            <div class="card login-card">
                <div class="login-badge badge-user">Utilisateur</div>
                <h3>Accès utilisateur</h3>
                <p class="muted">Connexion classique (ex: email + mot de passe).</p>

                <form class="form" method="post" action="authenticate.php" autocomplete="on">
                    <input type="hidden" name="role" value="user">

                    <div class="field">
                        <label for="user_email">Email</label>
                        <input id="user_email" name="identifier" type="email" required placeholder="ex: adam@email.com">
                    </div>

                    <div class="field">
                        <label for="user_password">Mot de passe</label>
                        <input id="user_password" name="password" type="password" required placeholder="••••••••">
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Connexion</button>
                        <a class="btn btn-outline" href="#">Mot de passe oublié</a>
                    </div>
                </form>
            </div>

            <!-- Admin -->
            <div class="card login-card">
                <div class="login-badge badge-admin">Administrateur</div>
                <h3>Accès admin</h3>
                <p class="muted">Réservé à l’administration (ex: identifiant + mot de passe).</p>

                <form class="form" method="post" action="authenticate.php" autocomplete="on">
                    <input type="hidden" name="role" value="admin">

                    <div class="field">
                        <label for="admin_login">Identifiant</label>
                        <input id="admin_login" name="identifier" type="text" required placeholder="ex: admin">
                    </div>

                    <div class="field">
                        <label for="admin_password">Mot de passe</label>
                        <input id="admin_password" name="password" type="password" required placeholder="••••••••">
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-donate" type="submit">Connexion admin</button>
                        <span class="muted">Accès restreint</span>
                    </div>
                </form>
            </div>

        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="container footer-bottom">
        © <?= date('Y') ?> — Connexion
    </div>
</footer>

</body>
</html>
