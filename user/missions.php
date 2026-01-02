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

$idBenevole = (int)$_SESSION['auth']['id_benevole'];
$success = null;
$error = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $idMission = (int)($_POST['id_mission'] ?? 0);

    try {
        if ($idMission <= 0) throw new Exception("Mission invalide.");

        if ($action === 'join') {
            $role = trim($_POST['role'] ?? 'Bénévole');
            $comment = trim($_POST['commentaire'] ?? '');


            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Mission WHERE IdMission = :m AND (DateHeureDebut IS NULL OR DateHeureDebut >= NOW())");
            $stmt->execute([':m' => $idMission]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new Exception("Cette mission n'est pas disponible.");
            }


            $stmt = $pdo->prepare("
                INSERT INTO Participer (IdMission, IdBenevole, RoleBenevole, Duree, Commentaire)
                VALUES (:m, :b, :r, NULL, :c)
            ");
            $stmt->execute([
                ':m' => $idMission,
                ':b' => $idBenevole,
                ':r' => $role,
                ':c' => $comment
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
            $error = "Erreur : " . $e->getMessage();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}


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


$availableStmt = $pdo->prepare("
    SELECT
        m.IdMission, m.TitreMission, m.DescriptionMission, m.CategorieMission, m.LieuMission,
        m.DateHeureDebut, m.DateHeureFin, m.NbBenevolesAttendus,
        COUNT(p2.IdBenevole) AS nbInscrits,
        MAX(CASE WHEN pMe.IdBenevole IS NULL THEN 0 ELSE 1 END) AS dejaInscrit
    FROM Mission m
    LEFT JOIN Participer p2 ON p2.IdMission = m.IdMission
    LEFT JOIN Participer pMe ON pMe.IdMission = m.IdMission AND pMe.IdBenevole = :b
    WHERE m.DateHeureDebut IS NULL OR m.DateHeureDebut >= NOW()
    GROUP BY m.IdMission
    ORDER BY COALESCE(m.DateHeureDebut,'9999-12-31 23:59:59') ASC
    LIMIT 30
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
            <a class="dash-link" href="missions.php">Mes missions</a>
            <a class="dash-link is-active" href="evenements.php">Mes événements</a>
            <a class="dash-link" href="../logout.php">Déconnexion</a>
        </nav>
    </aside>


    <main class="dash-main">

        <header class="dash-topbar">
            <div>
                <h1 class="dash-h1">Missions</h1>
                <p class="dash-sub">Inscris-toi aux missions et suis tes participations.</p>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="dash-alert dash-alert-success"><?= h($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="dash-alert dash-alert-error"><?= h($error) ?></div>
        <?php endif; ?>


        <section class="dash-card dash-tablecard" style="margin-bottom:12px;">
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
                        <th>Rôle</th>
                        <th>Commentaire</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($myMissions)): ?>
                        <tr><td colspan="6" class="dash-td-empty">Tu n’es inscrit à aucune mission.</td></tr>
                    <?php else: foreach($myMissions as $m): ?>
                        <tr>
                            <td><?= h($m['TitreMission']) ?></td>
                            <td><?= h($m['LieuMission'] ?? '—') ?></td>
                            <td><?= h($m['DateHeureDebut'] ?? '—') ?></td>
                            <td><?= h($m['RoleBenevole'] ?? '—') ?></td>
                            <td><?= h($m['Commentaire'] ?? '') ?></td>
                            <td style="text-align:right;">
                                <form method="post" style="display:inline;">
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


        <section class="dash-card">
            <div class="dash-card-head">
                <div class="dash-card-title">Missions disponibles</div>
                <div class="dash-card-meta"><?= count($available) ?> affichée(s)</div>
            </div>

            <div class="dash-card-body" style="display:grid; gap:10px;">
                <?php if(empty($available)): ?>
                    <div class="dash-empty">Aucune mission disponible pour le moment.</div>
                <?php else: foreach($available as $m):
                    $att = (int)($m['NbBenevolesAttendus'] ?? 0);
                    $ins = (int)($m['nbInscrits'] ?? 0);
                    $reste = ($att > 0) ? max(0, $att - $ins) : null;
                    $deja = (int)($m['dejaInscrit'] ?? 0) === 1;
                    $complet = ($att > 0 && $ins >= $att);
                    ?>
                    <div class="dash-card" style="padding:14px;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <div>
                                <div style="font-weight:800; font-size:1.02rem;"><?= h($m['TitreMission']) ?></div>
                                <div style="color:#6b7c98; font-size:.9rem;">
                                    <?= h($m['CategorieMission'] ?? '—') ?> • <?= h($m['LieuMission'] ?? '—') ?>
                                </div>
                                <div style="color:#6b7c98; font-size:.9rem;">
                                    Début : <?= h($m['DateHeureDebut'] ?? '—') ?>
                                    <?php if (!empty($m['DateHeureFin'])): ?> • Fin : <?= h($m['DateHeureFin']) ?><?php endif; ?>
                                </div>
                                <?php if (!empty($m['DescriptionMission'])): ?>
                                    <div style="margin-top:6px; color:#334155;">
                                        <?= h($m['DescriptionMission']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div style="text-align:right;">
                                <?php if ($att > 0): ?>
                                    <div style="font-weight:800;"><?= $ins ?>/<?= $att ?></div>
                                    <div style="color:#6b7c98;font-size:.85rem;">
                                        <?= $complet ? "Complet" : ($reste." place(s)") ?>
                                    </div>
                                <?php else: ?>
                                    <div style="color:#6b7c98;font-size:.85rem;">Places: —</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="height:10px"></div>

                        <?php if ($deja): ?>
                            <div style="color:#16a34a;font-weight:700;">✅ Déjà inscrit</div>
                        <?php elseif ($complet): ?>
                            <div style="color:#ef4444;font-weight:700;">❌ Mission complète</div>
                        <?php else: ?>
                            <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                                <input type="hidden" name="action" value="join">
                                <input type="hidden" name="id_mission" value="<?= (int)$m['IdMission'] ?>">

                                <select class="dash-input" name="role" style="max-width:220px;">
                                    <option value="Bénévole">Bénévole</option>
                                    <option value="Accueil">Accueil</option>
                                    <option value="Logistique">Logistique</option>
                                    <option value="Organisation">Organisation</option>
                                    <option value="Terrain">Terrain</option>
                                </select>

                                <input class="dash-input" name="commentaire" placeholder="Commentaire (optionnel)" style="flex:1; min-width:220px;">

                                <button class="dash-btn dash-btn-primary" type="submit">S’inscrire</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </section>

    </main>
</div>


<style>
    .dash-alert{padding:14px 18px;border-radius:14px;margin-bottom:16px;font-weight:600;}
    .dash-alert-success{background:#ecfdf5;border:1px solid #34d399;color:#065f46;}
    .dash-alert-error{background:#fef2f2;border:1px solid #f87171;color:#7f1d1d;}
    .dash-input{width:100%;padding:12px 14px;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;}
    .dash-input:focus{outline:none;border-color:#2563eb;background:#fff;box-shadow:0 0 0 3px rgba(37,99,235,.15);}
</style>

</body>
</html>
