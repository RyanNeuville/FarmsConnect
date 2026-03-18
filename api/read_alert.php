<?php
/*
 * Fichier : api/read_alert.php
 * Contrôleur miniature (Micro-service) chargé de l'acquittement des notifications d'anomalies.
 * Met à jour le flag de lecture (est_lu) dans le registre des incidents.
 */
require_once '../config/db.php';
require_once '../includes/auth.php';

forcer_connexion();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    /* Sécurisation par statement préparé pour basculer l'état booléen d'acquittement de la notification */
    $pdo->prepare("UPDATE alertes SET est_lu = 1 WHERE id = ?")->execute([$id]);
}

/* Redirection dynamique adaptative exploitant le REFERER pour maintenir le contexte de l'opérateur */
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: ../alertes.php");
}
exit;
?>
