<?php
require_once "init.php"; // doit faire session_start() + $pdo

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['register_error'] = "Accès interdit.";
    header("Location: register.php");
    exit;
}

$prenom  = trim($_POST['prenom'] ?? '');
$nom     = trim($_POST['nom'] ?? '');
$email   = trim($_POST['email'] ?? '');
$pass    = $_POST['password'] ?? '';
$pass2   = $_POST['password2'] ?? '';

$ville   = trim($_POST['ville'] ?? '');
$numero  = trim($_POST['numero'] ?? '');

// pour ré-afficher les champs (sans mot de passe)
$_SESSION['register_old'] = [
    'prenom' => $prenom,
    'nom'    => $nom,
    'email'  => $email,
    'ville'  => $ville,
    'numero' => $numero,
];

if ($prenom === '' || $nom === '' || $email === '' || $pass === '' || $pass2 === '') {
    $_SESSION['register_error'] = "Veuillez remplir tous les champs obligatoires.";
    header("Location: register.php"); exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = "Email invalide.";
    header("Location: register.php"); exit;
}

if ($pass !== $pass2) {
    $_SESSION['register_error'] = "Les mots de passe ne correspondent pas.";
    header("Location: register.php"); exit;
}

if (strlen($pass) < 8) {
    $_SESSION['register_error'] = "Mot de passe trop court (min 8 caractères).";
    header("Location: register.php"); exit;
}

// email déjà pris ?
$check = $pdo->prepare("SELECT 1 FROM Benevole WHERE Email = :email LIMIT 1");
$check->execute([':email' => $email]);
if ($check->fetch()) {
    $_SESSION['register_error'] = "Cet email est déjà utilisé.";
    header("Location: register.php"); exit;
}

$hash = password_hash($pass, PASSWORD_DEFAULT);

// INSERT : Role USER par défaut
$sql = "INSERT INTO Benevole (
            NomBenevole,
            PrenomBenevole,
            NumeroBenevole,
            VilleBenevole,
            DateInscriptionBenevole,
            Email,
            Password,
            Role
        )
        VALUES (
            :nom,
            :prenom,
            :numero,
            :ville,
            CURDATE(),
            :email,
            :pwd,
            'USER'
        )";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':nom'    => $nom,
    ':prenom' => $prenom,
    ':numero' => ($numero !== '' ? $numero : null),
    ':ville'  => ($ville !== '' ? $ville : null),
    ':email'  => $email,
    ':pwd'    => $hash,
]);

unset($_SESSION['register_old']);

$_SESSION['login_error'] = "Compte créé ✅ Tu peux te connecter.";
header("Location: login.php");
exit;

