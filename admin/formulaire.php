<?php
require_once __DIR__ . "/../init.php";

if (empty($_SESSION['auth'])) {
    $_SESSION['login_error'] = "Vous devez être connecté.";
    header("Location: ../login.php"); exit;
}
if (($_SESSION['auth']['role'] ?? '') !== 'ADMIN') {
    $_SESSION['login_error'] = "Accès réservé à l'administration.";
    header("Location: ../index.php"); exit;
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function flash_get($k){ if(!empty($_SESSION[$k])){ $v=$_SESSION[$k]; unset($_SESSION[$k]); return $v; } return null; }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$flashSuccess = flash_get('flash_success');
$flashError   = flash_get('flash_error');

/** ACTIONS POST : supprimer / changer statut */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf'] ?? '') !== $csrf) {
        $_SESSION['flash_error'] = "Sécurité: token invalide.";
        header("Location: formulaire.php"); exit;
    }

    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        $_SESSION['flash_error'] = "ID invalide.";
        header("Location: formulaire.php"); exit;
    }

    if ($action === 'delete') {
        $st = $pdo->prepare("DELETE FROM Formulaire WHERE IdFormulaire = :id");
        $st->execute([':id' => $id]);
        $_SESSION['flash_success'] = "Message supprimé.";
        header("Location: formulaire.php"); exit;
    }

    if ($action === 'set_status') {
        $newStatus = $_POST['statut'] ?? 'NOUVEAU';
        $allowed = ['NOUVEAU','TRAITE','ARCHIVE'];
        if (!in_array($newStatus, $allowed, true)) $newStatus = 'NOUVEAU';

        $st = $pdo->prepare("UPDATE Formulaire SET Statut = :s WHERE IdFormulaire = :id");
        $st->execute([':s' => $newStatus, ':id' => $id]);

        $_SESSION['flash_success'] = "Statut mis à jour.";
        header("Location: formulaire.php"); exit;
    }
}

/** Filtres */
$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(Nom LIKE :q OR Email LIKE :q OR Message LIKE :q OR Objet LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($status !== '' && in_array($status, ['NOUVEAU','TRAITE','ARCHIVE'], true)) {
    $where[] = "Statut = :st";
    $params[':st'] = $status;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

/** Count */
$st = $pdo->prepare("SELECT COUNT(*) FROM Formulaire $whereSql");
$st->execute($params);
$total = (int)($st->fetchColumn() ?: 0);
$totalPages = max(1, (int)ceil($total / $perPage));

/** Liste */
$st = $pdo->prepare("
    SELECT
        IdFormulaire, Nom, Email, Objet, Message, Statut, DateCreation
    FROM Formulaire
    $whereSql
    ORDER BY DateCreation DESC, IdFormulaire DESC
    LIMIT $perPage OFFSET $offset
");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

$adminName  = trim(($_SESSION['auth']['prenom'] ?? 'System').' '.($_SESSION['auth']['nom'] ?? 'Admin'));
$adminEmail = $_SESSION['auth']['email'] ?? 'admin@site.com';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Admin • Formulaires</title>
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
            <a class="dash-link" href="statistiques.php">Statistiques</a>

            <div class="dash-menu-section">GESTION</div>
            <a class="dash-link" href="benevoles.php">Bénévoles</a>
            <a class="dash-link" href="missions.php">Missions</a>
            <a class="dash-link" href="evenements.php">Événements</a>
            <a class="dash-link" href="presse.php">Presse</a>
            <a class="dash-link is-active" href="formulaire.php">Formulaires</a>

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
                <h1 class="dash-h1">Formulaires</h1>
            </div>
        </header>

        <?php if ($flashSuccess): ?>
            <div class="dash-card fo-flash fo-flash-success">✅ <?= h($flashSuccess) ?></div>
            <div class="fo-spacer-10"></div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="dash-card fo-flash fo-flash-error">❌ <?= h($flashError) ?></div>
            <div class="fo-spacer-10"></div>
        <?php endif; ?>

        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Liste des messages</div>
                <div class="dash-card-meta"><?= (int)$total ?> résultat(s)</div>
            </div>

            <div class="dash-card-body fo-table-body-padfix">
                <form method="get" action="formulaire.php" class="fo-filters">
                    <input class="dash-input fo-filters-q" name="q" value="<?= h($q) ?>" placeholder="Rechercher (nom, email, objet, message)">
                    <select class="dash-input fo-filters-status" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="NOUVEAU" <?= $status==='NOUVEAU'?'selected':'' ?>>NOUVEAU</option>
                        <option value="TRAITE"  <?= $status==='TRAITE'?'selected':'' ?>>TRAITE</option>
                        <option value="ARCHIVE" <?= $status==='ARCHIVE'?'selected':'' ?>>ARCHIVE</option>
                    </select>
                    <button class="dash-btn" type="submit">Filtrer</button>
                    <a class="dash-btn" href="formulaire.php">Reset</a>
                </form>

                <div class="dash-tablewrap">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Objet</th>
                            <th>Message</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($rows)): ?>
                            <tr><td colspan="8" class="dash-td-empty">Aucun message.</td></tr>
                        <?php else: foreach($rows as $r): ?>
                            <tr>
                                <td><?= (int)$r['IdFormulaire'] ?></td>
                                <td><?= h($r['DateCreation']) ?></td>
                                <td><?= h($r['Nom']) ?></td>
                                <td><a href="mailto:<?= h($r['Email']) ?>"><?= h($r['Email']) ?></a></td>
                                <td><?= h($r['Objet']) ?></td>
                                <td class="fo-msg-ellipsis">
                                    <?= h($r['Message']) ?>
                                </td>
                                <td><?= h($r['Statut']) ?></td>
                                <td class="fo-actions-cell">
                                    <form method="post" action="formulaire.php">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="set_status">
                                        <input type="hidden" name="id" value="<?= (int)$r['IdFormulaire'] ?>">
                                        <select class="dash-input" name="statut" onchange="this.form.submit()">
                                            <option value="NOUVEAU" <?= $r['Statut']==='NOUVEAU'?'selected':'' ?>>NOUVEAU</option>
                                            <option value="TRAITE"  <?= $r['Statut']==='TRAITE'?'selected':'' ?>>TRAITE</option>
                                            <option value="ARCHIVE" <?= $r['Statut']==='ARCHIVE'?'selected':'' ?>>ARCHIVE</option>
                                        </select>
                                    </form>

                                    <form method="post" action="formulaire.php" onsubmit="return confirm('Supprimer ce message ?');">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$r['IdFormulaire'] ?>">
                                        <button class="dash-btn" type="submit">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="fo-pagination">
                        <?php
                        $base = "formulaire.php?";
                        if ($q !== '') $base .= "q=".urlencode($q)."&";
                        if ($status !== '') $base .= "status=".urlencode($status)."&";
                        ?>
                        <a class="dash-btn" href="<?= $base ?>p=1">« Début</a>
                        <a class="dash-btn" href="<?= $base ?>p=<?= max(1,$page-1) ?>">‹ Préc</a>
                        <span class="fo-pagination-info">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>
                        <a class="dash-btn" href="<?= $base ?>p=<?= min($totalPages,$page+1) ?>">Suiv ›</a>
                        <a class="dash-btn" href="<?= $base ?>p=<?= (int)$totalPages ?>">Fin »</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
</body>
</html>
