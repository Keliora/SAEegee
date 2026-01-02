<?php

$admin = $_SESSION['auth'] ?? null;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? "Admin — EGEE") ?></title>


    <link rel="stylesheet" href="../newcss.css">


</head>
<body class="admin-body">


<div class="admin-shell">


    <aside class="admin-sidebar">
        <div class="admin-brand">
            <div class="admin-logo">EGEE</div>
            <div class="admin-badge">ADMIN</div>
        </div>

        <nav class="admin-nav">
            <div class="admin-nav-title">DASHBOARD</div>
            <a class="admin-link <?= ($activeMenu ?? '')==='overview' ? 'is-active':'' ?>" href="/admin/index.php">Vue d’ensemble</a>

            <div class="admin-nav-title">GESTION</div>
            <a class="admin-link <?= ($activeMenu ?? '')==='benevoles' ? 'is-active':'' ?>" href="/admin/benevoles.php">Bénévoles</a>
            <a class="admin-link <?= ($activeMenu ?? '')==='missions' ? 'is-active':'' ?>" href="/admin/missions.php">Missions</a>
            <a class="admin-link <?= ($activeMenu ?? '')==='events' ? 'is-active':'' ?>" href="/admin/evenements.php">Événements</a>
            <a class="admin-link <?= ($activeMenu ?? '')==='partenaires' ? 'is-active':'' ?>" href="/admin/partenaires.php">Partenaires</a>
            <a class="admin-link <?= ($activeMenu ?? '')==='finances' ? 'is-active':'' ?>" href="/admin/financements.php">Dons / Financements</a>

            <div class="admin-nav-title">SESSION</div>
            <a class="admin-link" href="/logout.php">Déconnexion</a>
        </nav>

        <div class="admin-userbox">
            <div class="admin-user-name"><?= htmlspecialchars(($admin['prenom'] ?? 'System') . " " . ($admin['nom'] ?? 'Admin')) ?></div>
            <div class="admin-user-mail"><?= htmlspecialchars($admin['email'] ?? '') ?></div>
        </div>
    </aside>


    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="admin-hello">Bonjour, <?= htmlspecialchars($admin['prenom'] ?? 'Admin') ?></div>
                <div class="admin-subtitle">Résumé de l’activité (bénévoles, missions, événements, financements)</div>
            </div>

            <div class="admin-actions">
                <button class="btn btn-outline" type="button">🖨️ Imprimer</button>
                <button class="btn btn-primary" type="button">⬇️ Export</button>
            </div>
        </div>

        <div class="admin-content">
        </div>
    </main>


