<?php
require_once __DIR__ . "/../init.php";

/**
 * ADMIN Presse (CRUD) table:
 * Presse(IdPresse, TitrePresse, ResumePresse, AuteurPresse, DateHeurePublication, Statut, LienSource, Fichier, IdEvenement)
 *
 * + upload fichier optionnel
 * + tri: ORDER BY COALESCE(DateHeurePublication,'1970-01-01 00:00:00') DESC, IdPresse DESC
 */

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

function to_dt_local($dt){
    if(!$dt) return '';
    return str_replace(' ', 'T', substr($dt,0,16)); // "YYYY-MM-DD HH:MM:SS" => "YYYY-MM-DDTHH:MM"
}
function normalize_dt_from_input($dtLocal){
    $dtLocal = trim((string)$dtLocal);
    if ($dtLocal === '') return null;
    $dtLocal = str_replace('T', ' ', $dtLocal);
    if (strlen($dtLocal) === 16) $dtLocal .= ":00";
    return $dtLocal;
}

/** Upload config */
$uploadDirFs  = __DIR__ . "/../uploads/presse";
$uploadDirWeb = "../uploads/presse";
if (!is_dir($uploadDirFs)) { @mkdir($uploadDirFs, 0775, true); }

/** CSRF */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];

$adminName  = trim(($_SESSION['auth']['prenom'] ?? 'System').' '.($_SESSION['auth']['nom'] ?? 'Admin'));
$adminEmail = $_SESSION['auth']['email'] ?? 'admin@site.com';

$mode = $_GET['mode'] ?? '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/** Chargement article en édition */
$edit = null;
if ($mode === 'edit' && $id > 0) {
    $st = $pdo->prepare("SELECT * FROM Presse WHERE IdPresse=:id LIMIT 1");
    $st->execute([':id'=>$id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$edit) { $_SESSION['flash_error']="Article introuvable."; header("Location: presse.php"); exit; }
}

/** POST actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf'] ?? '') !== $csrf) {
        $_SESSION['flash_error'] = "Sécurité: token invalide.";
        header("Location: presse.php"); exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId <= 0) { $_SESSION['flash_error']="ID invalide."; header("Location: presse.php"); exit; }

        try {
            $st = $pdo->prepare("DELETE FROM Presse WHERE IdPresse=:id");
            $st->execute([':id'=>$delId]);
            $_SESSION['flash_success']="Article presse supprimé.";
        } catch (PDOException $e) {
            $_SESSION['flash_error']="Suppression impossible (liens existants).";
        }
        header("Location: presse.php"); exit;
    }

    if ($action === 'save') {
        $formId = (int)($_POST['id'] ?? 0);

        $TitrePresse         = trim($_POST['TitrePresse'] ?? '');
        $ResumePresse        = trim($_POST['ResumePresse'] ?? '');
        $AuteurPresse        = trim($_POST['AuteurPresse'] ?? '');
        $DateHeurePublication= normalize_dt_from_input($_POST['DateHeurePublication'] ?? '');
        $Statut              = trim($_POST['Statut'] ?? '');
        $LienSource          = trim($_POST['LienSource'] ?? '');
        $IdEvenement         = trim($_POST['IdEvenement'] ?? '');

        if ($TitrePresse === '') {
            $_SESSION['flash_error']="Titre obligatoire.";
            header("Location: presse.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
        }

        $IdEvenement = ($IdEvenement !== '' ? (int)$IdEvenement : null);

        // Fichier: champ texte OU upload
        $FichierTxt   = trim($_POST['Fichier'] ?? '');
        $FichierFinal = ($formId && $edit && !empty($edit['Fichier'])) ? $edit['Fichier'] : null;

        if ($FichierTxt !== '') {
            $FichierFinal = $FichierTxt;
        }

        // Upload
        if (!empty($_FILES['FichierUpload']) && $_FILES['FichierUpload']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['FichierUpload']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['flash_error'] = "Erreur upload fichier.";
                header("Location: presse.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
            }

            $tmp  = $_FILES['FichierUpload']['tmp_name'];
            $orig = (string)$_FILES['FichierUpload']['name'];

            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $allowed = ['pdf','png','jpg','jpeg','webp'];
            if (!in_array($ext, $allowed, true)) {
                $_SESSION['flash_error'] = "Format non autorisé (pdf, png, jpg, jpeg, webp).";
                header("Location: presse.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
            }

            $base    = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
            $newName = $base . "_" . date('Ymd_His') . "_" . bin2hex(random_bytes(3)) . "." . $ext;

            $dest = $uploadDirFs . "/" . $newName;
            if (!move_uploaded_file($tmp, $dest)) {
                $_SESSION['flash_error'] = "Impossible d’enregistrer le fichier uploadé.";
                header("Location: presse.php".($formId ? "?mode=edit&id=".$formId : "")); exit;
            }

            $FichierFinal = $newName;
        }

        if ($formId === 0) {
            try {
                $st = $pdo->prepare("
                    INSERT INTO Presse
                    (TitrePresse, ResumePresse, AuteurPresse, DateHeurePublication, Statut, LienSource, Fichier, IdEvenement)
                    VALUES (:Titre,:Resume,:Auteur,:Dt,:Statut,:Lien,:Fichier,:IdEvt)
                ");
                $st->execute([
                        ':Titre'   => $TitrePresse,
                        ':Resume'  => ($ResumePresse!==''?$ResumePresse:null),
                        ':Auteur'  => ($AuteurPresse!==''?$AuteurPresse:null),
                        ':Dt'      => $DateHeurePublication,
                        ':Statut'  => ($Statut!==''?$Statut:null),
                        ':Lien'    => ($LienSource!==''?$LienSource:null),
                        ':Fichier' => ($FichierFinal!==''?$FichierFinal:null),
                        ':IdEvt'   => $IdEvenement
                ]);
                $_SESSION['flash_success']="Article presse ajouté.";
                header("Location: presse.php"); exit;
            } catch (PDOException $e) {
                $_SESSION['flash_error']="Erreur SQL (IdEvenement invalide ?).";
                header("Location: presse.php"); exit;
            }
        } else {
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
                        ':Titre'   => $TitrePresse,
                        ':Resume'  => ($ResumePresse!==''?$ResumePresse:null),
                        ':Auteur'  => ($AuteurPresse!==''?$AuteurPresse:null),
                        ':Dt'      => $DateHeurePublication,
                        ':Statut'  => ($Statut!==''?$Statut:null),
                        ':Lien'    => ($LienSource!==''?$LienSource:null),
                        ':Fichier' => ($FichierFinal!==''?$FichierFinal:null),
                        ':IdEvt'   => $IdEvenement,
                        ':Id'      => $formId
                ]);
                $_SESSION['flash_success']="Article presse mis à jour.";
                header("Location: presse.php"); exit;
            } catch (PDOException $e) {
                $_SESSION['flash_error']="Erreur SQL lors de la mise à jour.";
                header("Location: presse.php?mode=edit&id=".$formId); exit;
            }
        }
    }
}

/** Recherche + pagination */
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page-1)*$perPage;

$where=""; $params=[];
if ($q!=='') {
    $where = "WHERE (TitrePresse LIKE :q OR AuteurPresse LIKE :q OR Statut LIKE :q OR ResumePresse LIKE :q)";
    $params[':q']="%$q%";
}

$st=$pdo->prepare("SELECT COUNT(*) FROM Presse $where");
$st->execute($params);
$total=(int)($st->fetchColumn()?:0);
$totalPages=max(1,(int)ceil($total/$perPage));

$st=$pdo->prepare("
    SELECT
        IdPresse,
        TitrePresse,
        ResumePresse,
        AuteurPresse,
        DateHeurePublication,
        LienSource,
        Fichier,
        Statut,
        IdEvenement
    FROM Presse
    $where
    ORDER BY COALESCE(DateHeurePublication,'1970-01-01 00:00:00') DESC, IdPresse DESC
    LIMIT $perPage OFFSET $offset
");
$st->execute($params);
$rows=$st->fetchAll(PDO::FETCH_ASSOC)?:[];

/** Events pour select */
$events = $pdo->query("
    SELECT IdEvenement, NomEvenement, DateEvenement
    FROM Evenement
    ORDER BY COALESCE(DateEvenement,'1970-01-01') DESC, IdEvenement DESC
")->fetchAll(PDO::FETCH_ASSOC)?:[];

$flashSuccess=flash_get('flash_success');
$flashError=flash_get('flash_error');
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
                <h1 class="dash-h1">Gestion Presse</h1>

            </div>
            <div class="dash-top-actions">
                <a class="dash-btn dash-btn-primary" href="presse.php">+ Nouvel article</a>
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

        <section class="dash-card dash-mb-12">
            <div class="dash-card-head">
                <div class="dash-card-title"><?= $edit ? "Modifier l'article #".(int)$edit['IdPresse'] : "Ajouter un article" ?></div>
                <div class="dash-card-meta"><?= $edit ? "Mode édition" : "Mode création" ?></div>
            </div>
            <div class="dash-card-body">
                <form method="post" enctype="multipart/form-data" action="presse.php<?= $edit ? '?mode=edit&id='.(int)$edit['IdPresse'] : '' ?>">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['IdPresse'] : 0 ?>">

                    <div class="dash-form-grid">
                        <div class="dash-col-span-2">
                            <label>Titre *</label>
                            <input class="dash-input" name="TitrePresse" value="<?= h($edit['TitrePresse'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label>Auteur</label>
                            <input class="dash-input" name="AuteurPresse" value="<?= h($edit['AuteurPresse'] ?? '') ?>">
                        </div>

                        <div class="dash-col-span-3">
                            <label>Résumé</label>
                            <textarea class="dash-input dash-textarea" name="ResumePresse" rows="3"><?= h($edit['ResumePresse'] ?? '') ?></textarea>
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

                        <div class="dash-col-span-2">
                            <label>Lien source</label>
                            <input class="dash-input" name="LienSource" value="<?= h($edit['LienSource'] ?? '') ?>" placeholder="https://...">
                        </div>

                        <div>
                            <label>Fichier (nom) (optionnel)</label>
                            <input class="dash-input" name="Fichier" value="<?= h($edit['Fichier'] ?? '') ?>" placeholder="ex: presse1.pdf">
                            <?php if (!empty($edit['Fichier'])): ?>
                                <small class="dash-helptext">Actuel: <?= h($edit['Fichier']) ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="dash-col-span-3">
                            <label>OU Upload fichier (pdf / image) (optionnel)</label>
                            <input class="dash-input" type="file" name="FichierUpload" accept=".pdf,.png,.jpg,.jpeg,.webp">
                            <small class="dash-helptext">
                                Stocké dans <?= h($uploadDirWeb) ?> / (le nom est enregistré dans la colonne <b>Fichier</b>)
                            </small>
                        </div>
                    </div>

                    <div class="dash-form-actions">
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
            <div class="dash-card-body dash-pt-0">
                <form method="get" action="presse.php" class="dash-searchbar">
                    <input class="dash-input dash-search-input" name="q" value="<?= h($q) ?>" placeholder="Rechercher (titre, auteur, statut, résumé)">
                    <button class="dash-btn" type="submit">Rechercher</button>
                    <?php if ($q !== ''): ?><a class="dash-btn" href="presse.php">Reset</a><?php endif; ?>
                </form>

                <div class="dash-tablewrap">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Résumé</th>
                            <th>Auteur</th>
                            <th>Date</th>
                            <th>Lien</th>
                            <th>Fichier</th>
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
                                <td><?= h($r['ResumePresse'] ?? '—') ?></td>
                                <td><?= h($r['AuteurPresse'] ?? '—') ?></td>
                                <td><?= h($r['DateHeurePublication'] ?? '—') ?></td>
                                <td>
                                    <?php if(!empty($r['LienSource'])): ?>
                                        <a href="<?= h($r['LienSource']) ?>" target="_blank" rel="noreferrer">ouvrir</a>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td>
                                    <?php if(!empty($r['Fichier'])): ?>
                                        <a href="<?= h($uploadDirWeb . "/" . $r['Fichier']) ?>" target="_blank" rel="noreferrer">
                                            <?= h($r['Fichier']) ?>
                                        </a>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td class="dash-row-actions">
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
                    <div class="dash-pagination">
                        <a class="dash-btn" href="presse.php?p=1<?= $q!=='' ? '&q='.urlencode($q) : '' ?>">« Début</a>
                        <a class="dash-btn" href="presse.php?p=<?= max(1,$page-1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">‹ Préc</a>
                        <span class="dash-pagination-info">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>
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
