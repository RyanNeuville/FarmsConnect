<?php
/*
 * Fichier : api/get_stats.php
 * Point de terminaison API JSON pour la récupération synchrone de l'état du système.
 * Utilisé par le frontend pour les mises à jour dynamiques sans rafraîchissement.
 */
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/auth.php';

/* Sécurisation de l'accès API : La session doit être active pour consommer les données */
if (!est_connecte()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    /* Extraction des métriques actuelles de tous les équipements */
    $stmt = $pdo->query("SELECT id, nom, valeur_actuelle, statut, unite, icone, couleur FROM equipements ORDER BY id ASC");
    $equipements = $stmt->fetchAll();

    /* Calcul du volume d'alertes critiques/importantes en attente de lecture */
    $stmtAlertes = $pdo->query("SELECT COUNT(*) as nb FROM alertes WHERE est_lu = 0");
    $alertesCount = (int)$stmtAlertes->fetch()['nb'];

    /* Agrégation de la réponse structurée */
    echo json_encode([
        'status' => 'success',
        'timestamp' => date('H:i'),
        'alertes_count' => $alertesCount,
        'equipements' => $equipements
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur lors de la récupération des données']);
}
