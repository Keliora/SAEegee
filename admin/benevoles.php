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


$mode = $_GET['mode'] ?? ''; // 'edit' ou '' (list)
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (($_POST['csrf'] ?? '') !== $csrf) {
        $_SESSION['flash_error'] = "Sécurité: token invalide.";
        header("Location: benevoles.php");
        exit;
    }

    $action = $_POST['action'] ?? '';


    if ($action === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);

        if ($delId <= 0) {
            $_SESSION['flash_error'] = "ID invalide.";
            header("Location: benevoles.php");
            exit;
        }

        if (!empty($_SESSION['auth']['id_benevole']) && $delId === (int)$_SESSION['auth']['id_benevole']) {
            $_SESSION['flash_error'] = "Vous ne pouvez pas supprimer votre propre compte admin.";
            header("Location: benevoles.php");
            exit;
        }

        $st = $pdo->prepare("DELETE FROM Benevole WHERE IdBenevole = :id");
        $st->execute([':id' => $delId]);

        $_SESSION['flash_success'] = "Bénévole supprimé.";
        header("Location: benevoles.php");
        exit;
    }


    if ($action === 'save') {
        $formId = (int)($_POST['id'] ?? 0);

        $PrenomBenevole = trim($_POST['PrenomBenevole'] ?? '');
        $NomBenevole    = trim($_POST['NomBenevole'] ?? '');
        $Email          = trim($_POST['Email'] ?? '');
        $Role           = strtoupper(trim($_POST['Role'] ?? 'USER'));

        $NumeroBenevole        = trim($_POST['NumeroBenevole'] ?? '');
        $VilleBenevole         = trim($_POST['VilleBenevole'] ?? '');
        $CompetenceBenevole    = trim($_POST['CompetenceBenevole'] ?? '');
        $ProfessionBenevole    = trim($_POST['ProfessionBenevole'] ?? '');
        $RegimeAlimentaire     = trim($_POST['RegimeAlimentaire'] ?? '');
        $DateNaissanceBenevole = trim($_POST['DateNaissanceBenevole'] ?? '');
        $OrigineGeographique   = trim($_POST['OrigineGeographique'] ?? '');
        $DateInscriptionBenevole = trim($_POST['DateInscriptionBenevole'] ?? '');
        $DomaineIntervention   = trim($_POST['DomaineIntervention'] ?? '');

        $NewPassword = $_POST['NewPassword'] ?? ''; // si vide: pas de changement (update)

        // Validation minimale
        if ($PrenomBenevole === '' || $NomBenevole === '' || $Email === '') {
            $_SESSION['flash_error'] = "Prénom, Nom et Email sont obligatoires.";
            header("Location: benevoles.php" . ($formId ? "?mode=edit&id=".$formId : ""));
            exit;
        }
        if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "Email invalide.";
            header("Location: benevoles.php" . ($formId ? "?mode=edit&id=".$formId : ""));
            exit;
        }
        if (!in_array($Role, ['USER','ADMIN'], true)) $Role = 'USER';

        // dates: si vide => NULL
        $DateNaissanceBenevole = ($DateNaissanceBenevole !== '') ? $DateNaissanceBenevole : null;
        $DateInscriptionBenevole = ($DateInscriptionBenevole !== '') ? $DateInscriptionBenevole : null;

        // Create
        if ($formId === 0) {
            if (trim($NewPassword) === '') {
                $_SESSION['flash_error'] = "Mot de passe obligatoire pour créer un bénévole.";
                header("Location: benevoles.php");
                exit;
            }

            $hash = password_hash($NewPassword, PASSWORD_DEFAULT);

            $sql = "INSERT INTO Benevole
                (NomBenevole, PrenomBenevole, NumeroBenevole, VilleBenevole, CompetenceBenevole, ProfessionBenevole,
                 RegimeAlimentaire, DateNaissanceBenevole, OrigineGeographique, DateInscriptionBenevole, DomaineIntervention,
                 Email, Password, Role)
                VALUES
                (:Nom, :Prenom, :Numero, :Ville, :Competence, :Profession,
                 :Regime, :DateNaissance, :Origine, :DateInscription, :Domaine,
                 :Email, :Password, :Role)";

            $st = $pdo->prepare($sql);

            try {
                $st->execute([
                        ':Nom' => $NomBenevole,
                        ':Prenom' => $PrenomBenevole,
                        ':Numero' => ($NumeroBenevole !== '' ? $NumeroBenevole : null),
                        ':Ville' => ($VilleBenevole !== '' ? $VilleBenevole : null),
                        ':Competence' => ($CompetenceBenevole !== '' ? $CompetenceBenevole : null),
                        ':Profession' => ($ProfessionBenevole !== '' ? $ProfessionBenevole : null),
                        ':Regime' => ($RegimeAlimentaire !== '' ? $RegimeAlimentaire : null),
                        ':DateNaissance' => $DateNaissanceBenevole,
                        ':Origine' => ($OrigineGeographique !== '' ? $OrigineGeographique : null),
                        ':DateInscription' => $DateInscriptionBenevole,
                        ':Domaine' => ($DomaineIntervention !== '' ? $DomaineIntervention : null),
                        ':Email' => $Email,
                        ':Password' => $hash,
                        ':Role' => $Role
                ]);
                $_SESSION['flash_success'] = "Bénévole ajouté.";
                header("Location: benevoles.php");
                exit;
            } catch (PDOException $e) {
                // duplicate email
                $_SESSION['flash_error'] = "Erreur: email déjà utilisé ou problème SQL.";
                header("Location: benevoles.php");
                exit;
            }
        }

        $fields = "NomBenevole=:Nom, PrenomBenevole=:Prenom, NumeroBenevole=:Numero, VilleBenevole=:Ville,
                   CompetenceBenevole=:Competence, ProfessionBenevole=:Profession, RegimeAlimentaire=:Regime,
                   DateNaissanceBenevole=:DateNaissance, OrigineGeographique=:Origine, DateInscriptionBenevole=:DateInscription,
                   DomaineIntervention=:Domaine, Email=:Email, Role=:Role";

        $params = [
                ':Nom' => $NomBenevole,
                ':Prenom' => $PrenomBenevole,
                ':Numero' => ($NumeroBenevole !== '' ? $NumeroBenevole : null),
                ':Ville' => ($VilleBenevole !== '' ? $VilleBenevole : null),
                ':Competence' => ($CompetenceBenevole !== '' ? $CompetenceBenevole : null),
                ':Profession' => ($ProfessionBenevole !== '' ? $ProfessionBenevole : null),
                ':Regime' => ($RegimeAlimentaire !== '' ? $RegimeAlimentaire : null),
                ':DateNaissance' => $DateNaissanceBenevole,
                ':Origine' => ($OrigineGeographique !== '' ? $OrigineGeographique : null),
                ':DateInscription' => $DateInscriptionBenevole,
                ':Domaine' => ($DomaineIntervention !== '' ? $DomaineIntervention : null),
                ':Email' => $Email,
                ':Role' => $Role,
                ':Id' => $formId
        ];

        if (trim($NewPassword) !== '') {
            $fields .= ", Password=:Password";
            $params[':Password'] = password_hash($NewPassword, PASSWORD_DEFAULT);
        }

        $sql = "UPDATE Benevole SET $fields WHERE IdBenevole=:Id";
        $st = $pdo->prepare($sql);

        try {
            $st->execute($params);
            $_SESSION['flash_success'] = "Bénévole mis à jour.";
            header("Location: benevoles.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Erreur: email déjà utilisé ou problème SQL.";
            header("Location: benevoles.php?mode=edit&id=".$formId);
            exit;
        }
    }
}


$edit = null;
if ($mode === 'edit' && $id > 0) {
    $st = $pdo->prepare("SELECT * FROM Benevole WHERE IdBenevole = :id LIMIT 1");
    $st->execute([':id' => $id]);
    $edit = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$edit) {
        $_SESSION['flash_error'] = "Bénévole introuvable.";
        header("Location: benevoles.php");
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
    $where = "WHERE (NomBenevole LIKE :q OR PrenomBenevole LIKE :q OR Email LIKE :q OR VilleBenevole LIKE :q)";
    $params[':q'] = "%$q%";
}

// total
$st = $pdo->prepare("SELECT COUNT(*) c FROM Benevole $where");
$st->execute($params);
$total = (int)($st->fetchColumn() ?: 0);
$totalPages = max(1, (int)ceil($total / $perPage));

// rows
$sql = "SELECT IdBenevole, PrenomBenevole, NomBenevole, Email, VilleBenevole, DateInscriptionBenevole, Role
        FROM Benevole
        $where
        ORDER BY IdBenevole DESC
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
    <title>Admin • Bénévoles</title>
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
            <a class="dash-link is-active" href="benevoles.php">Bénévoles</a>
            <a class="dash-link" href="missions.php">Missions</a>
            <a class="dash-link" href="evenements.php">Événements</a>
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
                <h1 class="dash-h1">Gestion des bénévoles</h1>
                <p class="dash-sub">Ajouter, modifier, rechercher et gérer les comptes (USER / ADMIN).</p>
            </div>
            <div class="dash-top-actions">
                <a class="dash-btn dash-btn-primary" href="benevoles.php">+ Nouveau bénévole</a>
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


        <section class="dash-card" style="margin-bottom:12px;">
            <div class="dash-card-head">
                <div class="dash-card-title">
                    <?= $edit ? "Modifier le bénévole #".(int)$edit['IdBenevole'] : "Ajouter un bénévole" ?>
                </div>
                <div class="dash-card-meta"><?= $edit ? "Mode édition" : "Mode création" ?></div>
            </div>

            <div class="dash-card-body">
                <form method="post" action="benevoles.php<?= $edit ? '?mode=edit&id='.(int)$edit['IdBenevole'] : '' ?>">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit ? (int)$edit['IdBenevole'] : 0 ?>">

                    <div style="display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:12px;">
                        <div>
                            <label>Prénom *</label>
                            <input class="dash-input" name="PrenomBenevole" value="<?= h($edit['PrenomBenevole'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label>Nom *</label>
                            <input class="dash-input" name="NomBenevole" value="<?= h($edit['NomBenevole'] ?? '') ?>" required>
                        </div>
                        <div>
                            <label>Email *</label>
                            <input class="dash-input" name="Email" value="<?= h($edit['Email'] ?? '') ?>" required>
                        </div>

                        <div>
                            <label>Rôle *</label>
                            <select class="dash-input" name="Role">
                                <?php $r = strtoupper($edit['Role'] ?? 'USER'); ?>
                                <option value="USER" <?= $r==='USER'?'selected':'' ?>>USER</option>
                                <option value="ADMIN" <?= $r==='ADMIN'?'selected':'' ?>>ADMIN</option>
                            </select>
                        </div>
                        <div>
                            <label>Téléphone</label>
                            <input class="dash-input" name="NumeroBenevole" value="<?= h($edit['NumeroBenevole'] ?? '') ?>">
                        </div>
                        <div>
                            <label>Ville</label>
                            <input class="dash-input" name="VilleBenevole" value="<?= h($edit['VilleBenevole'] ?? '') ?>">
                        </div>

                        <div>
                            <label>Compétence</label>
                            <input class="dash-input" name="CompetenceBenevole" value="<?= h($edit['CompetenceBenevole'] ?? '') ?>">
                        </div>
                        <div>
                            <label>Profession</label>
                            <input class="dash-input" name="ProfessionBenevole" value="<?= h($edit['ProfessionBenevole'] ?? '') ?>">
                        </div>
                        <div>
                            <label>Régime alimentaire</label>
                            <input class="dash-input" name="RegimeAlimentaire" value="<?= h($edit['RegimeAlimentaire'] ?? '') ?>">
                        </div>

                        <div>
                            <label>Date naissance</label>
                            <input class="dash-input" type="date" name="DateNaissanceBenevole" value="<?= h($edit['DateNaissanceBenevole'] ?? '') ?>">
                        </div>
                        <div>
                            <label>Origine géographique</label>
                            <input class="dash-input" name="OrigineGeographique" value="<?= h($edit['OrigineGeographique'] ?? '') ?>">
                        </div>
                        <div>
                            <label>Date inscription</label>
                            <input class="dash-input" type="date" name="DateInscriptionBenevole" value="<?= h($edit['DateInscriptionBenevole'] ?? '') ?>">
                        </div>

                        <div style="grid-column: span 2;">
                            <label>Domaine intervention</label>
                            <input class="dash-input" name="DomaineIntervention" value="<?= h($edit['DomaineIntervention'] ?? '') ?>">
                        </div>

                        <div>
                            <label><?= $edit ? "Nouveau mot de passe (optionnel)" : "Mot de passe *" ?></label>
                            <input class="dash-input" type="password" name="NewPassword" <?= $edit ? '' : 'required' ?> placeholder="<?= $edit ? 'Laisser vide pour ne pas changer' : '' ?>">
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:12px; flex-wrap:wrap;">
                        <button class="dash-btn dash-btn-primary" type="submit">
                            <?= $edit ? "Enregistrer" : "Créer" ?>
                        </button>

                        <?php if ($edit): ?>
                            <a class="dash-btn" href="benevoles.php">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </section>


        <section class="dash-card dash-tablecard">
            <div class="dash-card-head">
                <div class="dash-card-title">Liste des bénévoles</div>
                <div class="dash-card-meta"><?= (int)$total ?> résultat(s)</div>
            </div>

            <div class="dash-card-body" style="padding-top:0;">
                <form method="get" action="benevoles.php" style="display:flex; gap:10px; flex-wrap:wrap; margin:12px 0;">
                    <input class="dash-input" style="flex:1; min-width:240px;" name="q" value="<?= h($q) ?>" placeholder="Rechercher (nom, prénom, email, ville)">
                    <button class="dash-btn" type="submit">Rechercher</button>
                    <?php if ($q !== ''): ?>
                        <a class="dash-btn" href="benevoles.php">Reset</a>
                    <?php endif; ?>
                </form>

                <div class="dash-tablewrap">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Ville</th>
                            <th>Inscription</th>
                            <th>Rôle</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="7" class="dash-td-empty">Aucun bénévole trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $b): ?>
                                <tr>
                                    <td><?= (int)$b['IdBenevole'] ?></td>
                                    <td><?= h(($b['PrenomBenevole'] ?? '') . ' ' . ($b['NomBenevole'] ?? '')) ?></td>
                                    <td><?= h($b['Email'] ?? '') ?></td>
                                    <td><?= h($b['VilleBenevole'] ?? '—') ?></td>
                                    <td><?= h($b['DateInscriptionBenevole'] ?? '—') ?></td>
                                    <td><?= h($b['Role'] ?? 'USER') ?></td>
                                    <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <a class="dash-btn" href="benevoles.php?mode=edit&id=<?= (int)$b['IdBenevole'] ?>">Modifier</a>

                                        <form method="post" action="benevoles.php" onsubmit="return confirm('Supprimer ce bénévole ?');">
                                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$b['IdBenevole'] ?>">
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
                        <?php
                        $base = "benevoles.php";
                        $qs = ($q !== '') ? "&q=" . urlencode($q) : "";
                        ?>
                        <a class="dash-btn" href="<?= $base ?>?p=1<?= $q!=='' ? '&q='.urlencode($q) : '' ?>">« Début</a>
                        <a class="dash-btn" href="<?= $base ?>?p=<?= max(1,$page-1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">‹ Préc</a>

                        <span style="color:#6b7c98;">Page <?= (int)$page ?> / <?= (int)$totalPages ?></span>

                        <a class="dash-btn" href="<?= $base ?>?p=<?= min($totalPages,$page+1) ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Suiv ›</a>
                        <a class="dash-btn" href="<?= $base ?>?p=<?= (int)$totalPages ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>">Fin »</a>
                    </div>
                <?php endif; ?>

            </div>
        </section>

    </main>
</div>




</body>
</html>
