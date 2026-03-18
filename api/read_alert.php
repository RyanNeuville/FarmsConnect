<?php
// Fichier: api/read_alert.php
require_once '../config/db.php';
require_once '../includes/auth.php';

forcer_connexion();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $pdo->prepare("UPDATE alertes SET est_lu = 1 WHERE id = ?")->execute([$id]);
}

if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: ../alertes.php");
}
exit;
?>
