<?php
/**
 * API : Gestion globale des alertes (Tout lire / Tout supprimer).
 * Supporte AJAX (retourne JSON) et requêtes directes (redirection).
 */
require_once '../config/db.php';
require_once '../includes/auth.php';

forcer_connexion();

$action = $_GET['action'] ?? '';
$isAjax = !isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false
          || isset($_SERVER['HTTP_X_REQUESTED_WITH']);

// Détection fetch/AJAX : si le navigateur n'envoie pas de HTML dans Accept, c'est AJAX
$wantsJson = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
           || (isset($_SERVER['HTTP_X_REQUESTED_WITH']));

// Forcer JSON si appelé via fetch() sans Referer d'HTML classique
$isFetch = isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors'
        || (isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] === 'empty');

$returnJson = $wantsJson || $isFetch;

function respond($success, $message, $returnJson) {
    if ($returnJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
    } else {
        header('Location: ../alertes.php');
    }
    exit;
}

try {
    if ($action === 'mark_all_read') {
        $pdo->query("UPDATE alertes SET est_lu = 1 WHERE est_lu = 0");
        respond(true, 'Toutes les alertes ont été marquées comme lues.', $returnJson);
    } elseif ($action === 'delete_all') {
        $pdo->query("DELETE FROM alertes");
        respond(true, 'Journal des alertes vidé avec succès.', $returnJson);
    } else {
        respond(false, 'Action inconnue.', $returnJson);
    }
} catch (Exception $e) {
    respond(false, 'Erreur lors de l\'opération.', $returnJson);
}
