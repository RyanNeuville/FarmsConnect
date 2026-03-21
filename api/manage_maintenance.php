<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!est_connecte()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'clear_alerts') {
    $pdo->exec("DELETE FROM alertes");
    echo json_encode(['success' => true, 'message' => 'Toutes les alertes ont été supprimées.']);
} elseif ($action === 'clear_history') {
    $pdo->exec("DELETE FROM historique_donnees");
    echo json_encode(['success' => true, 'message' => 'L\'historique des données a été vidé.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Action non reconnue.']);
}
