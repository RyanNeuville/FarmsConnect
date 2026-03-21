<?php
/*
 * Fichier : api/get_map_data.php
 * API pour récupérer les données cartographiques (équipements et zones).
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!est_connecte()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

// Récupérer tous les équipements avec coordonnées
$stmt = $pdo->prepare("SELECT id, nom, type, statut, icone, couleur, latitude, longitude,
                             valeur_actuelle, unite
                      FROM equipements
                      WHERE latitude IS NOT NULL AND longitude IS NOT NULL
                      ORDER BY type, nom");
$stmt->execute();
$equipements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les zones de la ferme
$stmtZones = $pdo->query("SELECT id, nom, coordonnees, couleur FROM zone_ferme ORDER BY cree_le DESC");
$zonesRaw = $stmtZones->fetchAll(PDO::FETCH_ASSOC);

// Décoder les coordonnées JSON pour chaque zone
$zones = array_map(function($zone) {
    $zone['coordonnees'] = json_decode($zone['coordonnees'], true);
    return $zone;
}, $zonesRaw);

// Calculer le centre de la carte
$latitudes = array_column($equipements, 'latitude');
$longitudes = array_column($equipements, 'longitude');

$center = [
    'lat' => !empty($latitudes) ? (min($latitudes) + max($latitudes)) / 2 : 48.8566,
    'lng' => !empty($longitudes) ? (min($longitudes) + max($longitudes)) / 2 : 2.3522
];

$zoom = (!empty($latitudes) && !empty($longitudes)) ? 18 : 13;

echo json_encode([
    'status' => 'success',
    'timestamp' => date('H:i:s'),
    'center' => $center,
    'zoom' => $zoom,
    'equipements' => $equipements,
    'zones' => $zones
]);
?>