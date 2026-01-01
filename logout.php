<?php
require_once "init.php";
session_destroy();
session_start();
$_SESSION['login_error'] = "Déconnecté.";
header("Location: login.php");
exit;
