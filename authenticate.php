<?php
require_once "init.php";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['login_error'] = "Accès interdit.";
    header("Location: login.php");
    exit;
}


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


$sql = "SELECT IdBenevole, Email, Password, PrenomBenevole, NomBenevole
        FROM Benevole
        WHERE Email = :email
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['login_error'] = "Compte introuvable.";
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
];

// Redirection
unset($_SESSION['login_error']);
header("Location: dashboard.php");
exit;
