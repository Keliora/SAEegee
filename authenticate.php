<?php
require_once "init.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['login_error'] = "Accès interdit.";
    header("Location: login.php");
    exit;
}

$roleForm = $_POST['role'] ?? 'user';
$email    = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = "Veuillez remplir tous les champs.";
    header("Location: login.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['login_error'] = "Email invalide.";
    header("Location: login.php");
    exit;
}


$expectedRole = ($roleForm === 'admin') ? 'ADMIN' : 'USER';

$sql = "SELECT IdBenevole, Email, Password, PrenomBenevole, NomBenevole, Role
        FROM Benevole
        WHERE Email = :email AND Role = :role
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':email' => $email,
    ':role'  => $expectedRole
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['login_error'] = "Compte introuvable ou rôle non autorisé.";
    header("Location: login.php");
    exit;
}

if (empty($user['Password']) || !password_verify($password, $user['Password'])) {
    $_SESSION['login_error'] = "Mot de passe incorrect.";
    header("Location: login.php");
    exit;
}

$_SESSION['auth'] = [
    'id_benevole' => (int)$user['IdBenevole'],
    'email'       => $user['Email'],
    'prenom'      => $user['PrenomBenevole'],
    'nom'         => $user['NomBenevole'],
    'role'        => $user['Role'],
];

unset($_SESSION['login_error']);


if ($user['Role'] === 'ADMIN') {
    header("Location: admin/dashboard.php");
} else {
    header("Location: index.php");
}
exit;
