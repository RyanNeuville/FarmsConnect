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

/* Inclusion logique UI et encapsulation layout globale */
require_once 'includes/functions.php';

$page_title = 'Carte - FarmsConnect';
$active_nav = 'carte';

require 'includes/header.php';
?>

<!-- HEADER -->
<header class="flex justify-between items-start mt-4 mb-6 px-4">
    <div class="flex items-center gap-2">
        <div class="w-10 h-10 bg-green-100 border border-green-200 rounded-[14px] flex items-center justify-center text-green-600">
            <img src="assets/icon.png" alt="FarmsConnect Logo" class="w-6 h-6" />
        </div>
        <div>
            <h1 class="text-[1.1rem] font-black text-slate-800 dark:text-white leading-tight">Carte</h1>
            <p class="text-xs text-slate-400 font-bold">Vue d'ensemble de la ferme</p>
        </div>
    </div>
</header>

<!-- MAP CONTAINER -->
<div id="map" class="card-border mx-4 mb-4 rounded-2xl overflow-hidden" style="height: calc(100vh - 180px);"></div>

<!-- LEGEND (Fixed position) -->
<div class="fixed top-24 right-3 bg-white/95 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700 rounded-2xl p-3 shadow-2xl z-[1000] max-w-[220px] backdrop-blur-sm">
    <h4 class="font-bold text-sm mb-3 text-slate-800 dark:text-white">Légende</h4>
    <div class="space-y-2">
        <div class="flex items-center gap-3">
            <div class="w-4 h-4 rounded-full bg-green-500 border-2 border-white shadow-sm"></div>
            <span class="text-xs text-slate-600 dark:text-slate-300">Capteur normal</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="w-4 h-4 rounded-full bg-orange-500 border-2 border-white shadow-sm"></div>
            <span class="text-xs text-slate-600 dark:text-slate-300">Capteur alerte</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="w-4 h-4 rounded-full bg-red-500 border-2 border-white shadow-sm"></div>
            <span class="text-xs text-slate-600 dark:text-slate-300">Capteur critique</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="w-4 h-4 rounded-full bg-blue-500 border-2 border-white shadow-sm"></div>
            <span class="text-xs text-slate-600 dark:text-slate-300">Détecteur mouvement</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="w-4 h-4 rounded-full bg-gray-500 border-2 border-white shadow-sm"></div>
            <span class="text-xs text-slate-600 dark:text-slate-300">Actionneur arrêté</span>
        </div>
        <div class="flex items-center gap-3">
            <div class="w-4 h-4 rounded-full bg-green-600 border-2 border-white shadow-sm"></div>
            <span class="text-xs text-slate-600 dark:text-slate-300">Actionneur en marche</span>
        </div>
    </div>
</div>

<!-- STATUS BAR (Fixed position) -->
<div class="fixed bottom-20 left-4 right-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-lg z-[1000]">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
            <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Synchronisation active</span>
        </div>
        <div class="text-xs text-slate-400" id="last-map-update">Mis à jour à l'instant</div>
    </div>
</div>

<div class="fixed bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-white/95 backdrop-blur-sm z-[999]">

<!-- Spacer for bottom nav -->
    <?php 
require 'includes/nav.php'; ?>
</div>
<?php
require 'includes/footer.php';
?>

<!-- LEAFLET CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""/>

<!-- LEAFLET POPUP STYLES -->
<style>
    .leaflet-popup-content-wrapper {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-family: inherit;
    }

    .leaflet-popup-tip {
        background-color: white;
    }

    /* Dark mode support for Leaflet popups */
    html.dark .leaflet-popup-content-wrapper {
        background-color: rgb(30 41 59);
        color: rgb(241 245 249);
    }

    html.dark .leaflet-popup-tip {
        background-color: rgb(30 41 59);
    }
</style>

<!-- LEAFLET JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

<script>
    // Global variables
    let map;
    let equipmentMarkers = [];
    let farmZoneLayer = null;
    let lastUpdateTime = Date.now();

    // Equipment data cache
    let currentEquipments = <?php echo json_encode($equipements); ?>;
    let currentZone = <?php echo $zoneFerme ? json_encode($zoneFerme) : 'null'; ?>;

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

    // Initialize map
    function initMap() {
        map = L.map('map').setView([<?php echo $centerLat; ?>, <?php echo $centerLng; ?>], <?php echo $zoom; ?>);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Add farm zone
        updateFarmZone();

        // Add equipment markers
        updateEquipmentMarkers();

        // Start real-time updates
        startRealtimeUpdates();
    }

    // Update farm zone display
    function updateFarmZone() {
        // Remove existing zone
        if (farmZoneLayer) {
            map.removeLayer(farmZoneLayer);
        }

        // Add new zone if available
        if (currentZone && currentZone.coordonnees) {
            try {
                const zoneCoords = currentZone.coordonnees;
                farmZoneLayer = L.polygon(zoneCoords, {
                    color: currentZone.couleur || '#22c55e',
                    fillColor: currentZone.couleur || '#22c55e',
                    fillOpacity: 0.1,
                    weight: 2
                }).addTo(map);

                farmZoneLayer.bindPopup('<b>' + (currentZone.nom || 'Zone de ferme') + '</b><br/>Zone délimitée de la ferme');
            } catch (e) {
                console.error('Erreur lors du chargement de la zone:', e);
            }
        }
    }

    // Update equipment markers
    function updateEquipmentMarkers() {
        // Clear existing markers
        equipmentMarkers.forEach(marker => map.removeLayer(marker));
        equipmentMarkers = [];

        // Add new markers
        currentEquipments.forEach(equipement => {
            if (!equipement.latitude || !equipement.longitude) return;

            const color = colorMap[equipement.statut] || '#6b7280';
            const icon = iconMap[equipement.type] || '📍';

            // Create custom marker
            const markerHtml = `
                <div class="rounded-full border-2 border-white shadow-lg flex items-center justify-center text-white font-bold text-xs w-7 h-7" style="background-color: ${color}">
                    ${icon}
                </div>
            `;

            const customIcon = L.divIcon({
                html: markerHtml,
                className: 'custom-equipment-marker',
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            });

            const marker = L.marker([equipement.latitude, equipement.longitude], {
                icon: customIcon
            }).addTo(map);

            // Create popup content
            let popupContent = `
                <div class="min-w-[180px] text-sm font-sans">
                    <h3 class="m-0 mb-1.5 text-sm font-semibold text-slate-800 dark:text-slate-100">${equipement.nom}</h3>
                    <span class="inline-block px-1.5 py-0.5 rounded-lg text-[10px] font-medium mb-1.5" style="background-color: ${color}20; color: ${color}; border: 1px solid ${color}40;">
                        ${equipement.statut.charAt(0).toUpperCase() + equipement.statut.slice(1)}
                    </span>
            `;

            if (equipement.type === 'capteur') {
                popupContent += `
                    <div class="text-base font-bold my-1 text-slate-800 dark:text-slate-100" style="color: ${color}">
                        ${equipement.valeur_actuelle} ${equipement.unite}
                    </div>
                `;
            } else {
                popupContent += `
                    <div class="text-base font-bold my-1 text-slate-800 dark:text-slate-100" style="color: ${color}">
                        ${equipement.statut === 'marche' ? 'En marche' : 'Arrêté'}
                    </div>
                `;
            }

            popupContent += `
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                        Type: ${equipement.type}<br>
                        ID: ${equipement.id}
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent);
            equipmentMarkers.push(marker);
        });
    }

    // Real-time updates
    function startRealtimeUpdates() {
        // Update every 5 seconds (same as dashboard)
        setInterval(fetchMapData, 5000);
    }

    // Fetch updated map data
    function fetchMapData() {
        fetch('api/get_map_data.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update equipment data
                    const hasEquipmentChanges = JSON.stringify(currentEquipments) !== JSON.stringify(data.equipements);
                    const hasZoneChanges = JSON.stringify(currentZone) !== JSON.stringify(data.zones[0] || null);

                    if (hasEquipmentChanges || hasZoneChanges) {
                        currentEquipments = data.equipements;
                        currentZone = data.zones[0] || null;

                        // Update display
                        updateFarmZone();
                        updateEquipmentMarkers();

                        // Update timestamp
                        lastUpdateTime = Date.now();
                        updateStatusDisplay();
                    }
                }
            })
            .catch(error => {
                console.error('Erreur de synchronisation:', error);
                updateStatusDisplay('Erreur de connexion');
            });
    }

    // Update status display
    function updateStatusDisplay(status = null) {
        const statusEl = document.getElementById('last-map-update');
        if (status) {
            statusEl.textContent = status;
            statusEl.className = 'text-xs text-red-500';
        } else {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
            statusEl.textContent = 'Mis à jour à ' + timeString;
            statusEl.className = 'text-xs text-slate-400';
        }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        updateStatusDisplay();
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (map) {
            map.invalidateSize();
        }
    });
</script>



