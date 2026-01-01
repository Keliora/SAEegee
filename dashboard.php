<?php
require_once "init.php";

if (empty($_SESSION['auth'])) {
    $_SESSION['login_error'] = "Vous devez être connecté.";
    header("Location: login.php");
    exit;
}

$user = $_SESSION['auth'];

$pageTitle = "Dashboard – EGEE";
include "header.php";
?>

<section class="login-wrap">
    <div class="container">
        <div class="login-card">
            <h2>Dashboard</h2>
            <p>Connecté : <strong><?= htmlspecialchars($user['prenom']." ".$user['nom']) ?></strong></p>
            <p>Email : <strong><?= htmlspecialchars($user['email']) ?></strong></p>
            <p>Rôle : <strong><?= htmlspecialchars($user['role']) ?></strong></p>

            <?php if ($user['role'] === 'ADMIN'): ?>
                <hr style="margin: 1rem 0;">
                <h3>Menu Admin</h3>
                <ul>
                    <li><a href="page_presse.php">Gérer la presse</a></li>
                    <li><a href="page_partenaires.php">Gérer les partenaires</a></li>

                </ul>
            <?php endif; ?>

            <a class="btn btn-outline" href="logout.php">Se déconnecter</a>
        </div>
    </div>
</section>

<?php include "footer.php"; ?>
