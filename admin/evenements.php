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

        $NomEvenement       = trim($_POST['NomEvenement'] ?? '');
        $TypeEvenement      = trim($_POST['TypeEvenement'] ?? '');
        $DateEvenement      = trim($_POST['DateEvenement'] ?? '');
        $HeureEvenement     = trim($_POST['HeureEvenement'] ?? '');
        $LienMediaEvenement = trim($_POST['LienMediaEvenement'] ?? '');

        // Validation minimale
        if ($NomEvenement === '') {
            $_SESSION['flash_error'] = "Le nom de l'événement est obligatoire.";
            header("Location: evenements.php" . ($formId ? "?mode=edit&id=".$formId : ""));
            exit;
        }

        // conversions (vide => NULL)
        $DateEvenement  = ($DateEvenement !== '' ? $DateEvenement : null);
        $HeureEvenement = ($HeureEvenement !== '' ? $HeureEvenement : null);

        // Lien: si vide => NULL
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
            <a class="dash-link" href="formulaire.php">Formulaires</a>
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

            </div>
            <div class="dash-top-actions">
                <a class="dash-btn dash-btn-primary" href="evenements.php">+ Nouvel événement</a>
            </div>
        </header>

        <?php if ($flashSuccess): ?>
            <div class="dash-card dash-flash dash-flash-success">
                ✅ <?= h($flashSuccess) ?>
            </div>
            <div class="dash-spacer-10"></div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="dash-card dash-flash dash-flash-error">
                ❌ <?= h($flashError) ?>
            </div>
            <div class="dash-spacer-10"></div>
        <?php endif; ?>

        <!-- FORM create/edit -->
        <section class="dash-card">
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

                    <div class="dash-form-grid">
                        <div class="dash-col-span-2">
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

                        <div class="dash-col-span-3">
                            <label>Lien média</label>
                            <input class="dash-input" name="LienMediaEvenement" value="<?= h($edit['LienMediaEvenement'] ?? '') ?>" placeholder="https://...">
                        </div>
                    </div>

                    <div class="dash-form-actions">
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

            <div class="dash-card-body" ">
                <form method="get" action="evenements.php" class="dash-searchbar">
                    <input class="dash-input dash-search-input" name="q" value="<?= h($q) ?>"
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
                                    <td class="dash-row-actions">
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
                    <div class="dash-pagination">
                        <a class="dash-btn" href="evenements.php?p=1<?= $q!=='' ? '&q='.urlencode($q) : '' ?>">« Début</a>
                        <a class="dash-btn" href="evenements.php?p=<?= max(1,$page-1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">‹ Préc</a>

                        <span class="dash-pagination-info">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>

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
