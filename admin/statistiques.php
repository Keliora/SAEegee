<?php
require_once __DIR__ . "/../init.php";

if (empty($_SESSION['auth']) || ($_SESSION['auth']['role'] ?? '') !== 'ADMIN') {
    $_SESSION['login_error'] = "Accès réservé à l'administration.";
    header("Location: ../login.php");
    exit;
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$adminName  = trim(($_SESSION['auth']['prenom'] ?? 'System').' '.($_SESSION['auth']['nom'] ?? 'Admin'));
$adminEmail = $_SESSION['auth']['email'] ?? 'admin@site.com';

$kpi = [
        'benevoles'      => (int)$pdo->query("SELECT COUNT(*) FROM Benevole")->fetchColumn(),
        'evenements'     => (int)$pdo->query("SELECT COUNT(*) FROM Evenement")->fetchColumn(),
        'missions'       => (int)$pdo->query("SELECT COUNT(*) FROM Mission")->fetchColumn(),
        'partenaires'    => (int)$pdo->query("SELECT COUNT(*) FROM Partenaire")->fetchColumn(),
        'financementSum' => (float)$pdo->query("SELECT COALESCE(SUM(MontantFinancement),0) FROM Financement")->fetchColumn(),
];

$kpi['benevoles_30j'] = (int)$pdo->query("
    SELECT COUNT(*) FROM Benevole
    WHERE DateInscriptionBenevole IS NOT NULL
      AND DateInscriptionBenevole >= (CURDATE() - INTERVAL 30 DAY)
")->fetchColumn();

$kpi['events_a_venir'] = (int)$pdo->query("
    SELECT COUNT(*) FROM Evenement
    WHERE DateEvenement IS NOT NULL AND DateEvenement >= CURDATE()
")->fetchColumn();

$kpi['missions_a_venir'] = (int)$pdo->query("
    SELECT COUNT(*) FROM Mission
    WHERE DateHeureDebut IS NOT NULL AND DateHeureDebut >= NOW()
")->fetchColumn();


$presence = $pdo->query("
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN EstPresent = 1 THEN 1 ELSE 0 END) AS presents
    FROM Assister
")->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'presents'=>0];
$totalAssister = (int)($presence['total'] ?? 0);
$presents      = (int)($presence['presents'] ?? 0);
$tauxPresence  = $totalAssister > 0 ? round(($presents / $totalAssister) * 100, 1) : 0.0;


$missionsAVenir = $pdo->query("
    SELECT IdMission, TitreMission, DateHeureDebut, DateHeureFin, NbBenevolesAttendus, CategorieMission, LieuMission
    FROM Mission
    WHERE DateHeureDebut IS NOT NULL AND DateHeureDebut >= NOW()
    ORDER BY DateHeureDebut ASC
    LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC) ?: [];


$dernierBenevoles = $pdo->query("
    SELECT IdBenevole, PrenomBenevole, NomBenevole, VilleBenevole, DateInscriptionBenevole
    FROM Benevole
    ORDER BY COALESCE(DateInscriptionBenevole,'1970-01-01') DESC, IdBenevole DESC
    LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC) ?: [];


$topVilles = $pdo->query("
    SELECT COALESCE(NULLIF(TRIM(VilleBenevole), ''), 'Non renseigné') AS Ville, COUNT(*) AS nb
    FROM Benevole
    GROUP BY Ville
    ORDER BY nb DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC) ?: [];


$finByYear = $pdo->query("
    SELECT AnneeFinancement AS annee, COALESCE(SUM(MontantFinancement),0) AS total
    FROM Financement
    GROUP BY AnneeFinancement
    ORDER BY AnneeFinancement DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC) ?: [];


$missionsByCat = $pdo->query("
    SELECT COALESCE(NULLIF(TRIM(CategorieMission), ''), 'Non renseigné') AS categorie, COUNT(*) AS nb
    FROM Mission
    GROUP BY categorie
    ORDER BY nb DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC) ?: [];


$remplissage = $pdo->query("
    SELECT
      m.IdMission,
      m.TitreMission,
      m.NbBenevolesAttendus,
      COUNT(p.IdBenevole) AS inscrits
    FROM Mission m
    LEFT JOIN Participer p ON p.IdMission = m.IdMission
    GROUP BY m.IdMission, m.TitreMission, m.NbBenevolesAttendus
    ORDER BY (CASE WHEN m.NbBenevolesAttendus IS NULL OR m.NbBenevolesAttendus=0 THEN 0 ELSE (COUNT(p.IdBenevole)/m.NbBenevolesAttendus) END) DESC,
             m.IdMission DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Admin • Statistiques</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../newcss.css">
</head>
<body>

<div class="dash-shell">

    <aside class="dash-side">
        <div class="dash-side-top">
            <div class="dash-brand">
                <div class="dash-avatar">E</div>
                <div>
                    <div class="dash-brand-title">EGEE Admin</div>
                    <div class="dash-brand-sub">ADMIN</div>
                </div>
            </div>
        </div>

        <nav class="dash-menu">
            <div class="dash-menu-section">DASHBOARD</div>
            <a class="dash-link" href="dashboard.php">Vue d'ensemble</a>
            <a class="dash-link is-active" href="statistiques.php">Statistiques</a>

            <div class="dash-menu-section">GESTION</div>
            <a class="dash-link" href="benevoles.php">Bénévoles</a>
            <a class="dash-link" href="missions.php">Missions</a>
            <a class="dash-link" href="evenements.php">Événements</a>
            <a class="dash-link" href="presse.php">Presse</a>
            <a class="dash-link" href="regions.php">Régions</a>
            <a class="dash-link" href="partenaires.php">Partenaires</a>
            <a class="dash-link" href="financements.php">Dons / Financements</a>

            <div class="dash-menu-section">OUTILS</div>
            <a class="dash-link" href="export.php?type=benevoles">Export CSV • Bénévoles</a>
            <a class="dash-link" href="export.php?type=missions">Export CSV • Missions</a>
            <a class="dash-link" href="export.php?type=evenements">Export CSV • Événements</a>

            <div class="dash-menu-section">SESSION</div>
            <a class="dash-link" href="../logout.php">Déconnexion</a>
        </nav>

        <div class="dash-side-footer">
            <div class="dash-usercard">
                <div class="dash-usericon">S</div>
                <div class="dash-usertext">
                    <div class="dash-username"><?= h($adminName) ?></div>
                    <div class="dash-usermail"><?= h($adminEmail) ?></div>
                </div>
            </div>
        </div>
    </aside>

    <main class="dash-main">

        <header class="dash-topbar">
            <div>
                <h1 class="dash-h1">Statistiques</h1>
                <p class="dash-sub">Indicateurs clés + tendances (utile SAE/Data).</p>
            </div>
            <div class="dash-top-actions stats-top-actions">
                <a class="dash-btn" href="export.php?type=benevoles">Export bénévoles</a>
                <a class="dash-btn" href="export.php?type=missions">Export missions</a>
                <a class="dash-btn" href="export.php?type=evenements">Export événements</a>
            </div>
        </header>

        <section class="stats-kpi-grid">
            <div class="dash-card stats-kpi-card">
                <div class="stats-kpi-label">Bénévoles</div>
                <div class="stats-kpi-value"><?= (int)$kpi['benevoles'] ?></div>
                <div class="stats-kpi-sub">+<?= (int)$kpi['benevoles_30j'] ?> (30 jours)</div>
            </div>
            <div class="dash-card stats-kpi-card">
                <div class="stats-kpi-label">Événements</div>
                <div class="stats-kpi-value"><?= (int)$kpi['evenements'] ?></div>
                <div class="stats-kpi-sub"><?= (int)$kpi['events_a_venir'] ?> à venir</div>
            </div>
            <div class="dash-card stats-kpi-card">
                <div class="stats-kpi-label">Missions</div>
                <div class="stats-kpi-value"><?= (int)$kpi['missions'] ?></div>
                <div class="stats-kpi-sub"><?= (int)$kpi['missions_a_venir'] ?> à venir</div>
            </div>
            <div class="dash-card stats-kpi-card">
                <div class="stats-kpi-label">Partenaires</div>
                <div class="stats-kpi-value"><?= (int)$kpi['partenaires'] ?></div>
                <div class="stats-kpi-sub">référencés</div>
            </div>
            <div class="dash-card stats-kpi-card">
                <div class="stats-kpi-label">Financements</div>
                <div class="stats-kpi-money">
                    <?= number_format((float)$kpi['financementSum'],2,',',' ') ?> €
                </div>
                <div class="stats-kpi-sub">total cumulé</div>
            </div>
        </section>

        <div class="stats-spacer-12"></div>

        <section class="stats-grid-2">
            <div class="dash-card dash-tablecard">
                <div class="dash-card-head">
                    <div class="dash-card-title">Missions à venir</div>
                    <div class="dash-card-meta">Prochaines 7</div>
                </div>
                <div class="dash-card-body">
                    <div class="dash-tablewrap">
                        <table class="dash-table">
                            <thead>
                            <tr>
                                <th>ID</th><th>Titre</th><th>Catégorie</th><th>Début</th><th>Lieu</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if(empty($missionsAVenir)): ?>
                                <tr><td colspan="5" class="dash-td-empty">Aucune mission à venir.</td></tr>
                            <?php else: foreach($missionsAVenir as $m): ?>
                                <tr>
                                    <td><?= (int)$m['IdMission'] ?></td>
                                    <td><?= h($m['TitreMission']) ?></td>
                                    <td><?= h($m['CategorieMission'] ?? '—') ?></td>
                                    <td><?= h($m['DateHeureDebut'] ?? '—') ?></td>
                                    <td><?= h($m['LieuMission'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="dash-card stats-card-pad">
                <div class="stats-presence-row">
                    <div>
                        <div class="stats-presence-title">Taux de présence (événements)</div>
                        <div class="stats-presence-sub">Assister.EstPresent</div>
                    </div>
                    <div class="stats-presence-value"><?= h($tauxPresence) ?>%</div>
                </div>

                <div class="stats-progress">
                    <div class="stats-progress-bar" style="width:<?= (float)$tauxPresence ?>%;"></div>
                </div>

                <div class="stats-presence-foot">
                    Présents: <strong><?= (int)$presents ?></strong> / Total: <strong><?= (int)$totalAssister ?></strong>
                </div>

                <div class="stats-spacer-14"></div>
                <div class="stats-topvilles-title">Top villes bénévoles</div>

                <div class="stats-topvilles-wrap">
                    <?php if(empty($topVilles)): ?>
                        <div class="stats-muted">Aucune donnée.</div>
                    <?php else: foreach($topVilles as $tv): ?>
                        <?php
                        $pct = $kpi['benevoles'] > 0 ? round(($tv['nb']/$kpi['benevoles'])*100, 1) : 0;
                        ?>
                        <div class="stats-topville-item">
                            <div class="stats-topville-row">
                                <div><?= h($tv['Ville']) ?></div>
                                <div class="stats-muted"><?= (int)$tv['nb'] ?> (<?= h($pct) ?>%)</div>
                            </div>
                            <div class="stats-progress-sm">
                                <div class="stats-progress-bar-green" style="width:<?= (float)$pct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </section>

        <div class="stats-spacer-12"></div>

        <section class="stats-grid-3">
            <div class="dash-card dash-tablecard">
                <div class="dash-card-head">
                    <div class="dash-card-title">Derniers bénévoles</div>
                    <div class="dash-card-meta">7 derniers</div>
                </div>
                <div class="dash-card-body">
                    <div class="dash-tablewrap">
                        <table class="dash-table">
                            <thead><tr><th>ID</th><th>Nom</th><th>Ville</th><th>Inscription</th></tr></thead>
                            <tbody>
                            <?php if(empty($dernierBenevoles)): ?>
                                <tr><td colspan="4" class="dash-td-empty">Aucun bénévole.</td></tr>
                            <?php else: foreach($dernierBenevoles as $b): ?>
                                <tr>
                                    <td><?= (int)$b['IdBenevole'] ?></td>
                                    <td><?= h(($b['PrenomBenevole'] ?? '').' '.($b['NomBenevole'] ?? '')) ?></td>
                                    <td><?= h($b['VilleBenevole'] ?? '—') ?></td>
                                    <td><?= h($b['DateInscriptionBenevole'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="dash-card stats-card-pad">
                <div class="stats-presence-title">Financements par année</div>
                <div class="stats-presence-sub">Somme des montants</div>
                <div class="stats-spacer-10"></div>

                <?php if(empty($finByYear)): ?>
                    <div class="stats-muted">Aucune donnée.</div>
                <?php else:
                    $maxYear = 0.0;
                    foreach($finByYear as $fy){ $maxYear = max($maxYear, (float)$fy['total']); }
                    foreach($finByYear as $fy):
                        $pct = $maxYear > 0 ? round(((float)$fy['total']/$maxYear)*100,1) : 0;
                        ?>
                        <div class="stats-fin-item">
                            <div class="stats-fin-row">
                                <div><?= h($fy['annee'] ?? '—') ?></div>
                                <div class="stats-muted"><?= number_format((float)$fy['total'],2,',',' ') ?> €</div>
                            </div>
                            <div class="stats-progress-sm">
                                <div class="stats-progress-bar-amber" style="width:<?= (float)$pct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
            </div>

            <div class="dash-card stats-card-pad">
                <div class="stats-presence-title">Missions par catégorie</div>
                <div class="stats-presence-sub">Répartition</div>
                <div class="stats-spacer-10"></div>

                <?php if(empty($missionsByCat)): ?>
                    <div class="stats-muted">Aucune donnée.</div>
                <?php else:
                    $maxCat = 0;
                    foreach($missionsByCat as $mc){ $maxCat = max($maxCat, (int)$mc['nb']); }
                    foreach($missionsByCat as $mc):
                        $pct = $maxCat > 0 ? round(((int)$mc['nb']/$maxCat)*100,1) : 0;
                        ?>
                        <div class="stats-cat-item">
                            <div class="stats-cat-row">
                                <div><?= h($mc['categorie']) ?></div>
                                <div class="stats-muted"><?= (int)$mc['nb'] ?></div>
                            </div>
                            <div class="stats-progress-sm">
                                <div class="stats-progress-bar-purple" style="width:<?= (float)$pct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
            </div>
        </section>

        <div class="stats-spacer-12"></div>

        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Remplissage des missions</div>
                <div class="dash-card-meta">Participer vs NbBenevolesAttendus</div>
            </div>
            <div class="dash-card-body">
                <div class="dash-tablewrap">
                    <table class="dash-table">
                        <thead><tr><th>ID</th><th>Titre</th><th>Inscrits</th><th>Attendus</th><th>Taux</th></tr></thead>
                        <tbody>
                        <?php if(empty($remplissage)): ?>
                            <tr><td colspan="5" class="dash-td-empty">Aucune mission.</td></tr>
                        <?php else: foreach($remplissage as $r):
                            $att = (int)($r['NbBenevolesAttendus'] ?? 0);
                            $ins = (int)($r['inscrits'] ?? 0);
                            $pct = ($att > 0) ? round(($ins/$att)*100,1) : 0;
                            $pctShow = min(100, max(0, $pct));
                            ?>
                            <tr>
                                <td><?= (int)$r['IdMission'] ?></td>
                                <td><?= h($r['TitreMission'] ?? '') ?></td>
                                <td><?= $ins ?></td>
                                <td><?= $att ?: '—' ?></td>
                                <td>
                                    <div class="stats-fill-row">
                                        <div class="stats-fill-track">
                                            <div class="stats-fill-bar" style="width:<?= (float)$pctShow ?>%;"></div>
                                        </div>
                                        <div class="stats-fill-pct">
                                            <?= $att > 0 ? h($pct) . '%' : '—' ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
