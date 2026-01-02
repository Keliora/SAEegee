<?php
session_start();

$err = $_SESSION['register_error'] ?? '';
$old = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_error'], $_SESSION['register_old']);

$pageTitle = "Inscription — EGEE";
include "header.php";
?>

<section class="hero">
    <div class="container hero-inner" style="grid-template-columns: 1fr;">
        <div class="hero-text">
            <h1>Inscription</h1>
            <p class="hero-subtitle">Crée ton compte bénévole (rôle USER).</p>
        </div>
    </div>
</section>

<div class="bandeau">Créer un compte</div>

<section class="login-wrap">
    <div class="container">
        <div class="card login-card">
            <h3>Inscris-toi</h3>
            <p class="muted">Les champs avec * sont obligatoires.</p>

            <?php if ($err): ?>
                <div class="alert"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form class="form" method="post" action="register_handler.php" autocomplete="on">

                <div class="field">
                    <label for="prenom">Prénom *</label>
                    <input id="prenom" name="prenom" required value="<?= htmlspecialchars($old['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="nom">Nom *</label>
                    <input id="nom" name="nom" required value="<?= htmlspecialchars($old['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="email">Email *</label>
                    <input id="email" name="email" type="email" required value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="password">Mot de passe *</label>
                    <input id="password" name="password" type="password" required placeholder="Min 8 caractères">
                </div>

                <div class="field">
                    <label for="password2">Confirmer le mot de passe *</label>
                    <input id="password2" name="password2" type="password" required>
                </div>

                <hr style="margin: 1rem 0;">

                <div class="field">
                    <label for="ville">Ville</label>
                    <input id="ville" name="ville" value="<?= htmlspecialchars($old['ville'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="field">
                    <label for="numero">Téléphone</label>
                    <input id="numero" name="numero" value="<?= htmlspecialchars($old['numero'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Créer mon compte</button>
                    <a class="btn btn-outline" href="login.php">Retour connexion</a>
                </div>

            </form>
        </div>
    </div>
</section>

<?php include "footer.php"; ?>
