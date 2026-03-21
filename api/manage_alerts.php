<?php
/**
 * API : Gestion globale des alertes (Tout lire / Tout supprimer).
 */
require_once '../config/db.php';
require_once '../includes/auth.php';

forcer_connexion();

$action = $_GET['action'] ?? '';

try {
    if ($action === 'mark_all_read') {
        $pdo->query("UPDATE alertes SET est_lu = 1 WHERE est_lu = 0");
        header('Location: ../alertes.php?status=all_read');
    } elseif ($action === 'delete_all') {
        $pdo->query("DELETE FROM alertes");
        header('Location: ../alertes.php?status=deleted');
    } else {
        header('Location: ../alertes.php');
    }
} catch (Exception $e) {
    header('Location: ../alertes.php?error=1');
}
exit;
