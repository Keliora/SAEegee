<?php
require_once __DIR__ . "/admin_guard.php";
$activeMenu = "overview";
$pageTitle = "Admin — Vue d’ensemble";


$nbBenevoles = (int)$pdo->query("SELECT COUNT(*) FROM Benevole")->fetchColumn();


$nbMissionsMois = (int)$pdo->query("
    SELECT COUNT(*) FROM Mission
    WHERE DateHeureDebut IS NOT NULL
      AND MONTH(DateHeureDebut) = MONTH(CURDATE())
      AND YEAR(DateHeureDebut) = YEAR(CURDATE())
")->fetchColumn();


$nbEvents30 = (int)$pdo->query("
    SELECT COUNT(*) FROM Evenement
    WHERE DateEvenement IS NOT NULL
      AND DateEvenement BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetchColumn();


$totalFinancementAnnee = (float)$pdo->query("
    SELECT COALESCE(SUM(MontantFinancement),0)
    FROM Financement
    WHERE AnneeFinancement = YEAR(CURDATE())
")->fetchColumn();


$events = $pdo->query("
    SELECT NomEvenement, DateEvenement, TypeEvenement
    FROM Evenement
    WHERE DateEvenement IS NOT NULL
    ORDER BY DateEvenement ASC
    LIMIT 5
")->fetchAll();


$missions = $pdo->query("
    SELECT TitreMission, CategorieMission, DateHeureDebut, DateHeureFin
    FROM Mission
    ORDER BY (DateHeureDebut IS NULL), DateHeureDebut DESC
    LIMIT 8
")->fetchAll();

include __DIR__ . "/layout_top.php";
?>

<div class="admin-kpis">
    <div class="admin-card kpi">
        <div class="kpi-title">Bénévoles (total)</div>
        <div class="kpi-value"><?= $nbBenevoles ?></div>
        <div class="kpi-sub">Base bénévoles</div>
    </div>

    <div class="admin-card kpi">
        <div class="kpi-title">Missions (ce mois)</div>
        <div class="kpi-value"><?= $nbMissionsMois ?></div>
        <div class="kpi-sub">Planifiées + en cours</div>
    </div>

    <div class="admin-card kpi">
        <div class="kpi-title">Événements (30 jours)</div>
        <div class="kpi-value"><?= $nbEvents30 ?></div>
        <div class="kpi-sub">À venir</div>
    </div>

    <div class="admin-card kpi">
        <div class="kpi-title">Financements (année)</div>
        <div class="kpi-value"><?= number_format($totalFinancementAnnee, 0, ',', ' ') ?> €</div>
        <div class="kpi-sub">Somme des financements</div>
    </div>
</div>

<div class="admin-grid-2">
    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Activité • Missions (7 jours)</h3>
            <span class="muted">Cette semaine</span>
        </div>
        <div class="admin-placeholder">
            (Graphique à brancher plus tard — on a déjà l’emplacement)
        </div>

        <div class="admin-card-header" style="margin-top:18px;">
            <h3>Finances • 6 derniers mois</h3>
            <span class="muted">Dons / Financements</span>
        </div>
        <div class="admin-placeholder">
            (Graphique à brancher plus tard)
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Prochains événements</h3>
            <span class="muted">Top 5</span>
        </div>

        <div class="admin-list">
            <?php if (!$events): ?>
                <div class="muted">Aucun événement pour le moment.</div>
            <?php else: ?>
                <?php foreach ($events as $e): ?>
                    <div class="admin-list-item">
                        <div>
                            <div class="admin-list-title"><?= htmlspecialchars($e['NomEvenement']) ?></div>
                            <div class="muted">
                                <?= htmlspecialchars($e['TypeEvenement'] ?? '') ?>
                            </div>
                        </div>
                        <div class="admin-pill">
                            <?= htmlspecialchars($e['DateEvenement'] ?? '') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="admin-card-header" style="margin-top:18px;">
            <h3>Top partenaires</h3>
            <span class="muted">Dons + financements</span>
        </div>
        <div class="admin-placeholder">
            (À brancher quand tu auras la logique “montant par partenaire”)
        </div>
    </div>
</div>

<div class="admin-card" style="margin-top:16px;">
    <div class="admin-card-header">
        <h3>Missions en cours / planifiées</h3>
        <span class="muted">Dernières</span>
    </div>

    <div class="admin-table">
        <div class="admin-thead">
            <div>Titre</div>
            <div>Catégorie</div>
            <div>Début</div>
            <div>Fin</div>
        </div>

        <?php if (!$missions): ?>
            <div class="admin-row muted">Aucune mission.</div>
        <?php else: ?>
            <?php foreach ($missions as $m): ?>
                <div class="admin-row">
                    <div><?= htmlspecialchars($m['TitreMission']) ?></div>
                    <div class="muted"><?= htmlspecialchars($m['CategorieMission'] ?? '') ?></div>
                    <div><?= htmlspecialchars($m['DateHeureDebut'] ?? '-') ?></div>
                    <div><?= htmlspecialchars($m['DateHeureFin'] ?? '-') ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . "/layout_bottom.php"; ?>
