<?php
session_start();
require_once __DIR__ . "/db.php";

// Sécurité: si db.php ne crée pas $pdo, on crash tout de suite (sinon $pdo rouge / fatal)
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erreur: \$pdo n'est pas initialisé. Vérifie db.php.");
}
