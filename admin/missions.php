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

/**
 * Convertit un DATETIME SQL (YYYY-MM-DD HH:MM:SS) en format datetime-local (YYYY-MM-DDTHH:MM)
 */
function dt_local_value($sqlDateTime) {
    if (empty($sqlDateTime)) return '';
    if (strpos($sqlDateTime, 'T') !== false) return substr($sqlDateTime, 0, 16);
    return str_replace(' ', 'T', substr($sqlDateTime, 0, 16));
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$adminName  = trim(($_SESSION['auth']['prenom'] ?? 'System') . ' ' . ($_SESSION['auth']['nom'] ?? 'Admin'));
$adminEmail = $_SESSION['auth']['email'] ?? 'admin@site.com';

$mode = $_GET['mode'] ?? '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (($_POST['csrf'] ?? '') !== $csrf) {
        $_SESSION['flash_error'] = "Sécurité: token invalide.";
        header("Location: missions.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // DELETE
    if ($action === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);

        if ($delId <= 0) {
            $_SESSION['flash_error'] = "ID invalide.";
            header("Location: missions.php");
            exit;
        }

        $st = $pdo->prepare("DELETE FROM Mission WHERE IdMission = :id");
        $st->execute([':id' => $delId]);

        $_SESSION['flash_success'] = "Mission supprimée.";
        header("Location: missions.php");
        exit;
    }

    // SAVE (CREATE/UPDATE)
    if ($action === 'save') {
        $formId = (int)($_POST['id'] ?? 0);

        $TitreMission        = trim($_POST['TitreMission'] ?? '');
        $DescriptionMission  = trim($_POST['DescriptionMission'] ?? '');
        $CategorieMission    = trim($_POST['CategorieMission'] ?? '');
        $LieuMission         = trim($_POST['LieuMission'] ?? '');
        $DateHeureDebut      = trim($_POST['DateHeureDebut'] ?? '');
        $DateHeureFin        = trim($_POST['DateHeureFin'] ?? '');
        $NbBenevolesAttendus = trim($_POST['NbBenevolesAttendus'] ?? '');
        $MaterielNecessaire  = trim($_POST['MaterielNecessaire'] ?? '');

        if ($TitreMission === '') {
            $_SESSION['flash_error'] = "Le titre de mission est obligatoire.";
            header("Location: missions.php" . ($formId ? "?mode=edit&id=".$formId : ""));
            exit;
        }

        $NbBenevolesAttendus = ($NbBenevolesAttendus !== '' ? (int)$NbBenevolesAttendus : null);

        $DateHeureDebut = ($DateHeureDebut !== '' ? str_replace('T', ' ', $DateHeureDebut) : null);
        $DateHeureFin   = ($DateHeureFin !== '' ? str_replace('T', ' ', $DateHeureFin) : null);

        // CREATE
        if ($formId === 0) {
            $sql = "INSERT INTO Mission
                (TitreMission, DescriptionMission, CategorieMission, LieuMission,
                 DateHeureDebut, DateHeureFin, NbBenevolesAttendus, MaterielNecessaire)
                VALUES
                (:Titre, :Descr, :Cat, :Lieu, :Debut, :Fin, :Nb, :Mat)";
            $st = $pdo->prepare($sql);

            try {
                $st->execute([
                        ':Titre' => $TitreMission,
                        ':Descr' => ($DescriptionMission !== '' ? $DescriptionMission : null),
                        ':Cat'   => ($CategorieMission !== '' ? $CategorieMission : null),
                        ':Lieu'  => ($LieuMission !== '' ? $LieuMission : null),
                        ':Debut' => $DateHeureDebut,
                        ':Fin'   => $DateHeureFin,
                        ':Nb'    => $NbBenevolesAttendus,
                        ':Mat'   => ($MaterielNecessaire !== '' ? $MaterielNecessaire : null),
                ]);

                $_SESSION['flash_success'] = "Mission ajoutée.";
                header("Location: missions.php");
                exit;
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Erreur SQL lors de l'ajout.";
                header("Location: missions.php");
                exit;
            }
        }

        // UPDATE
        $sql = "UPDATE Mission SET
                    TitreMission=:Titre,
                    DescriptionMission=:Descr,
                    CategorieMission=:Cat,
                    LieuMission=:Lieu,
                    DateHeureDebut=:Debut,
                    DateHeureFin=:Fin,
                    NbBenevolesAttendus=:Nb,
                    MaterielNecessaire=:Mat
                WHERE IdMission=:Id";
        $st = $pdo->prepare($sql);

        try {
            $st->execute([
                    ':Titre' => $TitreMission,
                    ':Descr' => ($DescriptionMission !== '' ? $DescriptionMission : null),
                    ':Cat'   => ($CategorieMission !== '' ? $CategorieMission : null),
                    ':Lieu'  => ($LieuMission !== '' ? $LieuMission : null),
                    ':Debut' => $DateHeureDebut,
                    ':Fin'   => $DateHeureFin,
                    ':Nb'    => $NbBenevolesAttendus,
                    ':Mat'   => ($MaterielNecessaire !== '' ? $MaterielNecessaire : null),
                    ':Id'    => $formId
            ]);

            $_SESSION['flash_success'] = "Mission mise à jour.";
            header("Location: missions.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Erreur SQL lors de la mise à jour.";
            header("Location: missions.php?mode=edit&id=".$formId);
            exit;
        }
    }
}

// EDIT LOAD
$edit = null;
if ($mode === 'edit' && $id > 0) {
    $st = $pdo->prepare("SELECT * FROM Mission WHERE IdMission = :id LIMIT 1");
    $st->execute([':id' => $id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$edit) {
        $_SESSION['flash_error'] = "Mission introuvable.";
        header("Location: missions.php");
        exit;
    }
}

// LIST + SEARCH + PAGINATION
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "";
$params = [];

if ($q !== '') {
    $where = "WHERE (TitreMission LIKE :q OR CategorieMission LIKE :q OR LieuMission LIKE :q)";
    $params[':q'] = "%$q%";
}

$st = $pdo->prepare("SELECT COUNT(*) c FROM Mission $where");
$st->execute($params);
$total = (int)($st->fetchColumn() ?: 0);
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT IdMission, TitreMission, CategorieMission, LieuMission, DateHeureDebut, DateHeureFin, NbBenevolesAttendus
        FROM Mission
        $where
        ORDER BY COALESCE(DateHeureDebut, '1970-01-01') DESC, IdMission DESC
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
    <title>Admin • Missions</title>
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
            <a class="dash-link is-active" href="missions.php">Missions</a>
            <a class="dash-link" href="evenements.php">Événements</a>
            <a class="dash-link" href="presse.php">Presse</a>
            <a class="dash-link" href="formulaire.php">Formulaires</a>
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
                <h1 class="dash-h1">Gestion des missions</h1>
                <p class="dash-sub">Ajouter, modifier, rechercher et gérer les missions.</p>
            </div>
            <div class="dash-top-actions">
                <a class="dash-btn dash-btn-primary" href="missions.php">+ Nouvelle mission</a>
            </div>
        </header>

        <?php if ($flashSuccess): ?>
            <div class="dash-card ms-flash ms-flash-success">✅ <?= h($flashSuccess) ?></div>
            <div class="ms-spacer-10"></div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="dash-card ms-flash ms-flash-error">❌ <?= h($flashError) ?></div>
            <div class="ms-spacer-10"></div>
        <?php endif; ?>

        <section class="dash-card ms-form-card">
            <div class="dash-card-head">
                <div class="dash-card-title">
                    <?= $edit ? "Modifier la mission #".(int)$edit['IdMission'] : "Ajouter une mission" ?>
                </div>
                <div class="dash-card-meta"><?= $edit ? "Mode édition" : "Mode création" ?></div>
            </div>

            <div class="dash-card-body">
                <form method="post" action="missions.php<?= $edit ? '?mode=edit&id='.(int)$edit['IdMission'] : '' ?>">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['IdMission'] : 0 ?>">

                    <div class="ms-grid-3">
                        <div class="ms-span-2">
                            <label>Titre *</label>
                            <input class="dash-input" name="TitreMission" value="<?= h($edit['TitreMission'] ?? '') ?>" required>
                        </div>

                        <div>
                            <label>Catégorie</label>
                            <input class="dash-input" name="CategorieMission" value="<?= h($edit['CategorieMission'] ?? '') ?>">
                        </div>

                        <div class="ms-span-3">
                            <label>Description</label>
                            <textarea class="dash-input ms-textarea" name="DescriptionMission" rows="3"><?= h($edit['DescriptionMission'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label>Lieu</label>
                            <input class="dash-input" name="LieuMission" value="<?= h($edit['LieuMission'] ?? '') ?>">
                        </div>

                        <div>
                            <label>Date/Heure début</label>
                            <input class="dash-input" type="datetime-local" name="DateHeureDebut"
                                   value="<?= h(dt_local_value($edit['DateHeureDebut'] ?? '')) ?>">
                        </div>

                        <div>
                            <label>Date/Heure fin</label>
                            <input class="dash-input" type="datetime-local" name="DateHeureFin"
                                   value="<?= h(dt_local_value($edit['DateHeureFin'] ?? '')) ?>">
                        </div>

                        <div>
                            <label>Nb bénévoles attendus</label>
                            <input class="dash-input" type="number" min="0" name="NbBenevolesAttendus"
                                   value="<?= h($edit['NbBenevolesAttendus'] ?? '') ?>">
                        </div>

                        <div class="ms-span-2">
                            <label>Matériel nécessaire</label>
                            <input class="dash-input" name="MaterielNecessaire" value="<?= h($edit['MaterielNecessaire'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="ms-actions">
                        <button class="dash-btn dash-btn-primary" type="submit">
                            <?= $edit ? "Enregistrer" : "Créer" ?>
                        </button>

                        <?php if ($edit): ?>
                            <a class="dash-btn" href="missions.php">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Liste des missions</div>
                <div class="dash-card-meta"><?= (int)$total ?> résultat(s)</div>
            </div>

            <div class="dash-card-body ms-table-body-padfix">
                <form method="get" action="missions.php" class="ms-searchbar">
                    <input class="dash-input ms-search-input" name="q" value="<?= h($q) ?>"
                           placeholder="Rechercher (titre, catégorie, lieu)">
                    <button class="dash-btn" type="submit">Rechercher</button>
                    <?php if ($q !== ''): ?>
                        <a class="dash-btn" href="missions.php">Reset</a>
                    <?php endif; ?>
                </form>

                <div class="dash-tablewrap">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Lieu</th>
                            <th>Début</th>
                            <th>Fin</th>
                            <th>Attendus</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="8" class="dash-td-empty">Aucune mission trouvée.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $m): ?>
                                <tr>
                                    <td><?= (int)$m['IdMission'] ?></td>
                                    <td><?= h($m['TitreMission'] ?? '') ?></td>
                                    <td><?= h($m['CategorieMission'] ?? '—') ?></td>
                                    <td><?= h($m['LieuMission'] ?? '—') ?></td>
                                    <td><?= h($m['DateHeureDebut'] ?? '—') ?></td>
                                    <td><?= h($m['DateHeureFin'] ?? '—') ?></td>
                                    <td><?= (int)($m['NbBenevolesAttendus'] ?? 0) ?></td>
                                    <td class="ms-table-actions">
                                        <a class="dash-btn" href="missions.php?mode=edit&id=<?= (int)$m['IdMission'] ?>">Modifier</a>

                                        <form method="post" action="missions.php" onsubmit="return confirm('Supprimer cette mission ?');">
                                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$m['IdMission'] ?>">
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
                    <div class="ms-pagination">
                        <a class="dash-btn" href="missions.php?p=1<?= $q!=='' ? '&q='.urlencode($q) : '' ?>">« Début</a>
                        <a class="dash-btn" href="missions.php?p=<?= max(1,$page-1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">‹ Préc</a>

                        <span class="ms-pagination-info">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>

                        <a class="dash-btn" href="missions.php?p=<?= min($totalPages,$page+1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Suiv ›</a>
                        <a class="dash-btn" href="missions.php?p=<?= (int)$totalPages ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Fin »</a>
                    </div>
                <?php endif; ?>

            </div>
        </section>

    </main>
</div>

</body>
</html>
