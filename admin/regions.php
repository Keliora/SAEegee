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


$benevoles = $pdo->query("SELECT IdBenevole, PrenomBenevole, NomBenevole FROM Benevole ORDER BY IdBenevole DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$events    = $pdo->query("SELECT IdEvenement, NomEvenement, DateEvenement FROM Evenement ORDER BY COALESCE(DateEvenement,'1970-01-01') DESC, IdEvenement DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$missions  = $pdo->query("SELECT IdMission, TitreMission FROM Mission ORDER BY IdMission DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf'] ?? '') !== $csrf) { $_SESSION['flash_error']="Sécurité: token invalide."; header("Location: regions.php"); exit; }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId <= 0) { $_SESSION['flash_error']="ID invalide."; header("Location: regions.php"); exit; }

        try {
            $st = $pdo->prepare("DELETE FROM Region WHERE IdRegion=:id");
            $st->execute([':id'=>$delId]);
            $_SESSION['flash_success']="Région supprimée.";
        } catch (PDOException $e) {
            // FK possible via Implante/Localiser
            $_SESSION['flash_error']="Suppression impossible (liens existants).";
        }
        header("Location: regions.php"); exit;
    }

    if ($action === 'save') {
        $formId = (int)($_POST['id'] ?? 0);

        $NomRegion = trim($_POST['NomRegion'] ?? '');
        $TypeRegion = trim($_POST['TypeRegion'] ?? '');
        $AdressePostale = trim($_POST['AdressePostale'] ?? '');

        $IdBenevole = (int)($_POST['IdBenevole'] ?? 0);
        $IdEvenement = (int)($_POST['IdEvenement'] ?? 0);
        $IdMission = (int)($_POST['IdMission'] ?? 0);

        if ($NomRegion === '') {
            $_SESSION['flash_error']="Nom de région obligatoire.";
            header("Location: regions.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
        }
        if ($IdBenevole<=0 || $IdEvenement<=0 || $IdMission<=0) {
            $_SESSION['flash_error']="IdBenevole, IdEvenement et IdMission sont obligatoires.";
            header("Location: regions.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
        }

        if ($formId === 0) {
            try {
                $st = $pdo->prepare("
                    INSERT INTO Region
                    (NomRegion, TypeRegion, AdressePostale, IdBenevole, IdEvenement, IdMission)
                    VALUES (:Nom,:Type,:Adr,:IdB,:IdE,:IdM)
                ");
                $st->execute([
                    ':Nom'=>$NomRegion,
                    ':Type'=>($TypeRegion!==''?$TypeRegion:null),
                    ':Adr'=>($AdressePostale!==''?$AdressePostale:null),
                    ':IdB'=>$IdBenevole,
                    ':IdE'=>$IdEvenement,
                    ':IdM'=>$IdMission
                ]);
                $_SESSION['flash_success']="Région ajoutée.";
                header("Location: regions.php"); exit;
            } catch (PDOException $e) {
                $_SESSION['flash_error']="Erreur SQL (FK invalides ?).";
                header("Location: regions.php"); exit;
            }
        }

        try {
            $st = $pdo->prepare("
                UPDATE Region SET
                    NomRegion=:Nom,
                    TypeRegion=:Type,
                    AdressePostale=:Adr,
                    IdBenevole=:IdB,
                    IdEvenement=:IdE,
                    IdMission=:IdM
                WHERE IdRegion=:Id
            ");
            $st->execute([
                ':Nom'=>$NomRegion,
                ':Type'=>($TypeRegion!==''?$TypeRegion:null),
                ':Adr'=>($AdressePostale!==''?$AdressePostale:null),
                ':IdB'=>$IdBenevole,
                ':IdE'=>$IdEvenement,
                ':IdM'=>$IdMission,
                ':Id'=>$formId
            ]);
            $_SESSION['flash_success']="Région mise à jour.";
            header("Location: regions.php"); exit;
        } catch (PDOException $e) {
            $_SESSION['flash_error']="Erreur SQL lors de la mise à jour.";
            header("Location: regions.php?mode=edit&id=".$formId); exit;
        }
    }
}


$edit = null;
if ($mode === 'edit' && $id > 0) {
    $st = $pdo->prepare("SELECT * FROM Region WHERE IdRegion=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$edit) { $_SESSION['flash_error']="Région introuvable."; header("Location: regions.php"); exit; }
}


$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page-1)*$perPage;

$where=""; $params=[];
if ($q!=='') {
    $where = "WHERE (NomRegion LIKE :q OR TypeRegion LIKE :q OR AdressePostale LIKE :q)";
    $params[':q']="%$q%";
}

$st=$pdo->prepare("SELECT COUNT(*) c FROM Region $where");
$st->execute($params);
$total=(int)($st->fetchColumn()?:0);
$totalPages=max(1,(int)ceil($total/$perPage));

$st=$pdo->prepare("
    SELECT IdRegion, NomRegion, TypeRegion, AdressePostale, IdBenevole, IdEvenement, IdMission
    FROM Region
    $where
    ORDER BY IdRegion DESC
    LIMIT $perPage OFFSET $offset
");
$st->execute($params);
$rows=$st->fetchAll(PDO::FETCH_ASSOC)?:[];

$flashSuccess=flash_get('flash_success');
$flashError=flash_get('flash_error');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Admin • Régions</title>
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
            <a class="dash-link is-active" href="regions.php">Régions</a>
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
                <h1 class="dash-h1">Gestion des régions</h1>
                <p class="dash-sub">Ta table impose 3 FK obligatoires (Bénévole + Événement + Mission).</p>
            </div>
            <div class="dash-top-actions">
                <a class="dash-btn dash-btn-primary" href="regions.php">+ Nouvelle région</a>
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
                <div class="dash-card-title"><?= $edit ? "Modifier la région #".(int)$edit['IdRegion'] : "Ajouter une région" ?></div>
                <div class="dash-card-meta"><?= $edit ? "Mode édition" : "Mode création" ?></div>
            </div>

            <div class="dash-card-body">
                <form method="post" action="regions.php<?= $edit ? '?mode=edit&id='.(int)$edit['IdRegion'] : '' ?>">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['IdRegion'] : 0 ?>">

                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px;">
                        <div style="grid-column: span 2;">
                            <label>Nom région *</label>
                            <input class="dash-input" name="NomRegion" value="<?= h($edit['NomRegion'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label>Type région</label>
                            <input class="dash-input" name="TypeRegion" value="<?= h($edit['TypeRegion'] ?? '') ?>">
                        </div>

                        <div style="grid-column: span 3;">
                            <label>Adresse postale</label>
                            <input class="dash-input" name="AdressePostale" value="<?= h($edit['AdressePostale'] ?? '') ?>">
                        </div>

                        <div>
                            <label>Bénévole *</label>
                            <?php $selB = (string)($edit['IdBenevole'] ?? ''); ?>
                            <select class="dash-input" name="IdBenevole" required>
                                <option value="">— Choisir —</option>
                                <?php foreach($benevoles as $b): ?>
                                    <option value="<?= (int)$b['IdBenevole'] ?>" <?= ((string)$b['IdBenevole']===$selB)?'selected':'' ?>>
                                        <?= h("#".$b['IdBenevole']." • ".$b['PrenomBenevole']." ".$b['NomBenevole']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Événement *</label>
                            <?php $selE = (string)($edit['IdEvenement'] ?? ''); ?>
                            <select class="dash-input" name="IdEvenement" required>
                                <option value="">— Choisir —</option>
                                <?php foreach($events as $ev): ?>
                                    <option value="<?= (int)$ev['IdEvenement'] ?>" <?= ((string)$ev['IdEvenement']===$selE)?'selected':'' ?>>
                                        <?= h("#".$ev['IdEvenement']." • ".$ev['NomEvenement']." • ".($ev['DateEvenement'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Mission *</label>
                            <?php $selM = (string)($edit['IdMission'] ?? ''); ?>
                            <select class="dash-input" name="IdMission" required>
                                <option value="">— Choisir —</option>
                                <?php foreach($missions as $m): ?>
                                    <option value="<?= (int)$m['IdMission'] ?>" <?= ((string)$m['IdMission']===$selM)?'selected':'' ?>>
                                        <?= h("#".$m['IdMission']." • ".$m['TitreMission']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
                        <button class="dash-btn dash-btn-primary" type="submit"><?= $edit ? "Enregistrer" : "Créer" ?></button>
                        <?php if ($edit): ?><a class="dash-btn" href="regions.php">Annuler</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Liste des régions</div>
                <div class="dash-card-meta"><?= (int)$total ?> résultat(s)</div>
            </div>

            <div class="dash-card-body" style="padding-top:0;">
                <form method="get" action="regions.php" style="display:flex; gap:10px; flex-wrap:wrap; margin:12px 0;">
                    <input class="dash-input" style="flex:1; min-width:240px;" name="q" value="<?= h($q) ?>" placeholder="Rechercher (nom, type, adresse)">
                    <button class="dash-btn" type="submit">Rechercher</button>
                    <?php if ($q !== ''): ?><a class="dash-btn" href="regions.php">Reset</a><?php endif; ?>
                </form>

                <div class="dash-tablewrap">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Adresse</th>
                            <th>IdBenevole</th>
                            <th>IdEvenement</th>
                            <th>IdMission</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($rows)): ?>
                            <tr><td colspan="8" class="dash-td-empty">Aucune région trouvée.</td></tr>
                        <?php else: foreach($rows as $r): ?>
                            <tr>
                                <td><?= (int)$r['IdRegion'] ?></td>
                                <td><?= h($r['NomRegion'] ?? '') ?></td>
                                <td><?= h($r['TypeRegion'] ?? '—') ?></td>
                                <td><?= h($r['AdressePostale'] ?? '—') ?></td>
                                <td><?= (int)$r['IdBenevole'] ?></td>
                                <td><?= (int)$r['IdEvenement'] ?></td>
                                <td><?= (int)$r['IdMission'] ?></td>
                                <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <a class="dash-btn" href="regions.php?mode=edit&id=<?= (int)$r['IdRegion'] ?>">Modifier</a>
                                    <form method="post" action="regions.php" onsubmit="return confirm('Supprimer cette région ?');">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$r['IdRegion'] ?>">
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
                        <a class="dash-btn" href="regions.php?p=1<?= $q!=='' ? '&q='.urlencode($q) : '' ?>">« Début</a>
                        <a class="dash-btn" href="regions.php?p=<?= max(1,$page-1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">‹ Préc</a>
                        <span style="color:#6b7c98;">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>
                        <a class="dash-btn" href="regions.php?p=<?= min($totalPages,$page+1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Suiv ›</a>
                        <a class="dash-btn" href="regions.php?p=<?= (int)$totalPages ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Fin »</a>
                    </div>
                <?php endif; ?>

            </div>
        </section>
    </main>
</div>


</body>
</html>
