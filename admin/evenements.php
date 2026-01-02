<?php
require_once __DIR__ . "/../init.php";

if (empty($_SESSION['auth'])) {
    $_SESSION['login_error'] = "Vous devez être connecté.";
    header("Location: ../login.php");
    exit;
}
if (($_SESSION['auth']['role'] ?? '') !== 'ADMIN') {
    $_SESSION['login_error'] = "Accès réservé à l'administration.";
    header("Location: ../index.php");
    exit;
}


function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function flash_get($key) {
    if (!empty($_SESSION[$key])) {
        $v = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $v;
    }
    return null;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$adminName  = trim(($_SESSION['auth']['prenom'] ?? 'System') . ' ' . ($_SESSION['auth']['nom'] ?? 'Admin'));
$adminEmail = $_SESSION['auth']['email'] ?? 'admin@site.com';


$mode = $_GET['mode'] ?? ''; // 'edit'
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (($_POST['csrf'] ?? '') !== $csrf) {
        $_SESSION['flash_error'] = "Sécurité: token invalide.";
        header("Location: evenements.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // DELETE
    if ($action === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);

        if ($delId <= 0) {
            $_SESSION['flash_error'] = "ID invalide.";
            header("Location: evenements.php");
            exit;
        }

        $st = $pdo->prepare("DELETE FROM Evenement WHERE IdEvenement = :id");
        $st->execute([':id' => $delId]);

        $_SESSION['flash_success'] = "Événement supprimé.";
        header("Location: evenements.php");
        exit;
    }

    // CREATE / UPDATE
    if ($action === 'save') {
        $formId = (int)($_POST['id'] ?? 0);

        $NomEvenement      = trim($_POST['NomEvenement'] ?? '');
        $TypeEvenement     = trim($_POST['TypeEvenement'] ?? '');
        $DateEvenement     = trim($_POST['DateEvenement'] ?? '');
        $HeureEvenement    = trim($_POST['HeureEvenement'] ?? '');
        $LienMediaEvenement= trim($_POST['LienMediaEvenement'] ?? '');

        // Validation minimale
        if ($NomEvenement === '') {
            $_SESSION['flash_error'] = "Le nom de l'événement est obligatoire.";
            header("Location: evenements.php" . ($formId ? "?mode=edit&id=".$formId : ""));
            exit;
        }

        // conversions (vide => NULL)
        $DateEvenement  = ($DateEvenement !== '' ? $DateEvenement : null);
        $HeureEvenement = ($HeureEvenement !== '' ? $HeureEvenement : null);

        // Lien: si vide => NULL, sinon on garde
        $LienMediaEvenement = ($LienMediaEvenement !== '' ? $LienMediaEvenement : null);

        // Create
        if ($formId === 0) {
            $sql = "INSERT INTO Evenement
                    (NomEvenement, TypeEvenement, DateEvenement, HeureEvenement, LienMediaEvenement)
                    VALUES
                    (:Nom, :Type, :DateE, :HeureE, :Lien)";
            $st = $pdo->prepare($sql);

            try {
                $st->execute([
                        ':Nom'   => $NomEvenement,
                        ':Type'  => ($TypeEvenement !== '' ? $TypeEvenement : null),
                        ':DateE' => $DateEvenement,
                        ':HeureE'=> $HeureEvenement,
                        ':Lien'  => $LienMediaEvenement
                ]);
                $_SESSION['flash_success'] = "Événement ajouté.";
                header("Location: evenements.php");
                exit;
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Erreur SQL lors de l'ajout.";
                header("Location: evenements.php");
                exit;
            }
        }

        // Update
        $sql = "UPDATE Evenement SET
                    NomEvenement=:Nom,
                    TypeEvenement=:Type,
                    DateEvenement=:DateE,
                    HeureEvenement=:HeureE,
                    LienMediaEvenement=:Lien
                WHERE IdEvenement=:Id";
        $st = $pdo->prepare($sql);

        try {
            $st->execute([
                    ':Nom'   => $NomEvenement,
                    ':Type'  => ($TypeEvenement !== '' ? $TypeEvenement : null),
                    ':DateE' => $DateEvenement,
                    ':HeureE'=> $HeureEvenement,
                    ':Lien'  => $LienMediaEvenement,
                    ':Id'    => $formId
            ]);

            $_SESSION['flash_success'] = "Événement mis à jour.";
            header("Location: evenements.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Erreur SQL lors de la mise à jour.";
            header("Location: evenements.php?mode=edit&id=".$formId);
            exit;
        }
    }
}

$edit = null;
if ($mode === 'edit' && $id > 0) {
    $st = $pdo->prepare("SELECT * FROM Evenement WHERE IdEvenement = :id LIMIT 1");
    $st->execute([':id' => $id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$edit) {
        $_SESSION['flash_error'] = "Événement introuvable.";
        header("Location: evenements.php");
        exit;
    }
}


$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "";
$params = [];

if ($q !== '') {
    $where = "WHERE (NomEvenement LIKE :q OR TypeEvenement LIKE :q OR LienMediaEvenement LIKE :q)";
    $params[':q'] = "%$q%";
}

$st = $pdo->prepare("SELECT COUNT(*) c FROM Evenement $where");
$st->execute($params);
$total = (int)($st->fetchColumn() ?: 0);
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT IdEvenement, NomEvenement, TypeEvenement, DateEvenement, HeureEvenement, LienMediaEvenement
        FROM Evenement
        $where
        ORDER BY COALESCE(DateEvenement, '1970-01-01') DESC, COALESCE(HeureEvenement,'00:00:00') DESC, IdEvenement DESC
        LIMIT $perPage OFFSET $offset";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

$flashSuccess = flash_get('flash_success');
$flashError   = flash_get('flash_error');

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Admin • Événements</title>
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
            <a class="dash-link is-active" href="evenements.php">Événements</a>
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
                <h1 class="dash-h1">Gestion des événements</h1>
                <p class="dash-sub">Ajouter, modifier, rechercher et gérer les événements.</p>
            </div>
            <div class="dash-top-actions">
                <a class="dash-btn dash-btn-primary" href="evenements.php">+ Nouvel événement</a>
            </div>
        </header>

        <?php if ($flashSuccess): ?>
            <div class="dash-card" style="padding:12px 14px; border-color:#c7f0d6; background:#f0fff5;">
                ✅ <?= h($flashSuccess) ?>
            </div>
            <div style="height:10px"></div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="dash-card" style="padding:12px 14px; border-color:#ffd0d0; background:#fff5f5;">
                ❌ <?= h($flashError) ?>
            </div>
            <div style="height:10px"></div>
        <?php endif; ?>

        <!-- FORM create/edit -->
        <section class="dash-card" style="margin-bottom:12px;">
            <div class="dash-card-head">
                <div class="dash-card-title">
                    <?= $edit ? "Modifier l'événement #".(int)$edit['IdEvenement'] : "Ajouter un événement" ?>
                </div>
                <div class="dash-card-meta"><?= $edit ? "Mode édition" : "Mode création" ?></div>
            </div>

            <div class="dash-card-body">
                <form method="post" action="evenements.php<?= $edit ? '?mode=edit&id='.(int)$edit['IdEvenement'] : '' ?>">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['IdEvenement'] : 0 ?>">

                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px;">
                        <div style="grid-column: span 2;">
                            <label>Nom *</label>
                            <input class="dash-input" name="NomEvenement" value="<?= h($edit['NomEvenement'] ?? '') ?>" required>
                        </div>

                        <div>
                            <label>Type</label>
                            <input class="dash-input" name="TypeEvenement" value="<?= h($edit['TypeEvenement'] ?? '') ?>">
                        </div>

                        <div>
                            <label>Date</label>
                            <input class="dash-input" type="date" name="DateEvenement" value="<?= h($edit['DateEvenement'] ?? '') ?>">
                        </div>

                        <div>
                            <label>Heure</label>
                            <input class="dash-input" type="time" name="HeureEvenement" value="<?= h($edit['HeureEvenement'] ?? '') ?>">
                        </div>

                        <div style="grid-column: span 3;">
                            <label>Lien média</label>
                            <input class="dash-input" name="LienMediaEvenement" value="<?= h($edit['LienMediaEvenement'] ?? '') ?>" placeholder="https://...">
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
                        <button class="dash-btn dash-btn-primary" type="submit">
                            <?= $edit ? "Enregistrer" : "Créer" ?>
                        </button>

                        <?php if ($edit): ?>
                            <a class="dash-btn" href="evenements.php">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Liste des événements</div>
                <div class="dash-card-meta"><?= (int)$total ?> résultat(s)</div>
            </div>

            <div class="dash-card-body" style="padding-top:0;">
                <form method="get" action="evenements.php" style="display:flex; gap:10px; flex-wrap:wrap; margin:12px 0;">
                    <input class="dash-input" style="flex:1; min-width:240px;" name="q" value="<?= h($q) ?>"
                           placeholder="Rechercher (nom, type, lien)">
                    <button class="dash-btn" type="submit">Rechercher</button>
                    <?php if ($q !== ''): ?>
                        <a class="dash-btn" href="evenements.php">Reset</a>
                    <?php endif; ?>
                </form>

                <div class="dash-tablewrap">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Lien</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="dash-td-empty">Aucun événement trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $e): ?>
                                <tr>
                                    <td><?= (int)$e['IdEvenement'] ?></td>
                                    <td><?= h($e['NomEvenement'] ?? '') ?></td>
                                    <td><?= h($e['TypeEvenement'] ?? '—') ?></td>
                                    <td><?= h($e['DateEvenement'] ?? '—') ?></td>
                                    <td><?= h($e['HeureEvenement'] ?? '—') ?></td>
                                    <td>
                                        <?php if (!empty($e['LienMediaEvenement'])): ?>
                                            <a href="<?= h($e['LienMediaEvenement']) ?>" target="_blank" rel="noreferrer">ouvrir</a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <a class="dash-btn" href="evenements.php?mode=edit&id=<?= (int)$e['IdEvenement'] ?>">Modifier</a>

                                        <form method="post" action="evenements.php" onsubmit="return confirm('Supprimer cet événement ?');">
                                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$e['IdEvenement'] ?>">
                                            <button class="dash-btn" type="submit">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; align-items:center;">
                        <a class="dash-btn" href="evenements.php?p=1<?= $q!=='' ? '&q='.urlencode($q) : '' ?>">« Début</a>
                        <a class="dash-btn" href="evenements.php?p=<?= max(1,$page-1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">‹ Préc</a>

                        <span style="color:#6b7c98;">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>

                        <a class="dash-btn" href="evenements.php?p=<?= min($totalPages,$page+1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Suiv ›</a>
                        <a class="dash-btn" href="evenements.php?p=<?= (int)$totalPages ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Fin »</a>
                    </div>
                <?php endif; ?>

            </div>
        </section>

    </main>
</div>




</body>
</html>
