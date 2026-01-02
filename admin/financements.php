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
    if (($_POST['csrf'] ?? '') !== $csrf) { $_SESSION['flash_error']="Sécurité: token invalide."; header("Location: financements.php"); exit; }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId <= 0) { $_SESSION['flash_error']="ID invalide."; header("Location: financements.php"); exit; }

        try {
            $st = $pdo->prepare("DELETE FROM Financement WHERE IdFinancement=:id");
            $st->execute([':id'=>$delId]);
            $_SESSION['flash_success']="Financement supprimé.";
        } catch (PDOException $e) {
            // FK via Partenaire
            $_SESSION['flash_error']="Suppression impossible (partenaires liés).";
        }
        header("Location: financements.php"); exit;
    }

    if ($action === 'save') {
        $formId = (int)($_POST['id'] ?? 0);

        $MontantFinancement = trim($_POST['MontantFinancement'] ?? '');
        $TypeFinancement    = trim($_POST['TypeFinancement'] ?? '');
        $AnneeFinancement   = trim($_POST['AnneeFinancement'] ?? '');
        $UsagePrevu         = trim($_POST['UsagePrevu'] ?? '');
        $Financeur          = trim($_POST['Financeur'] ?? '');

        if ($MontantFinancement === '') {
            $_SESSION['flash_error']="Montant obligatoire.";
            header("Location: financements.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
        }

        // conversions
        $MontantFinancement = (float)str_replace(',', '.', $MontantFinancement);
        $AnneeFinancement = ($AnneeFinancement !== '' ? (int)$AnneeFinancement : null);
        if ($AnneeFinancement !== null && ($AnneeFinancement < 1900 || $AnneeFinancement > 2200)) {
            $_SESSION['flash_error']="Année invalide.";
            header("Location: financements.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
        }

        if ($formId === 0) {
            try {
                $st = $pdo->prepare("
                    INSERT INTO Financement
                    (MontantFinancement, TypeFinancement, AnneeFinancement, UsagePrevu, Financeur)
                    VALUES (:Montant,:Type,:Annee,:Usage,:Financeur)
                ");
                $st->execute([
                        ':Montant'=>$MontantFinancement,
                        ':Type'=>($TypeFinancement!==''?$TypeFinancement:null),
                        ':Annee'=>$AnneeFinancement,
                        ':Usage'=>($UsagePrevu!==''?$UsagePrevu:null),
                        ':Financeur'=>($Financeur!==''?$Financeur:null),
                ]);
                $_SESSION['flash_success']="Financement ajouté.";
                header("Location: financements.php"); exit;
            } catch (PDOException $e) {
                $_SESSION['flash_error']="Erreur SQL lors de l'ajout.";
                header("Location: financements.php"); exit;
            }
        }

        try {
            $st = $pdo->prepare("
                UPDATE Financement SET
                    MontantFinancement=:Montant,
                    TypeFinancement=:Type,
                    AnneeFinancement=:Annee,
                    UsagePrevu=:Usage,
                    Financeur=:Financeur
                WHERE IdFinancement=:Id
            ");
            $st->execute([
                    ':Montant'=>$MontantFinancement,
                    ':Type'=>($TypeFinancement!==''?$TypeFinancement:null),
                    ':Annee'=>$AnneeFinancement,
                    ':Usage'=>($UsagePrevu!==''?$UsagePrevu:null),
                    ':Financeur'=>($Financeur!==''?$Financeur:null),
                    ':Id'=>$formId
            ]);
            $_SESSION['flash_success']="Financement mis à jour.";
            header("Location: financements.php"); exit;
        } catch (PDOException $e) {
            $_SESSION['flash_error']="Erreur SQL lors de la mise à jour.";
            header("Location: financements.php?mode=edit&id=".$formId); exit;
        }
    }
}


$edit = null;
if ($mode === 'edit' && $id > 0) {
    $st = $pdo->prepare("SELECT * FROM Financement WHERE IdFinancement=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$edit) { $_SESSION['flash_error']="Financement introuvable."; header("Location: financements.php"); exit; }
}


$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page-1)*$perPage;

$where = "";
$params = [];
if ($q !== '') {
    $where = "WHERE (TypeFinancement LIKE :q OR UsagePrevu LIKE :q OR Financeur LIKE :q)";
    $params[':q'] = "%$q%";
}

$st = $pdo->prepare("SELECT COUNT(*) c FROM Financement $where");
$st->execute($params);
$total = (int)($st->fetchColumn() ?: 0);
$totalPages = max(1, (int)ceil($total / $perPage));

$st = $pdo->prepare("
    SELECT IdFinancement, MontantFinancement, TypeFinancement, AnneeFinancement, UsagePrevu, Financeur
    FROM Financement
    $where
    ORDER BY IdFinancement DESC
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
    <title>Admin • Dons / Financements</title>
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
            <a class="dash-link" href="partenaires.php">Partenaires</a>
            <a class="dash-link is-active" href="financements.php">Dons / Financements</a>

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
                <h1 class="dash-h1">Dons / Financements</h1>
                <p class="dash-sub">CRUD + recherche + pagination (source des partenaires).</p>
            </div>
            <div class="dash-top-actions">
                <a class="dash-btn dash-btn-primary" href="financements.php">+ Nouveau financement</a>
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

        <!-- FORM -->
        <section class="dash-card" style="margin-bottom:12px;">
            <div class="dash-card-head">
                <div class="dash-card-title"><?= $edit ? "Modifier le financement #".(int)$edit['IdFinancement'] : "Ajouter un financement" ?></div>
                <div class="dash-card-meta"><?= $edit ? "Mode édition" : "Mode création" ?></div>
            </div>

            <div class="dash-card-body">
                <form method="post" action="financements.php<?= $edit ? '?mode=edit&id='.(int)$edit['IdFinancement'] : '' ?>">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['IdFinancement'] : 0 ?>">

                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px;">
                        <div>
                            <label>Montant (€) *</label>
                            <input class="dash-input" name="MontantFinancement" value="<?= h($edit['MontantFinancement'] ?? '') ?>" required placeholder="ex: 1500.00">
                        </div>
                        <div>
                            <label>Type</label>
                            <input class="dash-input" name="TypeFinancement" value="<?= h($edit['TypeFinancement'] ?? '') ?>" placeholder="Don / Subvention / ...">
                        </div>
                        <div>
                            <label>Année</label>
                            <input class="dash-input" type="number" min="1900" max="2200" name="AnneeFinancement" value="<?= h($edit['AnneeFinancement'] ?? '') ?>" placeholder="<?= date('Y') ?>">
                        </div>

                        <div style="grid-column: span 2;">
                            <label>Usage prévu</label>
                            <input class="dash-input" name="UsagePrevu" value="<?= h($edit['UsagePrevu'] ?? '') ?>" placeholder="Matériel / Transport / ...">
                        </div>
                        <div>
                            <label>Financeur</label>
                            <input class="dash-input" name="Financeur" value="<?= h($edit['Financeur'] ?? '') ?>" placeholder="Nom du financeur">
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
                        <button class="dash-btn dash-btn-primary" type="submit"><?= $edit ? "Enregistrer" : "Créer" ?></button>
                        <?php if ($edit): ?><a class="dash-btn" href="financements.php">Annuler</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </section>


        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Liste des financements</div>
                <div class="dash-card-meta"><?= (int)$total ?> résultat(s)</div>
            </div>

            <div class="dash-card-body" style="padding-top:0;">
                <form method="get" action="financements.php" style="display:flex; gap:10px; flex-wrap:wrap; margin:12px 0;">
                    <input class="dash-input" style="flex:1; min-width:240px;" name="q" value="<?= h($q) ?>" placeholder="Rechercher (type, usage, financeur)">
                    <button class="dash-btn" type="submit">Rechercher</button>
                    <?php if ($q !== ''): ?><a class="dash-btn" href="financements.php">Reset</a><?php endif; ?>
                </form>

                <div class="dash-tablewrap">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Montant</th>
                            <th>Type</th>
                            <th>Année</th>
                            <th>Usage</th>
                            <th>Financeur</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($rows)): ?>
                            <tr><td colspan="7" class="dash-td-empty">Aucun financement trouvé.</td></tr>
                        <?php else: foreach($rows as $f): ?>
                            <tr>
                                <td><?= (int)$f['IdFinancement'] ?></td>
                                <td><?= number_format((float)$f['MontantFinancement'], 2, ',', ' ') ?> €</td>
                                <td><?= h($f['TypeFinancement'] ?? '—') ?></td>
                                <td><?= h($f['AnneeFinancement'] ?? '—') ?></td>
                                <td><?= h($f['UsagePrevu'] ?? '—') ?></td>
                                <td><?= h($f['Financeur'] ?? '—') ?></td>
                                <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <a class="dash-btn" href="financements.php?mode=edit&id=<?= (int)$f['IdFinancement'] ?>">Modifier</a>
                                    <form method="post" action="financements.php" onsubmit="return confirm('Supprimer ce financement ? (Attention: partenaires liés)');">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$f['IdFinancement'] ?>">
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
                        <a class="dash-btn" href="financements.php?p=1<?= $q!=='' ? '&q='.urlencode($q) : '' ?>">« Début</a>
                        <a class="dash-btn" href="financements.php?p=<?= max(1,$page-1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">‹ Préc</a>
                        <span style="color:#6b7c98;">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>
                        <a class="dash-btn" href="financements.php?p=<?= min($totalPages,$page+1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Suiv ›</a>
                        <a class="dash-btn" href="financements.php?p=<?= (int)$totalPages ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Fin »</a>
                    </div>
                <?php endif; ?>

            </div>
        </section>
    </main>
</div>


</body>
</html>
