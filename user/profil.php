<?php
require_once __DIR__ . "/../init.php";
if (empty($_SESSION['auth'])) {
    $_SESSION['login_error'] = "Vous devez être connecté.";
    header("Location: ../login.php");
    exit;
}
if (($_SESSION['auth']['role'] ?? '') !== 'USER') {
    header("Location: ../admin/dashboard.php");
    exit;
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$id = (int)$_SESSION['auth']['id_benevole'];
$success = null;
$error   = null;

/* ===== UPDATE PROFIL ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ville   = trim($_POST['ville'] ?? '');
    $comp    = trim($_POST['competence'] ?? '');
    $dom     = trim($_POST['domaine'] ?? '');
    $regime  = trim($_POST['regime'] ?? '');
    $prof    = trim($_POST['profession'] ?? '');

    $newPwd  = $_POST['new_password'] ?? '';
    $pwdConf = $_POST['confirm_password'] ?? '';

    try {
        if ($newPwd !== '') {
            if (strlen($newPwd) < 6) {
                throw new Exception("Le mot de passe doit contenir au moins 6 caractères.");
            }
            if ($newPwd !== $pwdConf) {
                throw new Exception("Les mots de passe ne correspondent pas.");
            }

            $hash = password_hash($newPwd, PASSWORD_DEFAULT);

            $sql = "
                UPDATE Benevole SET
                    VilleBenevole = :ville,
                    CompetenceBenevole = :comp,
                    DomaineIntervention = :dom,
                    RegimeAlimentaire = :reg,
                    ProfessionBenevole = :prof,
                    Password = :pwd
                WHERE IdBenevole = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ville' => $ville,
                ':comp'  => $comp,
                ':dom'   => $dom,
                ':reg'   => $regime,
                ':prof'  => $prof,
                ':pwd'   => $hash,
                ':id'    => $id
            ]);
        } else {
            $sql = "
                UPDATE Benevole SET
                    VilleBenevole = :ville,
                    CompetenceBenevole = :comp,
                    DomaineIntervention = :dom,
                    RegimeAlimentaire = :reg,
                    ProfessionBenevole = :prof
                WHERE IdBenevole = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ville' => $ville,
                ':comp'  => $comp,
                ':dom'   => $dom,
                ':reg'   => $regime,
                ':prof'  => $prof,
                ':id'    => $id
            ]);
        }

        $success = "Profil mis à jour avec succès.";

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}


$stmt = $pdo->prepare("
    SELECT PrenomBenevole, NomBenevole, Email,
           VilleBenevole, CompetenceBenevole,
           DomaineIntervention, RegimeAlimentaire,
           ProfessionBenevole, DateInscriptionBenevole
    FROM Benevole
    WHERE IdBenevole = :id
");
$stmt->execute([':id'=>$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mon profil • EGEE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../newcss.css">
</head>
<body>

<div class="dash-shell">


    <aside class="dash-side">
        <div class="dash-side-top">
            <div class="dash-brand">
                <div class="dash-avatar">U</div>
                <div>
                    <div class="dash-brand-title">Mon espace</div>
                    <div class="dash-brand-sub">BÉNÉVOLE</div>
                </div>
            </div>
        </div>

        <nav class="dash-menu">
            <a class="dash-link" href="dashboard.php">Tableau de bord</a>
            <a class="dash-link is-active" href="profil.php">Mon profil</a>
            <a class="dash-link" href="missions.php">Mes missions</a>
            <a class="dash-link" href="evenements.php">Mes événements</a>
            <a class="dash-link" href="../logout.php">Déconnexion</a>
        </nav>
    </aside>


    <main class="dash-main">

        <header class="dash-topbar">
            <div>
                <h1 class="dash-h1">Mon profil</h1>
                <p class="dash-sub">Gérer mes informations personnelles</p>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="dash-alert dash-alert-success"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="dash-alert dash-alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" class="dash-card" style="max-width:700px;">
            <div class="dash-card-head">
                <div class="dash-card-title">Informations personnelles</div>
            </div>

            <div class="dash-card-body">

                <p><strong>Nom :</strong> <?= h($user['PrenomBenevole'].' '.$user['NomBenevole']) ?></p>
                <p><strong>Email :</strong> <?= h($user['Email']) ?></p>
                <p><strong>Inscrit depuis :</strong> <?= h($user['DateInscriptionBenevole']) ?></p>

                <div class="dash-form-grid">
                    <div>
                        <label>Ville</label>
                        <input class="dash-input" name="ville" value="<?= h($user['VilleBenevole']) ?>">
                    </div>

                    <div>
                        <label>Profession</label>
                        <input class="dash-input" name="profession" value="<?= h($user['ProfessionBenevole']) ?>">
                    </div>

                    <div>
                        <label>Compétences</label>
                        <input class="dash-input" name="competence" value="<?= h($user['CompetenceBenevole']) ?>">
                    </div>

                    <div>
                        <label>Domaine d’intervention</label>
                        <input class="dash-input" name="domaine" value="<?= h($user['DomaineIntervention']) ?>">
                    </div>

                    <div>
                        <label>Régime alimentaire</label>
                        <input class="dash-input" name="regime" value="<?= h($user['RegimeAlimentaire']) ?>">
                    </div>
                </div>

                <hr style="margin:20px 0">

                <h3>Changer le mot de passe</h3>

                <div class="dash-form-grid">
                    <div>
                        <label>Nouveau mot de passe</label>
                        <input type="password" class="dash-input" name="new_password">
                    </div>

                    <div>
                        <label>Confirmer le mot de passe</label>
                        <input type="password" class="dash-input" name="confirm_password">
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <button class="dash-btn dash-btn-primary" type="submit">
                        Enregistrer les modifications
                    </button>
                </div>

            </div>
        </form>

    </main>
</div>


</body>
</html>
