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

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function flash_get($k){
    if(!empty($_SESSION[$k])){
        $v=$_SESSION[$k];
        unset($_SESSION[$k]);
        return $v;
    }
    return null;
}

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$adminName  = trim(($_SESSION['auth']['prenom'] ?? 'System').' '.($_SESSION['auth']['nom'] ?? 'Admin'));
$adminEmail = $_SESSION['auth']['email'] ?? 'admin@site.com';

$mode = $_GET['mode'] ?? '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* =========================
   POST ACTIONS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (($_POST['csrf'] ?? '') !== $csrf) {
        $_SESSION['flash_error'] = "Sécurité: token invalide.";
        header("Location: partenaires.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId <= 0) {
            $_SESSION['flash_error'] = "ID invalide.";
            header("Location: partenaires.php");
            exit;
        }

        try {
            $st = $pdo->prepare("DELETE FROM Partenaire WHERE IdPartenaire = :id");
            $st->execute([':id' => $delId]);
            $_SESSION['flash_success'] = "Partenaire supprimé.";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Suppression impossible (partenaire lié à des financements / événements / etc.).";
        }
        header("Location: partenaires.php");
        exit;
    }

    if ($action === 'save') {
        $formId = (int)($_POST['id'] ?? 0);

        $NomPartenaire       = trim($_POST['NomPartenaire'] ?? '');
        $TypePartenaire      = trim($_POST['TypePartenaire'] ?? '');
        $EmailPartenaire     = trim($_POST['EmailPartenaire'] ?? '');
        $TelephonePartenaire = trim($_POST['TelephonePartenaire'] ?? '');
        $AdressePartenaire   = trim($_POST['AdressePartenaire'] ?? '');
        $SiteWebPartenaire   = trim($_POST['SiteWebPartenaire'] ?? '');
        $NotePartenaire      = trim($_POST['NotePartenaire'] ?? '');

        if ($NomPartenaire === '') {
            $_SESSION['flash_error'] = "Le nom du partenaire est obligatoire.";
            header("Location: partenaires.php" . ($formId ? "?mode=edit&id=".$formId : ""));
            exit;
        }

        if ($EmailPartenaire !== '' && !filter_var($EmailPartenaire, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "Email partenaire invalide.";
            header("Location: partenaires.php" . ($formId ? "?mode=edit&id=".$formId : ""));
            exit;
        }

        // CREATE
        if ($formId === 0) {
            try {
                $st = $pdo->prepare("
                    INSERT INTO Partenaire
                    (NomPartenaire, TypePartenaire, EmailPartenaire, TelephonePartenaire, AdressePartenaire, SiteWebPartenaire, NotePartenaire)
                    VALUES
                    (:Nom,:Type,:Email,:Tel,:Adr,:Web,:Note)
                ");
                $st->execute([
                        ':Nom'  => $NomPartenaire,
                        ':Type' => ($TypePartenaire!==''?$TypePartenaire:null),
                        ':Email'=> ($EmailPartenaire!==''?$EmailPartenaire:null),
                        ':Tel'  => ($TelephonePartenaire!==''?$TelephonePartenaire:null),
                        ':Adr'  => ($AdressePartenaire!==''?$AdressePartenaire:null),
                        ':Web'  => ($SiteWebPartenaire!==''?$SiteWebPartenaire:null),
                        ':Note' => ($NotePartenaire!==''?$NotePartenaire:null),
                ]);
                $_SESSION['flash_success'] = "Partenaire ajouté.";
                header("Location: partenaires.php");
                exit;
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Erreur SQL lors de l'ajout.";
                header("Location: partenaires.php");
                exit;
            }
        }

        // UPDATE
        try {
            $st = $pdo->prepare("
                UPDATE Partenaire SET
                    NomPartenaire=:Nom,
                    TypePartenaire=:Type,
                    EmailPartenaire=:Email,
                    TelephonePartenaire=:Tel,
                    AdressePartenaire=:Adr,
                    SiteWebPartenaire=:Web,
                    NotePartenaire=:Note
                WHERE IdPartenaire=:Id
            ");
            $st->execute([
                    ':Nom'  => $NomPartenaire,
                    ':Type' => ($TypePartenaire!==''?$TypePartenaire:null),
                    ':Email'=> ($EmailPartenaire!==''?$EmailPartenaire:null),
                    ':Tel'  => ($TelephonePartenaire!==''?$TelephonePartenaire:null),
                    ':Adr'  => ($AdressePartenaire!==''?$AdressePartenaire:null),
                    ':Web'  => ($SiteWebPartenaire!==''?$SiteWebPartenaire:null),
                    ':Note' => ($NotePartenaire!==''?$NotePartenaire:null),
                    ':Id'   => $formId
            ]);
            $_SESSION['flash_success'] = "Partenaire mis à jour.";
            header("Location: partenaires.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Erreur SQL lors de la mise à jour.";
            header("Location: partenaires.php?mode=edit&id=".$formId);
            exit;
        }
    }
}

/* =========================
   EDIT MODE
========================= */
$edit = null;
if ($mode === 'edit' && $id > 0) {
    $st = $pdo->prepare("SELECT * FROM Partenaire WHERE IdPartenaire=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$edit) {
        $_SESSION['flash_error'] = "Partenaire introuvable.";
        header("Location: partenaires.php");
        exit;
    }
}

/* =========================
   SEARCH + PAGINATION
========================= */
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "";
$params = [];
if ($q !== '') {
    $where = "WHERE (NomPartenaire LIKE :q OR TypePartenaire LIKE :q OR EmailPartenaire LIKE :q)";
    $params[':q'] = "%$q%";
}

$st = $pdo->prepare("SELECT COUNT(*) c FROM Partenaire $where");
$st->execute($params);
$total = (int)($st->fetchColumn() ?: 0);
$totalPages = max(1, (int)ceil($total / $perPage));

$st = $pdo->prepare("
    SELECT IdPartenaire, NomPartenaire, TypePartenaire, EmailPartenaire, TelephonePartenaire, SiteWebPartenaire
    FROM Partenaire
    $where
    ORDER BY IdPartenaire DESC
    LIMIT $perPage OFFSET $offset
");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

$flashSuccess = flash_get('flash_success');
$flashError   = flash_get('flash_error');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Admin • Partenaires</title>
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
            <a class="dash-link" href="formulaire.php">Formulaires</a>
            <a class="dash-link is-active" href="partenaires.php">Partenaires</a>
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
                <h1 class="dash-h1">Gestion des partenaires</h1>
                <p class="dash-sub">Ajouter, modifier, rechercher et gérer les partenaires.</p>
            </div>
            <div class="dash-top-actions">
                <a class="dash-btn dash-btn-primary" href="partenaires.php">+ Nouveau partenaire</a>
            </div>
        </header>

        <?php if ($flashSuccess): ?>
            <div class="dash-card dash-flash dash-flash-success">✅ <?= h($flashSuccess) ?></div>
            <div class="dash-spacer-10"></div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="dash-card dash-flash dash-flash-error">❌ <?= h($flashError) ?></div>
            <div class="dash-spacer-10"></div>
        <?php endif; ?>

        <!-- FORM -->
        <section class="dash-card dash-mb-12">
            <div class="dash-card-head">
                <div class="dash-card-title">
                    <?= $edit ? "Modifier le partenaire #".(int)$edit['IdPartenaire'] : "Ajouter un partenaire" ?>
                </div>
                <div class="dash-card-meta"><?= $edit ? "Mode édition" : "Mode création" ?></div>
            </div>

            <div class="dash-card-body">
                <form method="post" action="partenaires.php<?= $edit ? '?mode=edit&id='.(int)$edit['IdPartenaire'] : '' ?>">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['IdPartenaire'] : 0 ?>">

                    <div class="dash-form-grid">
                        <div>
                            <label>Nom *</label>
                            <input class="dash-input" name="NomPartenaire" value="<?= h($edit['NomPartenaire'] ?? '') ?>" required>
                        </div>

                        <div>
                            <label>Type</label>
                            <input class="dash-input" name="TypePartenaire" value="<?= h($edit['TypePartenaire'] ?? '') ?>" placeholder="Entreprise / Institution / ...">
                        </div>

                        <div>
                            <label>Email</label>
                            <input class="dash-input" name="EmailPartenaire" value="<?= h($edit['EmailPartenaire'] ?? '') ?>" placeholder="contact@...">
                        </div>

                        <div>
                            <label>Téléphone</label>
                            <input class="dash-input" name="TelephonePartenaire" value="<?= h($edit['TelephonePartenaire'] ?? '') ?>">
                        </div>

                        <div class="dash-col-span-2">
                            <label>Adresse</label>
                            <input class="dash-input" name="AdressePartenaire" value="<?= h($edit['AdressePartenaire'] ?? '') ?>">
                        </div>

                        <div>
                            <label>Site web</label>
                            <input class="dash-input" name="SiteWebPartenaire" value="<?= h($edit['SiteWebPartenaire'] ?? '') ?>" placeholder="https://...">
                        </div>

                        <div class="dash-col-span-3">
                            <label>Note</label>
                            <textarea class="dash-input dash-textarea" name="NotePartenaire" rows="3"><?= h($edit['NotePartenaire'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="dash-form-actions">
                        <button class="dash-btn dash-btn-primary" type="submit"><?= $edit ? "Enregistrer" : "Créer" ?></button>
                        <?php if ($edit): ?><a class="dash-btn" href="partenaires.php">Annuler</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <!-- LIST -->
        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Liste des partenaires</div>
                <div class="dash-card-meta"><?= (int)$total ?> résultat(s)</div>
            </div>

            <div class="dash-card-body dash-pt-0">
                <form method="get" action="partenaires.php" class="dash-searchbar">
                    <input class="dash-input dash-search-input" name="q" value="<?= h($q) ?>" placeholder="Rechercher (nom, type, email)">
                    <button class="dash-btn" type="submit">Rechercher</button>
                    <?php if ($q !== ''): ?><a class="dash-btn" href="partenaires.php">Reset</a><?php endif; ?>
                </form>

                <div class="dash-tablewrap">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Site</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="dash-td-empty">Aucun partenaire trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $p): ?>
                                <tr>
                                    <td><?= (int)$p['IdPartenaire'] ?></td>
                                    <td><?= h($p['NomPartenaire'] ?? '') ?></td>
                                    <td><?= h($p['TypePartenaire'] ?? '—') ?></td>
                                    <td>
                                        <?php if (!empty($p['EmailPartenaire'])): ?>
                                            <a href="mailto:<?= h($p['EmailPartenaire']) ?>"><?= h($p['EmailPartenaire']) ?></a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td><?= h($p['TelephonePartenaire'] ?? '—') ?></td>
                                    <td>
                                        <?php if (!empty($p['SiteWebPartenaire'])): ?>
                                            <a href="<?= h($p['SiteWebPartenaire']) ?>" target="_blank" rel="noopener">Voir</a>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="dash-row-actions">
                                        <a class="dash-btn" href="partenaires.php?mode=edit&id=<?= (int)$p['IdPartenaire'] ?>">Modifier</a>

                                        <form method="post" action="partenaires.php" onsubmit="return confirm('Supprimer ce partenaire ?');">
                                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$p['IdPartenaire'] ?>">
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
                        <a class="dash-btn" href="partenaires.php?p=1<?= $q!=='' ? '&q='.urlencode($q) : '' ?>">« Début</a>
                        <a class="dash-btn" href="partenaires.php?p=<?= max(1,$page-1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">‹ Préc</a>

                        <span class="dash-pagination-info">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>

                        <a class="dash-btn" href="partenaires.php?p=<?= min($totalPages,$page+1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Suiv ›</a>
                        <a class="dash-btn" href="partenaires.php?p=<?= (int)$totalPages ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Fin »</a>
                    </div>
                <?php endif; ?>

            </div>
        </section>

    </main>
</div>

</body>
</html>
