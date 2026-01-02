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

$idBenevole = (int)$_SESSION['auth']['id_benevole'];


$profil = $pdo->prepare("
    SELECT PrenomBenevole, NomBenevole, Email,
           VilleBenevole, CompetenceBenevole,
           DomaineIntervention, DateInscriptionBenevole
    FROM Benevole
    WHERE IdBenevole = :id
");
$profil->execute([':id'=>$idBenevole]);
$user = $profil->fetch(PDO::FETCH_ASSOC);

$missions = $pdo->prepare("
    SELECT m.TitreMission, m.LieuMission, m.DateHeureDebut, m.DateHeureFin,
           p.RoleBenevole, p.Duree
    FROM Participer p
    JOIN Mission m ON m.IdMission = p.IdMission
    WHERE p.IdBenevole = :id
    ORDER BY m.DateHeureDebut DESC
");
$missions->execute([':id'=>$idBenevole]);
$missions = $missions->fetchAll(PDO::FETCH_ASSOC);

/* ===== EVENEMENTS DU USER ===== */
$events = $pdo->prepare("
    SELECT e.NomEvenement, e.DateEvenement, e.HeureEvenement,
           a.Role, a.EstPresent
    FROM Assister a
    JOIN Evenement e ON e.IdEvenement = a.IdEvenement
    WHERE a.IdBenevole = :id
    ORDER BY e.DateEvenement DESC
");
$events->execute([':id'=>$idBenevole]);
$events = $events->fetchAll(PDO::FETCH_ASSOC);

$nbMissions = count($missions);
$nbEvents   = count($events);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mon espace • EGEE</title>
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
            <a class="dash-link is-active" href="dashboard.php">Tableau de bord</a>
            <a class="dash-link" href="profil.php">Mon profil</a>
            <a class="dash-link" href="missions.php">Mes missions</a>
            <a class="dash-link" href="evenements.php">Mes événements</a>
            <a class="dash-link" href="../logout.php">Déconnexion</a>
        </nav>
    </aside>


    <main class="dash-main">

        <header class="dash-topbar">
            <div>
                <h1 class="dash-h1">Bonjour <?= h($user['PrenomBenevole']) ?> 👋</h1>
                <p class="dash-sub">Bienvenue dans ton espace personnel</p>
            </div>
        </header>


        <section class="dash-kpis">
            <div class="dash-kpi">
                <div class="dash-kpi-title">Missions</div>
                <div class="dash-kpi-value"><?= $nbMissions ?></div>
            </div>
            <div class="dash-kpi">
                <div class="dash-kpi-title">Événements</div>
                <div class="dash-kpi-value"><?= $nbEvents ?></div>
            </div>
        </section>


        <section class="dash-card">
            <div class="dash-card-head">
                <div class="dash-card-title">Mon profil</div>
            </div>
            <div class="dash-card-body">
                <p><strong>Nom :</strong> <?= h($user['PrenomBenevole'].' '.$user['NomBenevole']) ?></p>
                <p><strong>Email :</strong> <?= h($user['Email']) ?></p>
                <p><strong>Ville :</strong> <?= h($user['VilleBenevole'] ?? '—') ?></p>
                <p><strong>Compétences :</strong> <?= h($user['CompetenceBenevole'] ?? '—') ?></p>
                <p><strong>Domaine :</strong> <?= h($user['DomaineIntervention'] ?? '—') ?></p>
                <p><strong>Inscrit depuis :</strong> <?= h($user['DateInscriptionBenevole']) ?></p>
            </div>
        </section>


        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Mes missions</div>
            </div>
            <div class="dash-tablewrap">
                <table class="dash-table">
                    <thead>
                    <tr>
                        <th>Mission</th>
                        <th>Lieu</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Rôle</th>
                        <th>Durée</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($missions)): ?>
                        <tr><td colspan="6" class="dash-td-empty">Aucune mission</td></tr>
                    <?php else: foreach($missions as $m): ?>
                        <tr>
                            <td><?= h($m['TitreMission']) ?></td>
                            <td><?= h($m['LieuMission']) ?></td>
                            <td><?= h($m['DateHeureDebut']) ?></td>
                            <td><?= h($m['DateHeureFin']) ?></td>
                            <td><?= h($m['RoleBenevole']) ?></td>
                            <td><?= h($m['Duree']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>


        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Mes événements</div>
            </div>
            <div class="dash-tablewrap">
                <table class="dash-table">
                    <thead>
                    <tr>
                        <th>Événement</th>
                        <th>Date</th>
                        <th>Rôle</th>
                        <th>Présent</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($events)): ?>
                        <tr><td colspan="4" class="dash-td-empty">Aucun événement</td></tr>
                    <?php else: foreach($events as $e): ?>
                        <tr>
                            <td><?= h($e['NomEvenement']) ?></td>
                            <td><?= h($e['DateEvenement'].' '.$e['HeureEvenement']) ?></td>
                            <td><?= h($e['Role']) ?></td>
                            <td><?= $e['EstPresent'] ? '✅' : '❌' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</div>

</body>
</html>
