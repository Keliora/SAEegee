<?php
require_once __DIR__ . "/../init.php";


if (empty($_SESSION['auth'])) {
    $_SESSION['login_error'] = "Vous devez être connecté.";
    header("Location: ../login.php");
    exit;
}
if (($_SESSION['auth']['role'] ?? '') !== 'ADMIN') {
    $_SESSION['login_error'] = "Accès réservé à l'administration.";
    header("Location: ../login.php");
    exit;
}

function one(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
function allRows(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$adminName    = $_SESSION['auth']['prenom'] ?? 'Admin';
$adminSurname = $_SESSION['auth']['nom'] ?? '';


$nbBenevoles = (int)(one($pdo, "SELECT COUNT(*) c FROM Benevole")['c'] ?? 0);

$nbMissionsMois = (int)(one($pdo, "
    SELECT COUNT(*) c
    FROM Mission
    WHERE DateHeureDebut IS NOT NULL
      AND YEAR(DateHeureDebut)=YEAR(CURDATE())
      AND MONTH(DateHeureDebut)=MONTH(CURDATE())
")['c'] ?? 0);

$nbEvents30 = (int)(one($pdo, "
    SELECT COUNT(*) c
    FROM Evenement
    WHERE DateEvenement IS NOT NULL
      AND DateEvenement >= CURDATE()
      AND DateEvenement < DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")['c'] ?? 0);


$totalDons = (float)$pdo->query("
    SELECT COALESCE(SUM(MontantFinancement), 0)
    FROM Financement
")->fetchColumn();


$sumFinMois = (float)(one($pdo, "
    SELECT COALESCE(SUM(MontantFinancement),0) s
    FROM Financement
    WHERE AnneeFinancement = YEAR(CURDATE())
")['s'] ?? 0);


$rowsMis = allRows($pdo, "
    SELECT DATE(DateHeureDebut) d, COUNT(*) nb
    FROM Mission
    WHERE DateHeureDebut IS NOT NULL
      AND DATE(DateHeureDebut) >= (CURDATE() - INTERVAL 6 DAY)
      AND DATE(DateHeureDebut) <= CURDATE()
    GROUP BY d
    ORDER BY d ASC
");

$mapMis = [];
foreach ($rowsMis as $r) {
    $mapMis[$r['d']] = (int)($r['nb'] ?? 0);
}
$labelsMissions7j = [];
$dataMissions7j   = [];
for ($i = 6; $i >= 0; $i--) {
    $d = (new DateTime())->modify("-{$i} day")->format('Y-m-d');
    $labelsMissions7j[] = (new DateTime($d))->format('d/m');
    $dataMissions7j[]   = $mapMis[$d] ?? 0;
}


$rowsFin = allRows($pdo, "
    SELECT DATE_FORMAT(m.DateHeureDebut, '%Y-%m') ym,
           COALESCE(SUM(s.MontantSoutien),0) total
    FROM Mission m
    JOIN Soutenir s ON s.IdMission = m.IdMission
    WHERE m.DateHeureDebut IS NOT NULL
      AND m.DateHeureDebut >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
    GROUP BY ym
    ORDER BY ym ASC
");

$mapFin = [];
foreach ($rowsFin as $r) {
    $mapFin[$r['ym']] = (float)($r['total'] ?? 0);
}
$labelsFin6m = [];
$dataFin6m   = [];
for ($i = 5; $i >= 0; $i--) {
    $dt = (new DateTime('first day of this month'))->modify("-{$i} month");
    $ym = $dt->format('Y-m');
    $labelsFin6m[] = $dt->format('m/Y');
    $dataFin6m[]   = $mapFin[$ym] ?? 0;
}



$nextEvents = allRows($pdo, "
    SELECT IdEvenement, NomEvenement, TypeEvenement, DateEvenement, HeureEvenement
    FROM Evenement
    WHERE DateEvenement IS NOT NULL AND DateEvenement >= CURDATE()
    ORDER BY DateEvenement ASC, HeureEvenement ASC
    LIMIT 5
");


$topPartenaires = allRows($pdo, "
    SELECT p.IdPartenaire, p.NomPartenaire,
           COALESCE(SUM(s.MontantSoutien),0) total
    FROM Partenaire p
    LEFT JOIN Soutenir s ON s.IdPartenaire = p.IdPartenaire
    GROUP BY p.IdPartenaire, p.NomPartenaire
    ORDER BY total DESC
    LIMIT 5
");

$missionsList = allRows($pdo, "
    SELECT IdMission, TitreMission, CategorieMission, LieuMission, DateHeureDebut, DateHeureFin, NbBenevolesAttendus
    FROM Mission
    ORDER BY COALESCE(DateHeureDebut, '1970-01-01') DESC, IdMission DESC
    LIMIT 10
");
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Admin • Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../newcss.css">
</head>
<body>

<div class="dash-shell">

    <!-- sidebar -->
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
            <a class="dash-link is-active" href="dashboard.php">Vue d'ensemble</a>
            <a class="dash-link" href="statistiques.php">Statistiques</a>

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
    </aside>


    <main class="dash-main">

        <header class="dash-topbar">
            <div>
                <h1 class="dash-h1">Bonjour, <?= htmlspecialchars($adminName . ' ' . $adminSurname) ?></h1>
                <p class="dash-sub">Résumé de l'activité (bénévoles, missions, événements, financements)</p>
            </div>

            <div class="dash-top-actions">
                <div class="dash-date" id="dashDate"><?= date('d/m/Y') ?></div>
                <button class="dash-btn" type="button" onclick="window.print()">Imprimer</button>
                <button class="dash-btn dash-btn-primary" type="button" onclick="alert('Export à brancher (CSV/PDF)')">Export</button>
            </div>
        </header>


        <section class="dash-kpis">
            <div class="dash-kpi">
                <div class="dash-kpi-title">Bénévoles (total)</div>
                <div class="dash-kpi-value"><?= $nbBenevoles ?></div>
                <div class="dash-kpi-note">• Base bénévoles</div>
            </div>

            <div class="dash-kpi">
                <div class="dash-kpi-title">Missions (ce mois)</div>
                <div class="dash-kpi-value"><?= $nbMissionsMois ?></div>
                <div class="dash-kpi-note">• Planifiées + en cours</div>
            </div>

            <div class="dash-kpi">
                <div class="dash-kpi-title">Événements (30 jours)</div>
                <div class="dash-kpi-value"><?= $nbEvents30 ?></div>
                <div class="dash-kpi-note">• À venir</div>
            </div>

            <div class="dash-kpi">
                <div class="dash-kpi-title">Dons (total)</div>
                <div class="dash-kpi-value"><?= number_format($totalDons, 2, ',', ' ') ?> €</div>
                <div class="dash-kpi-note">• Année en cours : <?= number_format($sumFinMois, 2, ',', ' ') ?> €</div>
            </div>
        </section>


        <section class="dash-grid">


            <div class="dash-card dash-card-big">
                <div class="dash-card-head">
                    <div class="dash-card-title">Activité • Missions (7 jours)</div>
                    <div class="dash-card-meta">Cette semaine</div>
                </div>

                <div class="dash-card-body" style="padding:12px 14px;">
                    <canvas id="chartMissions7j" height="140"></canvas>
                </div>

                <div class="dash-card-sep"></div>

                <div class="dash-card-head">
                    <div class="dash-card-title">Finances • 6 derniers mois</div>
                    <div class="dash-card-meta">Soutiens (Soutenir + Mission)</div>
                </div>

                <div class="dash-card-body" style="padding:12px 14px;">
                    <canvas id="chartFin6m" height="140"></canvas>
                </div>
            </div>


            <div class="dash-card">
                <div class="dash-card-head">
                    <div class="dash-card-title">Prochains événements</div>
                    <div class="dash-card-meta">Top 5</div>
                </div>

                <div class="dash-list">
                    <?php if (empty($nextEvents)): ?>
                        <div class="dash-empty">Aucun événement à venir.</div>
                    <?php else: ?>
                        <?php foreach ($nextEvents as $e): ?>
                            <div class="dash-item">
                                <div class="dash-item-title">
                                    <?= htmlspecialchars($e['NomEvenement'] ?? '') ?>
                                </div>
                                <div class="dash-item-sub">
                                    <?= htmlspecialchars($e['TypeEvenement'] ?? '—') ?> •
                                    <?= htmlspecialchars($e['DateEvenement'] ?? '—') ?>
                                    <?= $e['HeureEvenement'] ? ' • ' . htmlspecialchars($e['HeureEvenement']) : '' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="dash-card-sep"></div>

                <div class="dash-card-head">
                    <div class="dash-card-title">Top partenaires</div>
                    <div class="dash-card-meta">par montant soutenu</div>
                </div>

                <div class="dash-list">
                    <?php if (empty($topPartenaires)): ?>
                        <div class="dash-empty">Aucun partenaire trouvé.</div>
                    <?php else: ?>
                        <?php foreach ($topPartenaires as $p): ?>
                            <div class="dash-item">
                                <div class="dash-item-title">
                                    <?= htmlspecialchars($p['NomPartenaire'] ?? '') ?>
                                </div>
                                <div class="dash-item-sub">
                                    <?= number_format((float)($p['total'] ?? 0), 2, ',', ' ') ?> €
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>


        <section class="dash-card dash-tablecard" style="margin-top:12px;">
            <div class="dash-card-head">
                <div class="dash-card-title">Missions récentes</div>
                <div class="dash-card-meta">10 dernières</div>
            </div>

            <div class="dash-tablewrap">
                <table class="dash-table">
                    <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Lieu</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Attendus</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($missionsList)): ?>
                        <tr><td colspan="6" class="dash-td-empty">Aucune mission trouvée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($missionsList as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['TitreMission'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['CategorieMission'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($m['LieuMission'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($m['DateHeureDebut'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($m['DateHeureFin'] ?? '—') ?></td>
                                <td><?= (int)($m['NbBenevolesAttendus'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>


    const labelsMissions7j = <?= json_encode($labelsMissions7j, JSON_UNESCAPED_UNICODE) ?>;
    const dataMissions7j   = <?= json_encode($dataMissions7j, JSON_UNESCAPED_UNICODE) ?>;

    const labelsFin6m = <?= json_encode($labelsFin6m, JSON_UNESCAPED_UNICODE) ?>;
    const dataFin6m   = <?= json_encode($dataFin6m, JSON_UNESCAPED_UNICODE) ?>;

    const c1 = document.getElementById('chartMissions7j');
    if (c1) {
        new Chart(c1, {
            type: 'line',
            data: {
                labels: labelsMissions7j,
                datasets: [{
                    label: 'Missions',
                    data: dataMissions7j,
                    tension: 0.35,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    const c2 = document.getElementById('chartFin6m');
    if (c2) {
        new Chart(c2, {
            type: 'bar',
            data: {
                labels: labelsFin6m,
                datasets: [{
                    label: '€',
                    data: dataFin6m
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
</script>

</body>
</html>
