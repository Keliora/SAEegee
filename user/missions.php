<?php
require_once __DIR__ . "/../init.php";

if (empty($_SESSION['auth'])) {
    $_SESSION['login_error'] = "Vous devez être connecté.";
    header("Location: ../login.php");
    exit;
}

if (($_SESSION['auth']['role'] ?? '') !== 'USER') {
    header("Location: ../admin/dashboard.php");
    exit;
}

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function fmt_dt($dt){
    if (!$dt) return '—';
    $ts = strtotime($dt);
    return $ts ? date('d/m/Y H:i', $ts) : h($dt);
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

$idBenevole = (int)($_SESSION['auth']['id_benevole'] ?? 0);
$success = null;
$error = null;

if ($idBenevole <= 0) {
    $error = "Compte invalide : IdBenevole manquant.";
}

/**
 * Filtre d’affichage :
 * - upcoming (par défaut) : missions futures OU sans date
 * - all : toutes les missions (même passées)
 */
$view = $_GET['view'] ?? 'upcoming';
$view = in_array($view, ['upcoming','all'], true) ? $view : 'upcoming';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $action = $_POST['action'] ?? '';
    $idMission = (int)($_POST['id_mission'] ?? 0);

    // CSRF
    if (($_POST['csrf'] ?? '') !== $csrf) {
        $error = "Sécurité : token invalide.";
    } else {
        try {
            if ($idMission <= 0) throw new Exception("Mission invalide.");

            if ($action === 'join') {
                $role = trim($_POST['role'] ?? 'Bénévole');
                $comment = trim($_POST['commentaire'] ?? '');

                // Mission dispo si future ou null
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM Mission WHERE IdMission = :m AND (DateHeureDebut IS NULL OR DateHeureDebut >= NOW())");
                $stmt->execute([':m' => $idMission]);
                if ((int)$stmt->fetchColumn() === 0) {
                    throw new Exception("Cette mission n'est pas disponible à l'inscription (date passée).");
                }

                $stmt = $pdo->prepare("
                    INSERT INTO Participer (IdMission, IdBenevole, RoleBenevole, Duree, Commentaire)
                    VALUES (:m, :b, :r, NULL, :c)
                ");
                $stmt->execute([
                        ':m' => $idMission,
                        ':b' => $idBenevole,
                        ':r' => ($role !== '' ? $role : 'Bénévole'),
                        ':c' => ($comment !== '' ? $comment : null)
                ]);

                $success = "✅ Inscription réussie !";

            } elseif ($action === 'leave') {
                $stmt = $pdo->prepare("DELETE FROM Participer WHERE IdMission = :m AND IdBenevole = :b");
                $stmt->execute([':m' => $idMission, ':b' => $idBenevole]);

                $success = "✅ Désinscription effectuée.";
            }

        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), '1062')) {
                $error = "Tu es déjà inscrit à cette mission.";
            } else {
                $error = "Erreur SQL lors de l’action sur la mission.";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Mes missions
$myMissionsStmt = $pdo->prepare("
    SELECT m.IdMission, m.TitreMission, m.CategorieMission, m.LieuMission,
           m.DateHeureDebut, m.DateHeureFin,
           p.RoleBenevole, p.Commentaire
    FROM Participer p
    JOIN Mission m ON m.IdMission = p.IdMission
    WHERE p.IdBenevole = :b
    ORDER BY COALESCE(m.DateHeureDebut,'1970-01-01') DESC, m.IdMission DESC
");
$myMissionsStmt->execute([':b' => $idBenevole]);
$myMissions = $myMissionsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Conditions selon le filtre
$whereAvailable = "";
if ($view === 'upcoming') {
    $whereAvailable = "WHERE (m.DateHeureDebut IS NULL OR m.DateHeureDebut >= NOW())";
}

// Missions
$availableStmt = $pdo->prepare("
    SELECT
        m.IdMission, m.TitreMission, m.DescriptionMission, m.CategorieMission, m.LieuMission,
        m.DateHeureDebut, m.DateHeureFin, m.NbBenevolesAttendus,
        COUNT(p2.IdBenevole) AS nbInscrits,
        MAX(CASE WHEN pMe.IdBenevole IS NULL THEN 0 ELSE 1 END) AS dejaInscrit
    FROM Mission m
    LEFT JOIN Participer p2 ON p2.IdMission = m.IdMission
    LEFT JOIN Participer pMe ON pMe.IdMission = m.IdMission AND pMe.IdBenevole = :b
    $whereAvailable
    GROUP BY m.IdMission
    ORDER BY
        CASE WHEN m.DateHeureDebut IS NULL THEN 1 ELSE 0 END ASC,
        COALESCE(m.DateHeureDebut,'9999-12-31 23:59:59') ASC,
        m.IdMission DESC
    LIMIT 50
");
$availableStmt->execute([':b' => $idBenevole]);
$available = $availableStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mes missions • EGEE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../newcss.css">
</head>
<body>

<div class="dash-shell">

    <aside class="dash-side">
        <div class="dash-side-top">
            <div class="dash-brand">
                <div class="dash-avatar">U</div>
                <div>
                    <div class="dash-brand-title">Mon espace</div>
                    <div class="dash-brand-sub">BÉNÉVOLE</div>
                </div>
            </div>
        </div>

        <nav class="dash-menu">
            <a class="dash-link" href="dashboard.php">Tableau de bord</a>
            <a class="dash-link" href="profil.php">Mon profil</a>
            <a class="dash-link is-active" href="missions.php">Mes missions</a>
            <a class="dash-link" href="evenements.php">Mes événements</a>
            <a class="dash-link" href="../logout.php">Déconnexion</a>
        </nav>
    </aside>

    <main class="dash-main">

        <header class="dash-topbar">
            <div>
                <h1 class="dash-h1">Missions</h1>
                <p class="dash-sub">Inscris-toi aux missions créées par les admins et suis tes participations.</p>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="dash-alert dash-alert-success"><?= h($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="dash-alert dash-alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <!-- MES MISSIONS -->
        <section class="dash-card dash-tablecard ms-mycard">
            <div class="dash-card-head">
                <div class="dash-card-title">Mes missions (inscriptions)</div>
                <div class="dash-card-meta"><?= count($myMissions) ?> mission(s)</div>
            </div>

            <div class="dash-tablewrap">
                <table class="dash-table">
                    <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Lieu</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Rôle</th>
                        <th>Commentaire</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($myMissions)): ?>
                        <tr><td colspan="7" class="dash-td-empty">Tu n’es inscrit à aucune mission.</td></tr>
                    <?php else: foreach($myMissions as $m): ?>
                        <tr>
                            <td><?= h($m['TitreMission']) ?></td>
                            <td><?= h($m['LieuMission'] ?? '—') ?></td>
                            <td><?= fmt_dt($m['DateHeureDebut'] ?? null) ?></td>
                            <td><?= fmt_dt($m['DateHeureFin'] ?? null) ?></td>
                            <td><?= h($m['RoleBenevole'] ?? '—') ?></td>
                            <td><?= h($m['Commentaire'] ?? '') ?></td>
                            <td class="ms-right">
                                <form method="post" class="ms-inline">
                                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                    <input type="hidden" name="action" value="leave">
                                    <input type="hidden" name="id_mission" value="<?= (int)$m['IdMission'] ?>">
                                    <button class="dash-btn" type="submit" onclick="return confirm('Se désinscrire ?')">Se désinscrire</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- MISSIONS (ADMINS) -->
        <section class="dash-card dash-tablecard">
            <div class="dash-card-head ms-head">
                <div>
                    <div class="dash-card-title">Missions créées par les admins</div>
                    <div class="dash-card-meta"><?= count($available) ?> affichée(s)</div>
                </div>

                <div class="ms-tabs">
                    <a class="dash-btn <?= $view==='upcoming' ? 'dash-btn-primary' : '' ?>"
                       href="missions.php?view=upcoming">À venir</a>
                    <a class="dash-btn <?= $view==='all' ? 'dash-btn-primary' : '' ?>"
                       href="missions.php?view=all">Toutes</a>
                </div>
            </div>

            <div class="dash-tablewrap">
                <table class="dash-table">
                    <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Catégorie</th>
                        <th>Lieu</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Places</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($available)): ?>
                        <tr><td colspan="7" class="dash-td-empty">Aucune mission trouvée pour ce filtre.</td></tr>
                    <?php else: foreach($available as $m):
                        $att = (int)($m['NbBenevolesAttendus'] ?? 0);
                        $ins = (int)($m['nbInscrits'] ?? 0);
                        $deja = (int)($m['dejaInscrit'] ?? 0) === 1;
                        $complet = ($att > 0 && $ins >= $att);

                        $isPast = false;
                        if (!empty($m['DateHeureDebut'])) {
                            $isPast = (strtotime($m['DateHeureDebut']) < time());
                        }
                        ?>
                        <tr>
                            <td>
                                <div class="ms-title"><?= h($m['TitreMission'] ?? '') ?></div>
                                <?php if(!empty($m['DescriptionMission'])): ?>
                                    <div class="ms-desc"><?= h($m['DescriptionMission']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= h($m['CategorieMission'] ?? '—') ?></td>
                            <td><?= h($m['LieuMission'] ?? '—') ?></td>
                            <td><?= fmt_dt($m['DateHeureDebut'] ?? null) ?></td>
                            <td><?= fmt_dt($m['DateHeureFin'] ?? null) ?></td>
                            <td>
                                <?php if($att > 0): ?>
                                    <?= $ins ?> / <?= $att ?>
                                <?php else: ?>
                                    <?= $ins ?> inscrit(s)
                                <?php endif; ?>
                            </td>
                            <td class="ms-right">
                                <?php if($deja): ?>
                                    <span class="dash-badge">Déjà inscrit</span>
                                <?php elseif($isPast): ?>
                                    <span class="dash-badge">Terminée</span>
                                <?php elseif($complet): ?>
                                    <span class="dash-badge">Complet</span>
                                <?php else: ?>
                                    <form method="post" class="ms-join">
                                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="join">
                                        <input type="hidden" name="id_mission" value="<?= (int)$m['IdMission'] ?>">

                                        <div class="ms-joinrow">
                                            <input class="dash-input ms-role" name="role" placeholder="Rôle (ex: Bénévole)" value="Bénévole">
                                            <button class="dash-btn dash-btn-primary" type="submit">S’inscrire</button>
                                        </div>

                                        <textarea class="dash-input dash-textarea ms-comment" name="commentaire" rows="2" placeholder="Commentaire (optionnel)"></textarea>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="dash-spacer-10"></div>
    </main>
</div>

</body>
</html>
