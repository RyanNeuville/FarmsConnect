<?php
/**
 * API : Récupération de l'historique et des détails d'un capteur spécifique.
 * Retourne les 7 derniers points de mesure pour le graphique SVG.
 */
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/simulation_engine.php';

if (!est_connecte()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID d\'équipement invalide']);
    exit;
}

// On déclenche le moteur de simulation au passage pour le dynamisme global
runSimulationEngine($pdo);

try {
    // 1. Récupération des infos actuelles
    $stmt = $pdo->prepare("SELECT * FROM equipements WHERE id = ? AND type = 'capteur'");
    $stmt->execute([$id]);
    $capteur = $stmt->fetch();

    if (!$capteur) {
        http_response_code(404);
        echo json_encode(['error' => 'Capteur non trouvé']);
        exit;
    }

    // 2. Récupération de l'historique (les 7 derniers points)
    $stmtHist = $pdo->prepare("SELECT valeur, enregistre_le FROM historique_donnees WHERE equipement_id = ? ORDER BY enregistre_le DESC LIMIT 10");
    $stmtHist->execute([$id]);
    $historique = array_reverse($stmtHist->fetchAll()); // On remet dans l'ordre chronologique

    echo json_encode([
        'status' => 'success',
        'timestamp' => date('H:i'),
        'capteur' => $capteur,
        'historique' => $historique
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
