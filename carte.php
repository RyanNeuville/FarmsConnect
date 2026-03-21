<?php
/*
 * Fichier : carte.php
 * Page de visualisation cartographique de la ferme et des équipements.
 */

require_once 'config/db.php';
require_once 'includes/auth.php';

forcer_connexion();
$user_nom = $_SESSION['user_nom'] ?? 'Utilisateur';

// Récupérer tous les équipements avec leurs coordonnées
$stmt = $pdo->query("SELECT id, nom, type, statut, icone, couleur, latitude, longitude,
                           valeur_actuelle, unite
                    FROM equipements
                    WHERE latitude IS NOT NULL AND longitude IS NOT NULL
                    ORDER BY type, nom");
$equipements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la zone de la ferme
$stmtZone = $pdo->query("SELECT nom, coordonnees, couleur FROM zone_ferme LIMIT 1");
$zoneFerme = $stmtZone->fetch(PDO::FETCH_ASSOC);

// Calculer le centre de la carte basé sur les équipements
$latitudes = array_column($equipements, 'latitude');
$longitudes = array_column($equipements, 'longitude');

if (!empty($latitudes) && !empty($longitudes)) {
    $centerLat = (min($latitudes) + max($latitudes)) / 2;
    $centerLng = (min($longitudes) + max($longitudes)) / 2;
    $zoom = 18; // Zoom rapproché pour voir les détails
} else {
    // Coordonnées par défaut (Paris)
    $centerLat = 48.8566;
    $centerLng = 2.3522;
    $zoom = 13;
}

$active_nav = 'carte';
?>

<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte - FarmsConnect</title>
    <meta name="description" content="Visualisation cartographique de la ferme et des équipements">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>

    <!-- FarmsConnect Styles -->
    <link rel="stylesheet" href="css/app.css">

    <!-- Fonts and Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <style>
        #map {
            height: calc(100vh - 140px);
            width: 100%;
            border-radius: 16px;
            margin: 16px;
            margin-bottom: 80px;
        }

        .equipment-marker {
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            width: 32px;
            height: 32px;
        }

        .equipment-popup {
            min-width: 200px;
        }

        .equipment-popup h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
        }

        .equipment-popup .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .equipment-popup .value {
            font-size: 18px;
            font-weight: 700;
            margin: 4px 0;
        }

        .legend {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            max-width: 200px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 12px;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white font-inter h-full">
    <!-- Header -->
    <header class="flex justify-between items-start mt-4 mb-6 px-4">
        <div class="flex items-center gap-2">
            <div class="w-10 h-10 bg-brand-green-light border border-brand-green rounded-[14px] flex items-center justify-center">
                <img src="assets/icon.png" alt="FarmsConnect Logo" class="w-6 h-6" />
            </div>
            <div>
                <h1 class="text-[1.1rem] font-black text-brand-dark dark:text-white">Carte</h1>
                <p class="text-xs text-slate-400">Vue d'ensemble de la ferme</p>
            </div>
        </div>
    </header>

    <!-- Map Container -->
    <div id="map" class="card-border"></div>

    <!-- Legend -->
    <div class="legend">
        <h4 class="font-semibold text-sm mb-2">Légende</h4>
        <div class="legend-item">
            <div class="legend-dot bg-green-500"></div>
            <span>Capteur normal</span>
        </div>
        <div class="legend-item">
            <div class="legend-dot bg-orange-500"></div>
            <span>Capteur alerte</span>
        </div>
        <div class="legend-item">
            <div class="legend-dot bg-red-500"></div>
            <span>Capteur critique</span>
        </div>
        <div class="legend-item">
            <div class="legend-dot bg-blue-500"></div>
            <span>Détecteur mouvement</span>
        </div>
        <div class="legend-item">
            <div class="legend-dot bg-gray-500"></div>
            <span>Actionneur arrêté</span>
        </div>
        <div class="legend-item">
            <div class="legend-dot bg-green-600"></div>
            <span>Actionneur en marche</span>
        </div>
    </div>

    <!-- Navigation -->
    <?php include 'includes/nav.php'; ?>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

    <script>
        // Initialize map
        const map = L.map('map').setView([<?php echo $centerLat; ?>, <?php echo $centerLng; ?>], <?php echo $zoom; ?>);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Add farm zone if available
        <?php if ($zoneFerme && $zoneFerme['coordonnees']): ?>
        try {
            const zoneCoords = <?php echo $zoneFerme['coordonnees']; ?>;
            const zonePolygon = L.polygon(zoneCoords, {
                color: '<?php echo $zoneFerme['couleur']; ?>',
                fillColor: '<?php echo $zoneFerme['couleur']; ?>',
                fillOpacity: 0.1,
                weight: 2
            }).addTo(map);

            zonePolygon.bindPopup('<b><?php echo htmlspecialchars($zoneFerme['nom']); ?></b><br/>Zone délimitée de la ferme');
        } catch (e) {
            console.error('Erreur lors du chargement de la zone:', e);
        }
        <?php endif; ?>

        // Equipment data
        const equipements = <?php echo json_encode($equipements); ?>;

        // Color mapping for markers
        const colorMap = {
            'normal': '#10b981',    // green
            'alerte': '#f59e0b',    // orange
            'critique': '#ef4444',  // red
            'arret': '#6b7280',     // gray
            'marche': '#16a34a'     // green-600
        };

        // Icon mapping for equipment types
        const iconMap = {
            'capteur': '📊',
            'actionneur': '⚙️'
        };

        // Add equipment markers
        equipements.forEach(equipement => {
            if (!equipement.latitude || !equipement.longitude) return;

            const color = colorMap[equipement.statut] || '#6b7280';
            const icon = iconMap[equipement.type] || '📍';

            // Create custom marker
            const markerHtml = `
                <div class="equipment-marker" style="background-color: ${color}">
                    ${icon}
                </div>
            `;

            const customIcon = L.divIcon({
                html: markerHtml,
                className: 'custom-equipment-marker',
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });

            const marker = L.marker([equipement.latitude, equipement.longitude], {
                icon: customIcon
            }).addTo(map);

            // Create popup content
            let popupContent = `
                <div class="equipment-popup">
                    <h3>${equipement.nom}</h3>
                    <span class="status" style="background-color: ${color}20; color: ${color}; border: 1px solid ${color}40;">
                        ${equipement.statut.charAt(0).toUpperCase() + equipement.statut.slice(1)}
                    </span>
            `;

            if (equipement.type === 'capteur') {
                popupContent += `
                    <div class="value" style="color: ${color}">
                        ${equipement.valeur_actuelle} ${equipement.unite}
                    </div>
                `;
            } else {
                popupContent += `
                    <div class="value" style="color: ${color}">
                        ${equipement.statut === 'marche' ? 'En marche' : 'Arrêté'}
                    </div>
                `;
            }

            popupContent += `
                    <div class="text-xs text-gray-600 mt-2">
                        Type: ${equipement.type}<br>
                        ID: ${equipement.id}
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent);
        });

        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>