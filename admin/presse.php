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
    if (($_POST['csrf'] ?? '') !== $csrf) { $_SESSION['flash_error']="Sécurité: token invalide."; header("Location: presse.php"); exit; }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId <= 0) { $_SESSION['flash_error']="ID invalide."; header("Location: presse.php"); exit; }

        try {
            $st = $pdo->prepare("DELETE FROM Presse WHERE IdPresse=:id");
            $st->execute([':id'=>$delId]);
            $_SESSION['flash_success']="Article presse supprimé.";
        } catch (PDOException $e) {
            // FK possible via Mission/Localiser
            $_SESSION['flash_error']="Suppression impossible (liens existants).";
        }
        header("Location: presse.php"); exit;
    }

    if ($action === 'save') {
        $formId = (int)($_POST['id'] ?? 0);

        $TitrePresse = trim($_POST['TitrePresse'] ?? '');
        $ResumePresse = trim($_POST['ResumePresse'] ?? '');
        $AuteurPresse = trim($_POST['AuteurPresse'] ?? '');
        $DateHeurePublication = trim($_POST['DateHeurePublication'] ?? '');
        $Statut = trim($_POST['Statut'] ?? '');
        $LienSource = trim($_POST['LienSource'] ?? '');
        $Fichier = trim($_POST['Fichier'] ?? '');
        $IdEvenement = trim($_POST['IdEvenement'] ?? '');

        if ($TitrePresse === '') {
            $_SESSION['flash_error']="Titre obligatoire.";
            header("Location: presse.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
        }

        $IdEvenement = ($IdEvenement !== '' ? (int)$IdEvenement : null);


        if ($DateHeurePublication !== '') {
            $DateHeurePublication = str_replace('T', ' ', $DateHeurePublication);
            if (strlen($DateHeurePublication) === 16) $DateHeurePublication .= ":00";
        } else {
            $DateHeurePublication = null;
        }

        if ($formId === 0) {
            try {
                $st = $pdo->prepare("
                    INSERT INTO Presse
                    (TitrePresse, ResumePresse, AuteurPresse, DateHeurePublication, Statut, LienSource, Fichier, IdEvenement)
                    VALUES (:Titre,:Resume,:Auteur,:Dt,:Statut,:Lien,:Fichier,:IdEvt)
                ");
                $st->execute([
                    ':Titre'=>$TitrePresse,
                    ':Resume'=>($ResumePresse!==''?$ResumePresse:null),
                    ':Auteur'=>($AuteurPresse!==''?$AuteurPresse:null),
                    ':Dt'=>$DateHeurePublication,
                    ':Statut'=>($Statut!==''?$Statut:null),
                    ':Lien'=>($LienSource!==''?$LienSource:null),
                    ':Fichier'=>($Fichier!==''?$Fichier:null),
                    ':IdEvt'=>$IdEvenement
                ]);
                $_SESSION['flash_success']="Article presse ajouté.";
                header("Location: presse.php"); exit;
            } catch (PDOException $e) {
                $_SESSION['flash_error']="Erreur SQL (IdEvenement invalide ?).";
                header("Location: presse.php"); exit;
            }
        }

        try {
            $st = $pdo->prepare("
                UPDATE Presse SET
                    TitrePresse=:Titre,
                    ResumePresse=:Resume,
                    AuteurPresse=:Auteur,
                    DateHeurePublication=:Dt,
                    Statut=:Statut,
                    LienSource=:Lien,
                    Fichier=:Fichier,
                    IdEvenement=:IdEvt
                WHERE IdPresse=:Id
            ");
            $st->execute([
                ':Titre'=>$TitrePresse,
                ':Resume'=>($ResumePresse!==''?$ResumePresse:null),
                ':Auteur'=>($AuteurPresse!==''?$AuteurPresse:null),
                ':Dt'=>$DateHeurePublication,
                ':Statut'=>($Statut!==''?$Statut:null),
                ':Lien'=>($LienSource!==''?$LienSource:null),
                ':Fichier'=>($Fichier!==''?$Fichier:null),
                ':IdEvt'=>$IdEvenement,
                ':Id'=>$formId
            ]);
            $_SESSION['flash_success']="Article presse mis à jour.";
            header("Location: presse.php"); exit;
        } catch (PDOException $e) {
            $_SESSION['flash_error']="Erreur SQL lors de la mise à jour.";
            header("Location: presse.php?mode=edit&id=".$formId); exit;
        }
    }
}


$edit = null;
if ($mode === 'edit' && $id > 0) {
    $st = $pdo->prepare("SELECT * FROM Presse WHERE IdPresse=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$edit) { $_SESSION['flash_error']="Article introuvable."; header("Location: presse.php"); exit; }
}


$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page-1)*$perPage;

$where=""; $params=[];
if ($q!=='') {
    $where = "WHERE (TitrePresse LIKE :q OR AuteurPresse LIKE :q OR Statut LIKE :q)";
    $params[':q']="%$q%";
}

$st=$pdo->prepare("SELECT COUNT(*) c FROM Presse $where");
$st->execute($params);
$total=(int)($st->fetchColumn()?:0);
$totalPages=max(1,(int)ceil($total/$perPage));

$st=$pdo->prepare("
    SELECT IdPresse, TitrePresse, AuteurPresse, DateHeurePublication, Statut, IdEvenement, LienSource
    FROM Presse
    $where
    ORDER BY COALESCE(DateHeurePublication,'1970-01-01 00:00:00') DESC, IdPresse DESC
    LIMIT $perPage OFFSET $offset
");
$st->execute($params);
$rows=$st->fetchAll(PDO::FETCH_ASSOC)?:[];


$events = $pdo->query("SELECT IdEvenement, NomEvenement, DateEvenement FROM Evenement ORDER BY COALESCE(DateEvenement,'1970-01-01') DESC, IdEvenement DESC")->fetchAll(PDO::FETCH_ASSOC)?:[];

$flashSuccess=flash_get('flash_success');
$flashError=flash_get('flash_error');


function to_dt_local($dt){
    if(!$dt) return '';
    // "YYYY-MM-DD HH:MM:SS" => "YYYY-MM-DDTHH:MM"
    return str_replace(' ', 'T', substr($dt,0,16));
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Admin • Presse</title>
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
            <a class="dash-link is-active" href="presse.php">Presse</a>
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
                <h1 class="dash-h1">Gestion Presse</h1>
                <p class="dash-sub">Articles + lien optionnel vers un événement.</p>
            </div>
            <div class="dash-top-actions">
                <a class="dash-btn dash-btn-primary" href="presse.php">+ Nouvel article</a>
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
                <div class="dash-card-title"><?= $edit ? "Modifier l'article #".(int)$edit['IdPresse'] : "Ajouter un article" ?></div>
                <div class="dash-card-meta"><?= $edit ? "Mode édition" : "Mode création" ?></div>
            </div>
            <div class="dash-card-body">
                <form method="post" action="presse.php<?= $edit ? '?mode=edit&id='.(int)$edit['IdPresse'] : '' ?>">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['IdPresse'] : 0 ?>">

                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px;">
                        <div style="grid-column: span 2;">
                            <label>Titre *</label>
                            <input class="dash-input" name="TitrePresse" value="<?= h($edit['TitrePresse'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label>Auteur</label>
                            <input class="dash-input" name="AuteurPresse" value="<?= h($edit['AuteurPresse'] ?? '') ?>">
                        </div>

                        <div style="grid-column: span 3;">
                            <label>Résumé</label>
                            <textarea class="dash-input" name="ResumePresse" rows="3"><?= h($edit['ResumePresse'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label>Date/heure publication</label>
                            <input class="dash-input" type="datetime-local" name="DateHeurePublication"
                                   value="<?= h(to_dt_local($edit['DateHeurePublication'] ?? '')) ?>">
                        </div>

                        <div>
                            <label>Statut</label>
                            <input class="dash-input" name="Statut" value="<?= h($edit['Statut'] ?? '') ?>" placeholder="Publié / Brouillon / ...">
                        </div>

                        <div>
                            <label>Événement associé</label>
                            <?php $sel = (string)($edit['IdEvenement'] ?? ''); ?>
                            <select class="dash-input" name="IdEvenement">
                                <option value="">Aucun</option>
                                <?php foreach($events as $ev): ?>
                                    <option value="<?= (int)$ev['IdEvenement'] ?>" <?= ((string)$ev['IdEvenement'] === $sel) ? 'selected' : '' ?>>
                                        <?= h("#".$ev['IdEvenement']." • ".$ev['NomEvenement']." • ".($ev['DateEvenement'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="grid-column: span 2;">
                            <label>Lien source</label>
                            <input class="dash-input" name="LienSource" value="<?= h($edit['LienSource'] ?? '') ?>" placeholder="https://...">
                        </div>

                        <div>
                            <label>Fichier (nom)</label>
                            <input class="dash-input" name="Fichier" value="<?= h($edit['Fichier'] ?? '') ?>" placeholder="ex: presse1.pdf">
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
                        <button class="dash-btn dash-btn-primary" type="submit"><?= $edit ? "Enregistrer" : "Créer" ?></button>
                        <?php if ($edit): ?><a class="dash-btn" href="presse.php">Annuler</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </section>

        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Liste des articles</div>
                <div class="dash-card-meta"><?= (int)$total ?> résultat(s)</div>
            </div>
            <div class="dash-card-body" style="padding-top:0;">
                <form method="get" action="presse.php" style="display:flex; gap:10px; flex-wrap:wrap; margin:12px 0;">
                    <input class="dash-input" style="flex:1; min-width:240px;" name="q" value="<?= h($q) ?>" placeholder="Rechercher (titre, auteur, statut)">
                    <button class="dash-btn" type="submit">Rechercher</button>
                    <?php if ($q !== ''): ?><a class="dash-btn" href="presse.php">Reset</a><?php endif; ?>
                </form>

                <div class="dash-tablewrap">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Auteur</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>IdEvenement</th>
                            <th>Lien</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($rows)): ?>
                            <tr><td colspan="8" class="dash-td-empty">Aucun article trouvé.</td></tr>
                        <?php else: foreach($rows as $r): ?>
                            <tr>
                                <td><?= (int)$r['IdPresse'] ?></td>
                                <td><?= h($r['TitrePresse'] ?? '') ?></td>
                                <td><?= h($r['AuteurPresse'] ?? '—') ?></td>
                                <td><?= h($r['DateHeurePublication'] ?? '—') ?></td>
                                <td><?= h($r['Statut'] ?? '—') ?></td>
                                <td><?= h($r['IdEvenement'] ?? '—') ?></td>
                                <td>
                                    <?php if(!empty($r['LienSource'])): ?>
                                        <a href="<?= h($r['LienSource']) ?>" target="_blank" rel="noreferrer">ouvrir</a>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <a class="dash-btn" href="presse.php?mode=edit&id=<?= (int)$r['IdPresse'] ?>">Modifier</a>
                                    <form method="post" action="presse.php" onsubmit="return confirm('Supprimer cet article ?');">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$r['IdPresse'] ?>">
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
                        <a class="dash-btn" href="presse.php?p=1<?= $q!=='' ? '&q='.urlencode($q) : '' ?>">« Début</a>
                        <a class="dash-btn" href="presse.php?p=<?= max(1,$page-1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">‹ Préc</a>
                        <span style="color:#6b7c98;">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>
                        <a class="dash-btn" href="presse.php?p=<?= min($totalPages,$page+1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Suiv ›</a>
                        <a class="dash-btn" href="presse.php?p=<?= (int)$totalPages ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Fin »</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>


</body>
</html>
