<?php
require_once __DIR__ . "/../init.php";


if (empty($_SESSION['auth'])) { $_SESSION['login_error']="Vous devez être connecté."; header("Location: ../login.php"); exit; }
if (($_SESSION['auth']['role'] ?? '') !== 'ADMIN') { $_SESSION['login_error']="Accès réservé à l'administration."; header("Location: ../index.php"); exit; }


function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function flash_get($k){ if(!empty($_SESSION[$k])){ $v=$_SESSION[$k]; unset($_SESSION[$k]); return $v; } return null; }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$adminName  = trim(($_SESSION['auth']['prenom'] ?? 'System').' '.($_SESSION['auth']['nom'] ?? 'Admin'));
$adminEmail = $_SESSION['auth']['email'] ?? 'admin@site.com';

$mode = $_GET['mode'] ?? '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf'] ?? '') !== $csrf) { $_SESSION['flash_error']="Sécurité: token invalide."; header("Location: partenaires.php"); exit; }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId <= 0) { $_SESSION['flash_error']="ID invalide."; header("Location: partenaires.php"); exit; }

        try {
            $st = $pdo->prepare("DELETE FROM Partenaire WHERE IdPartenaire=:id");
            $st->execute([':id'=>$delId]);
            $_SESSION['flash_success']="Partenaire supprimé.";
        } catch (PDOException $e) {
            // FK possible via Soutenir / Implante
            $_SESSION['flash_error']="Suppression impossible (liens existants).";
        }
        header("Location: partenaires.php"); exit;
    }

    if ($action === 'save') {
        $formId = (int)($_POST['id'] ?? 0);

        $NomPartenaire  = trim($_POST['NomPartenaire'] ?? '');
        $PrenomPartenaire = trim($_POST['PrenomPartenaire'] ?? '');
        $TypePartenaire = trim($_POST['TypePartenaire'] ?? '');
        $TypeSoutienPartenaire = trim($_POST['TypeSoutienPartenaire'] ?? '');
        $ContactPrincipalPartenaire = trim($_POST['ContactPrincipalPartenaire'] ?? '');
        $IdFinancement = trim($_POST['IdFinancement'] ?? '');

        if ($NomPartenaire === '') {
            $_SESSION['flash_error']="Le nom du partenaire est obligatoire.";
            header("Location: partenaires.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
        }

        $IdFinancement = ($IdFinancement !== '' ? (int)$IdFinancement : null);
        if ($IdFinancement === null || $IdFinancement <= 0) {
            $_SESSION['flash_error']="IdFinancement est obligatoire (FK).";
            header("Location: partenaires.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
        }

        if ($formId === 0) {
            try {
                $st = $pdo->prepare("
                    INSERT INTO Partenaire
                    (NomPartenaire, PrenomPartenaire, TypePartenaire, TypeSoutienPartenaire, ContactPrincipalPartenaire, IdFinancement)
                    VALUES (:Nom,:Prenom,:Type,:Soutien,:Contact,:IdFin)
                ");
                $st->execute([
                        ':Nom'=>$NomPartenaire,
                        ':Prenom'=>($PrenomPartenaire!==''?$PrenomPartenaire:null),
                        ':Type'=>($TypePartenaire!==''?$TypePartenaire:null),
                        ':Soutien'=>($TypeSoutienPartenaire!==''?$TypeSoutienPartenaire:null),
                        ':Contact'=>($ContactPrincipalPartenaire!==''?$ContactPrincipalPartenaire:null),
                        ':IdFin'=>$IdFinancement
                ]);
                $_SESSION['flash_success']="Partenaire ajouté.";
                header("Location: partenaires.php"); exit;
            } catch (PDOException $e) {
                $_SESSION['flash_error']="Erreur SQL (IdFinancement invalide ?).";
                header("Location: partenaires.php"); exit;
            }
        }

        try {
            $st = $pdo->prepare("
                UPDATE Partenaire SET
                    NomPartenaire=:Nom,
                    PrenomPartenaire=:Prenom,
                    TypePartenaire=:Type,
                    TypeSoutienPartenaire=:Soutien,
                    ContactPrincipalPartenaire=:Contact,
                    IdFinancement=:IdFin
                WHERE IdPartenaire=:Id
            ");
            $st->execute([
                    ':Nom'=>$NomPartenaire,
                    ':Prenom'=>($PrenomPartenaire!==''?$PrenomPartenaire:null),
                    ':Type'=>($TypePartenaire!==''?$TypePartenaire:null),
                    ':Soutien'=>($TypeSoutienPartenaire!==''?$TypeSoutienPartenaire:null),
                    ':Contact'=>($ContactPrincipalPartenaire!==''?$ContactPrincipalPartenaire:null),
                    ':IdFin'=>$IdFinancement,
                    ':Id'=>$formId
            ]);
            $_SESSION['flash_success']="Partenaire mis à jour.";
            header("Location: partenaires.php"); exit;
        } catch (PDOException $e) {
            $_SESSION['flash_error']="Erreur SQL (IdFinancement invalide ?).";
            header("Location: partenaires.php?mode=edit&id=".$formId); exit;
        }
    }
}


$edit = null;
if ($mode === 'edit' && $id > 0) {
    $st = $pdo->prepare("SELECT * FROM Partenaire WHERE IdPartenaire=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$edit) { $_SESSION['flash_error']="Partenaire introuvable."; header("Location: partenaires.php"); exit; }
}

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page-1)*$perPage;

$where = "";
$params = [];

if ($q !== '') {
    $where = "WHERE (NomPartenaire LIKE :q OR TypePartenaire LIKE :q OR ContactPrincipalPartenaire LIKE :q)";
    $params[':q'] = "%$q%";
}

$st = $pdo->prepare("SELECT COUNT(*) c FROM Partenaire $where");
$st->execute($params);
$total = (int)($st->fetchColumn() ?: 0);
$totalPages = max(1, (int)ceil($total / $perPage));

$st = $pdo->prepare("
    SELECT IdPartenaire, NomPartenaire, TypePartenaire, TypeSoutienPartenaire, ContactPrincipalPartenaire, IdFinancement
    FROM Partenaire
    $where
    ORDER BY IdPartenaire DESC
    LIMIT $perPage OFFSET $offset
");
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];


$finList = $pdo->query("SELECT IdFinancement, TypeFinancement, MontantFinancement, AnneeFinancement FROM Financement ORDER BY IdFinancement DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

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
            <a class="dash-link" href="regions.php">Régions</a>
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
                <p class="dash-sub">CRUD + recherche + pagination (FK vers Financement).</p>
            </div>
            <div class="dash-top-actions">
                <a class="dash-btn dash-btn-primary" href="partenaires.php">+ Nouveau partenaire</a>
            </div>
        </header>

        <?php if ($flashSuccess): ?>
            <div class="dash-card" style="padding:12px 14px; border-color:#c7f0d6; background:#f0fff5;">✅ <?= h($flashSuccess) ?></div>
            <div style="height:10px"></div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="dash-card" style="padding:12px 14px; border-color:#ffd0d0; background:#fff5f5;">❌ <?= h($flashError) ?></div>
            <div style="height:10px"></div>
        <?php endif; ?>


        <section class="dash-card" style="margin-bottom:12px;">
            <div class="dash-card-head">
                <div class="dash-card-title"><?= $edit ? "Modifier le partenaire #".(int)$edit['IdPartenaire'] : "Ajouter un partenaire" ?></div>
                <div class="dash-card-meta"><?= $edit ? "Mode édition" : "Mode création" ?></div>
            </div>

            <div class="dash-card-body">
                <form method="post" action="partenaires.php<?= $edit ? '?mode=edit&id='.(int)$edit['IdPartenaire'] : '' ?>">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['IdPartenaire'] : 0 ?>">

                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px;">
                        <div style="grid-column: span 2;">
                            <label>Nom *</label>
                            <input class="dash-input" name="NomPartenaire" value="<?= h($edit['NomPartenaire'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label>Prénom</label>
                            <input class="dash-input" name="PrenomPartenaire" value="<?= h($edit['PrenomPartenaire'] ?? '') ?>">
                        </div>

                        <div>
                            <label>Type partenaire</label>
                            <input class="dash-input" name="TypePartenaire" value="<?= h($edit['TypePartenaire'] ?? '') ?>">
                        </div>
                        <div>
                            <label>Type soutien</label>
                            <input class="dash-input" name="TypeSoutienPartenaire" value="<?= h($edit['TypeSoutienPartenaire'] ?? '') ?>">
                        </div>
                        <div>
                            <label>Contact principal</label>
                            <input class="dash-input" name="ContactPrincipalPartenaire" value="<?= h($edit['ContactPrincipalPartenaire'] ?? '') ?>">
                        </div>

                        <div style="grid-column: span 3;">
                            <label>Financement associé (IdFinancement) *</label>
                            <select class="dash-input" name="IdFinancement" required>
                                <option value="">— Choisir —</option>
                                <?php
                                $selected = (string)($edit['IdFinancement'] ?? '');
                                foreach ($finList as $f):
                                    $label = "#".$f['IdFinancement']." • ".($f['TypeFinancement'] ?? '—')." • ".number_format((float)($f['MontantFinancement'] ?? 0),2,',',' ')."€ • ".($f['AnneeFinancement'] ?? '');
                                    ?>
                                    <option value="<?= (int)$f['IdFinancement'] ?>" <?= ((string)$f['IdFinancement'] === $selected) ? 'selected' : '' ?>>
                                        <?= h($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div style="margin-top:6px; color:#6b7c98; font-size:.85rem;">
                                Si tu n’as aucun financement, crée d’abord dans “Dons/Financements”.
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
                        <button class="dash-btn dash-btn-primary" type="submit"><?= $edit ? "Enregistrer" : "Créer" ?></button>
                        <?php if ($edit): ?><a class="dash-btn" href="partenaires.php">Annuler</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </section>


        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Liste des partenaires</div>
                <div class="dash-card-meta"><?= (int)$total ?> résultat(s)</div>
            </div>

            <div class="dash-card-body" style="padding-top:0;">
                <form method="get" action="partenaires.php" style="display:flex; gap:10px; flex-wrap:wrap; margin:12px 0;">
                    <input class="dash-input" style="flex:1; min-width:240px;" name="q" value="<?= h($q) ?>" placeholder="Rechercher (nom, type, contact)">
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
                            <th>Soutien</th>
                            <th>Contact</th>
                            <th>IdFinancement</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($rows)): ?>
                            <tr><td colspan="7" class="dash-td-empty">Aucun partenaire trouvé.</td></tr>
                        <?php else: foreach($rows as $p): ?>
                            <tr>
                                <td><?= (int)$p['IdPartenaire'] ?></td>
                                <td><?= h($p['NomPartenaire'] ?? '') ?></td>
                                <td><?= h($p['TypePartenaire'] ?? '—') ?></td>
                                <td><?= h($p['TypeSoutienPartenaire'] ?? '—') ?></td>
                                <td><?= h($p['ContactPrincipalPartenaire'] ?? '—') ?></td>
                                <td><?= (int)$p['IdFinancement'] ?></td>
                                <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <a class="dash-btn" href="partenaires.php?mode=edit&id=<?= (int)$p['IdPartenaire'] ?>">Modifier</a>
                                    <form method="post" action="partenaires.php" onsubmit="return confirm('Supprimer ce partenaire ?');">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$p['IdPartenaire'] ?>">
                                        <button class="dash-btn" type="submit">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; align-items:center;">
                        <a class="dash-btn" href="partenaires.php?p=1<?= $q!=='' ? '&q='.urlencode($q) : '' ?>">« Début</a>
                        <a class="dash-btn" href="partenaires.php?p=<?= max(1,$page-1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">‹ Préc</a>
                        <span style="color:#6b7c98;">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>
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
