<?php
require_once __DIR__ . "/../init.php";

/* ===== GUARD USER ===== */
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

/* ===== ACTIONS INSCRIPTION / DESINSCRIPTION ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $idEvenement = (int)($_POST['id_evenement'] ?? 0);

    try {
        if ($idEvenement <= 0) throw new Exception("Événement invalide.");

        if ($action === 'join') {
            $role = trim($_POST['role'] ?? 'Participant');

            // vérifier que l'événement existe et est à venir (date >= aujourd'hui)
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM Evenement
                WHERE IdEvenement = :e
                  AND (DateEvenement IS NULL OR DateEvenement >= CURDATE())
            ");
            $stmt->execute([':e' => $idEvenement]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new Exception("Cet événement n'est pas disponible.");
            }

            // inscription (PK (IdBenevole, IdEvenement))
            $stmt = $pdo->prepare("
                INSERT INTO Assister (IdBenevole, IdEvenement, Role, EstPresent)
                VALUES (:b, :e, :r, 0)
            ");
            $stmt->execute([
                ':b' => $idBenevole,
                ':e' => $idEvenement,
                ':r' => $role
            ]);

            $success = "✅ Inscription à l’événement réussie !";

        } elseif ($action === 'leave') {
            $stmt = $pdo->prepare("DELETE FROM Assister WHERE IdBenevole = :b AND IdEvenement = :e");
            $stmt->execute([':b' => $idBenevole, ':e' => $idEvenement]);

            $success = "✅ Désinscription effectuée.";
        }

    } catch (PDOException $e) {
        // Duplicate entry => déjà inscrit
        if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), '1062')) {
            $error = "Tu es déjà inscrit à cet événement.";
        } else {
            $error = "Erreur : " . $e->getMessage();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

/* ===== EVENEMENTS USER (déjà inscrit) ===== */
$myEventsStmt = $pdo->prepare("
    SELECT e.IdEvenement, e.NomEvenement, e.TypeEvenement, e.DateEvenement, e.HeureEvenement,
           a.Role, a.EstPresent
    FROM Assister a
    JOIN Evenement e ON e.IdEvenement = a.IdEvenement
    WHERE a.IdBenevole = :b
    ORDER BY COALESCE(e.DateEvenement,'1970-01-01') DESC, e.IdEvenement DESC
");
$myEventsStmt->execute([':b' => $idBenevole]);
$myEvents = $myEventsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* ===== EVENEMENTS DISPONIBLES (à venir) ===== */
$availableStmt = $pdo->prepare("
    SELECT
        e.IdEvenement, e.NomEvenement, e.TypeEvenement, e.DateEvenement, e.HeureEvenement, e.LienMediaEvenement,
        MAX(CASE WHEN aMe.IdBenevole IS NULL THEN 0 ELSE 1 END) AS dejaInscrit,
        COUNT(a2.IdBenevole) AS nbInscrits
    FROM Evenement e
    LEFT JOIN Assister a2 ON a2.IdEvenement = e.IdEvenement
    LEFT JOIN Assister aMe ON aMe.IdEvenement = e.IdEvenement AND aMe.IdBenevole = :b
    WHERE e.DateEvenement IS NULL OR e.DateEvenement >= CURDATE()
    GROUP BY e.IdEvenement
    ORDER BY COALESCE(e.DateEvenement,'9999-12-31') ASC, COALESCE(e.HeureEvenement,'23:59:59') ASC
    LIMIT 30
");
$availableStmt->execute([':b' => $idBenevole]);
$available = $availableStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mes événements • EGEE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../newcss.css">
</head>
<body>

<div class="dash-shell">

    <!-- SIDEBAR USER -->
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

    <!-- MAIN -->
    <main class="dash-main">

        <header class="dash-topbar">
            <div>
                <h1 class="dash-h1">Événements</h1>
                <p class="dash-sub">Inscris-toi aux événements et retrouve tes participations.</p>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="dash-alert dash-alert-success"><?= h($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="dash-alert dash-alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <!-- MES EVENEMENTS -->
        <section class="dash-card dash-tablecard" style="margin-bottom:12px;">
            <div class="dash-card-head">
                <div class="dash-card-title">Mes événements (inscriptions)</div>
                <div class="dash-card-meta"><?= count($myEvents) ?> événement(s)</div>
            </div>

            <div class="dash-tablewrap">
                <table class="dash-table">
                    <thead>
                    <tr>
                        <th>Événement</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Rôle</th>
                        <th>Présence</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($myEvents)): ?>
                        <tr><td colspan="6" class="dash-td-empty">Tu n’es inscrit à aucun événement.</td></tr>
                    <?php else: foreach($myEvents as $e): ?>
                        <tr>
                            <td><?= h($e['NomEvenement']) ?></td>
                            <td><?= h($e['TypeEvenement'] ?? '—') ?></td>
                            <td><?= h(($e['DateEvenement'] ?? '—') . ' ' . ($e['HeureEvenement'] ?? '')) ?></td>
                            <td><?= h($e['Role'] ?? '—') ?></td>
                            <td><?= ((int)($e['EstPresent'] ?? 0) === 1) ? '✅' : '—' ?></td>
                            <td style="text-align:right;">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="leave">
                                    <input type="hidden" name="id_evenement" value="<?= (int)$e['IdEvenement'] ?>">
                                    <button class="dash-btn" type="submit" onclick="return confirm('Se désinscrire ?')">Se désinscrire</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- EVENEMENTS DISPONIBLES -->
        <section class="dash-card">
            <div class="dash-card-head">
                <div class="dash-card-title">Événements disponibles</div>
                <div class="dash-card-meta"><?= count($available) ?> affiché(s)</div>
            </div>

            <div class="dash-card-body" style="display:grid; gap:10px;">
                <?php if(empty($available)): ?>
                    <div class="dash-empty">Aucun événement disponible pour le moment.</div>
                <?php else: foreach($available as $e):
                    $deja = (int)($e['dejaInscrit'] ?? 0) === 1;
                    $nbIns = (int)($e['nbInscrits'] ?? 0);
                    ?>
                    <div class="dash-card" style="padding:14px;">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <div>
                                <div style="font-weight:800; font-size:1.02rem;"><?= h($e['NomEvenement']) ?></div>
                                <div style="color:#6b7c98; font-size:.9rem;">
                                    <?= h($e['TypeEvenement'] ?? '—') ?> •
                                    <?= h($e['DateEvenement'] ?? '—') ?>
                                    <?= !empty($e['HeureEvenement']) ? (' • '.h($e['HeureEvenement'])) : '' ?>
                                </div>
                                <?php if (!empty($e['LienMediaEvenement'])): ?>
                                    <div style="margin-top:6px; font-size:.9rem;">
                                        <a href="<?= h($e['LienMediaEvenement']) ?>" target="_blank" rel="noopener noreferrer">Lien / média</a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div style="text-align:right;">
                                <div style="font-weight:800;"><?= $nbIns ?></div>
                                <div style="color:#6b7c98;font-size:.85rem;">inscrit(s)</div>
                            </div>
                        </div>

                        <div style="height:10px"></div>

                        <?php if ($deja): ?>
                            <div style="color:#16a34a;font-weight:700;">✅ Déjà inscrit</div>
                        <?php else: ?>
                            <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                                <input type="hidden" name="action" value="join">
                                <input type="hidden" name="id_evenement" value="<?= (int)$e['IdEvenement'] ?>">

                                <select class="dash-input" name="role" style="max-width:220px;">
                                    <option value="Participant">Participant</option>
                                    <option value="Accueil">Accueil</option>
                                    <option value="Logistique">Logistique</option>
                                    <option value="Organisation">Organisation</option>
                                    <option value="Distribution">Distribution</option>
                                </select>

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
