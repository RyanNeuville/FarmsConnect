<?php
/*
 * Fichier : api/get_stats.php
 * Point de terminaison API JSON pour la récupération synchrone de l'état du système.
 * Utilisé par le frontend pour les mises à jour dynamiques sans rafraîchissement.
 */
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/simulation_engine.php';
require_once '../includes/functions.php';

/* Sécurisation de l'accès API : La session doit être active pour consommer les données */
if (!est_connecte()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

/* 
 * DÉCLENCHEMENT DE L'INTELLIGENCE AMBIANTE (Simulation invisible)
 * Le moteur s'exécute silencieusement en arrière-plan à chaque appel 
 * si le cooldown de 5 secondes est expiré.
 */
runSimulationEngine($pdo);

try {
    /* Extraction des métriques actuelles de tous les équipements */
    $stmt = $pdo->query("SELECT id, nom, valeur_actuelle, statut, unite, icone, couleur FROM equipements ORDER BY id ASC");
    $equipements = $stmt->fetchAll();

    /* Calcul du volume d'alertes critiques/importantes en attente de lecture */
    $stmtAlertes = $pdo->query("SELECT COUNT(*) as nb FROM alertes WHERE est_lu = 0");
    $alertesCount = (int)$stmtAlertes->fetch()['nb'];

    /* Récupération des 4 dernières alertes/activités pour le flux dynamique */
    $stmtRecent = $pdo->query("
        SELECT a.*, e.nom as equipement_nom, e.icone 
        FROM alertes a 
        JOIN equipements e ON a.equipement_id = e.id 
        ORDER BY a.cree_le DESC 
        LIMIT 4
    ");
    $activites = $stmtRecent->fetchAll();

    /* Simulation de données météo temporelles centralisées */
    $weather = simulateWeather();

    /* Calcul de l'état global du système */
    $hasCritical = false;
    foreach ($equipements as $eq) {
        if ($eq['statut'] === 'critique') {
            $hasCritical = true;
            break;
        }
    }

    $systemStatus = [
        'is_ok' => !$hasCritical,
        'message' => $hasCritical ? 'Attention, anomalie détectée ⚠️' : 'Tout va bien 🌾',
        'class' => $hasCritical ? 'text-red-600 bg-red-50 border-red-200' : 'text-green-600 bg-green-50 border-green-200'
    ];

    /* Agrégation de la réponse structurée */
    echo json_encode([
        'status' => 'success',
        'timestamp' => date('H:i'),
        'alertes_count' => $alertesCount,
        'equipements' => $equipements,
        'activites' => $activites,
        'weather' => $weather,
        'system_status' => $systemStatus
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur lors de la récupération des données']);
}
