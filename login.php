<?php
require_once "init.php";

if (!empty($_SESSION['auth'])) {
    header("Location: dashboard.php");
    exit;
}

$err = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

$pageTitle = "Connexion – EGEE";
include "header.php";
?>

<section class="login-wrap">
    <div class="container">

        <div class="login-header">
            <h1>Se connecter</h1>
            <p class="split-note">Remplis le bon formulaire selon ton rôle.</p>

        </div>

        <?php if (!empty($err)): ?>
            <div class="alert"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="login-grid">


            <div class="login-card">
                <div class="login-badge badge-user">Utilisateur</div>
                <h3>Accès utilisateur</h3>
                <p class="muted">Connexion via email + mot de passe.</p>

                <form class="form" method="post" action="authenticate.php" autocomplete="on">
                    <input type="hidden" name="role" value="user">

                    <div class="field">
                        <label for="user_email">Email</label>
                        <input id="user_email" name="identifier" type="email" required placeholder="ex: john@demo.com">
                    </div>

                    <div class="field">
                        <label for="user_password">Mot de passe</label>
                        <input id="user_password" name="password" type="password" required placeholder="••••••••">
                    </div>

                    <button class="btn btn-primary" type="submit">Connexion utilisateur</button>
                </form>
                <p class = "login-signin">
                    Pas de compte ? <a href="register.php">Inscris-toi</a>
                </p>
            </div>


            <div class="login-card">
                <div class="login-badge badge-admin">Administrateur</div>
                <h3>Accès admin</h3>
                <p class="muted">Connexion via email admin + mot de passe.</p>

                <form class="form" method="post" action="authenticate.php" autocomplete="on">
                    <input type="hidden" name="role" value="admin">

                    <div class="field">
                        <label for="admin_email">Email</label>
                        <input id="admin_email" name="identifier" type="email" required placeholder="ex: admin@egee.fr">
                    </div>

                    <div class="field">
                        <label for="admin_password">Mot de passe</label>
                        <input id="admin_password" name="password" type="password" required placeholder="••••••••">
                    </div>

                    <button class="btn btn-outline" type="submit">Connexion admin</button>
                </form>
            </div>

        </div>
    </div>
</section>

<?php include "footer.php"; ?>
