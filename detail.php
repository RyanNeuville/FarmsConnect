<?php
/*
 * Fichier : detail.php
 * Interface modulaire de monitoring détaillé pour un capteur d'équipement spécifique.
 * Gère le rendu de l'historique graphique et l'affichage des limites de tolérance (seuils).
 */
require_once 'config/db.php';
require_once 'includes/auth.php';

forcer_connexion();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM equipements WHERE id = ? AND type = 'capteur'");
$stmt->execute([$id]);
$capteur = $stmt->fetch();

if (!$capteur) {
    header('Location: index.php');
    exit;
}

/*
 * Structure de mappage colorimétrique associant un état métier de la sonde
 * (normal, alerte, critique) avec la palette de styles graphiques UI du badge.
 */
$statusColors = [
    'normal' => ['bg' => '#dcfce7', 'text' => '#16a34a', 'dot' => '#16a34a', 'label' => 'Normal'],
    'alerte' => ['bg' => '#fef3c7', 'text' => '#d97706', 'dot' => '#f59e0b', 'label' => 'Alerte'],
    'critique' => ['bg' => '#fee2e2', 'text' => '#ef4444', 'dot' => '#ef4444', 'label' => 'Critique']
];

$couleurStatus = $statusColors[$capteur['statut']] ?? $statusColors['normal'];

/* 
 * Définition du thème chromatique dominant de l'interface composant
 * aligné avec le code couleur natif du capteur défini dans la structure base de données.
 */
$mainColorMap = [
    'green' => '#22c55e',
    'orange' => '#f59e0b',
    'red' => '#ef4444',
    'blue' => '#3b82f6',
    'grey' => '#64748b'
];
$mainBgColor = $mainColorMap[$capteur['couleur']] ?? '#22c55e';

/*
 * Algorithme de jauge de progression visuelle :
 * Calcule dynamiquement le ratio en pourcentage relatif entre la tolérance plancher et plafond du capteur.
 */
$percent = 50;
if ($capteur['seuil_min'] !== null && $capteur['seuil_max'] !== null && $capteur['seuil_max'] > $capteur['seuil_min']) {
    $percent = (($capteur['valeur_actuelle'] - $capteur['seuil_min']) / ($capteur['seuil_max'] - $capteur['seuil_min'])) * 100;
    $percent = max(0, min(100, $percent)); // Clamp entre 0 et 100
} elseif ($capteur['unite'] == '%') {
    $percent = $capteur['valeur_actuelle'];
}
?>
<?php
/* Intégration du moteur partagé de génération d'UI */
require_once 'includes/functions.php';

$page_title = 'FarmsConnect - Détail ' . $capteur['nom'];
$active_nav = 'equipements';

require 'includes/header.php';
?>
      <!-- HEADER -->
      <header class="flex justify-between items-center mt-4 mb-6">
        <a href="javascript:history.back()" class="w-10 h-10 bg-white card-border rounded-xl flex items-center justify-center text-[#0f2b46]">
          <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="flex-1 ml-4 relative">
          <h1 class="text-[17px] font-black text-[#0f2b46] leading-tight flex items-center gap-2">
             <?= htmlspecialchars($capteur['nom']) ?>
          </h1>
          <div class="mt-0.5">
            <span id="status-badge" class="pill" style="padding: 2px 6px; font-size: 10px; background-color: <?= $couleurStatus['bg'] ?>; color: <?= $couleurStatus['text'] ?>;">
                <span id="status-dot" class="status-dot" style="background-color: <?= $couleurStatus['dot'] ?>"></span>
                <span id="status-label"><?= $couleurStatus['label'] ?></span>
            </span>
          </div>
        </div>
        <a href="reglages.php" class="w-10 h-10 bg-white card-border rounded-xl flex items-center justify-center text-slate-400">
          <i data-lucide="settings" class="w-5 h-5"></i>
        </a>
      </header>

      <!-- BLOC D'INFORMATION PRINCIPAL DU CAPTEUR EXAMINÉ (MAIN SENSOR BLOCK) -->
      <div class="rounded-[24px] p-5 text-white mb-6 relative overflow-hidden shadow-sm" style="background-color: <?= $mainBgColor ?>">
        <div class="flex items-center gap-3 mb-6 relative z-10">
          <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
            <i data-lucide="<?= $capteur['icone'] ?>" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-sm opacity-90"><?= htmlspecialchars($capteur['nom']) ?></span>
        </div>

        <div class="flex items-baseline gap-1 mb-6 relative z-10">
          <span id="current-value" class="text-5xl font-black tracking-tight"><?= htmlspecialchars($capteur['valeur_actuelle']) ?></span>
          <span class="text-xl font-bold opacity-80"><?= htmlspecialchars($capteur['unite']) ?></span>
        </div>

        <div class="w-full h-1.5 bg-white/30 rounded-full mb-2 relative z-10">
          <div id="progress-fill" class="h-1.5 bg-white rounded-full" style="width: <?= $percent ?>%"></div>
        </div>

        <div class="flex justify-between text-[10px] font-bold opacity-80 relative z-10">
          <span>Min: <?= $capteur['seuil_min'] ?? '-' ?><?= $capteur['unite'] ?></span>
          <span>Max: <?= $capteur['seuil_max'] ?? '-' ?><?= $capteur['unite'] ?></span>
        </div>
      </div>

      <!-- VUE GRAPHIQUE HISTORIQUE INTÉGRÉE (CHART SVG 7 JOURS) -->
      <div class="card-border p-5 mb-5 rounded-2xl">
        <h3 class="text-xs font-black text-[#0f2b46] mb-4 text-left">Historique réel</h3>
        <div class="h-28 w-full mt-4 flex flex-col justify-end relative">
          <svg id="sensor-chart" viewBox="0 0 100 50" class="w-full h-full overflow-visible" preserveAspectRatio="none">
            <defs>
              <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="<?= $mainBgColor ?>" stop-opacity="0.3" />
                <stop offset="100%" stop-color="<?= $mainBgColor ?>" stop-opacity="0" />
              </linearGradient>
            </defs>
            <polygon id="chart-polygon" points="" fill="url(#chartGradient)" />
            <polyline id="chart-line" points="" fill="none" stroke="<?= $mainBgColor ?>" stroke-width="1.5" />
            <!-- Points dynamiques -->
            <g id="chart-points"></g>
          </svg>

          <div class="flex justify-between text-[9px] font-bold text-slate-400 mt-2 px-1">
            <span>Passé</span><span>Aujourd'hui</span>
          </div>
        </div>
      </div>

      <!-- AFFICHAGE DES CRITÈRES DE LIMITES OPÉRATIONNELLES (SEUILS) -->
      <?php if ($capteur['seuil_min'] !== null || $capteur['seuil_max'] !== null): ?>
      <div class="card-border p-5 rounded-2xl">
        <h3 class="text-xs font-black text-[#0f2b46] mb-4 text-left">Seuils d'alerte</h3>
        <div class="flex gap-4">
          <?php if ($capteur['seuil_min'] !== null): ?>
          <div class="flex-1 bg-blue-50/50 rounded-xl p-4 text-center">
            <span class="block text-[10px] font-bold text-blue-500 mb-1">Minimum</span>
            <span class="block text-2xl font-black text-blue-600"><?= htmlspecialchars($capteur['seuil_min']) ?><?= $capteur['unite'] ?></span>
          </div>
          <?php endif; ?>
          <?php if ($capteur['seuil_max'] !== null): ?>
          <div class="flex-1 bg-red-50/50 rounded-xl p-4 text-center">
            <span class="block text-[10px] font-bold text-red-500 mb-1">Maximum</span>
            <span class="block text-2xl font-black text-red-600"><?= htmlspecialchars($capteur['seuil_max']) ?><?= $capteur['unite'] ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </main>


<script>
const sensorId = <?= $id ?>;
const minBound = <?= $capteur['seuil_min'] ?? 0 ?>;
const maxBound = <?= $capteur['seuil_max'] ?? 100 ?>;
const mainColor = '<?= $mainBgColor ?>';

/**
 * Mise à jour dynamique de la page de détails et du graphique SVG.
 */
function refreshSensorDetail() {
    fetch(`api/get_sensor_history.php?id=${sensorId}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const capteur = data.capteur;
                const historique = data.historique;

                // 1. Mise à jour de la valeur textuelle
                const valElem = document.getElementById('current-value');
                if (valElem.innerText != capteur.valeur_actuelle) {
                    valElem.innerText = capteur.valeur_actuelle;
                    valElem.classList.remove('animate-pulse-quick');
                    void valElem.offsetWidth; 
                    valElem.classList.add('animate-pulse-quick');
                }

                // 2. Mise à jour de la barre de progression
                let percent = 50;
                if (capteur.seuil_min !== null && capteur.seuil_max !== null) {
                    percent = ((capteur.valeur_actuelle - capteur.seuil_min) / (capteur.seuil_max - capteur.seuil_min)) * 100;
                    percent = Math.max(0, Math.min(100, percent));
                } else if (capteur.unite === '%') {
                    percent = capteur.valeur_actuelle;
                }
                document.getElementById('progress-fill').style.width = percent + '%';

                // 3. Mise à jour du graphique SVG
                if (historique && historique.length > 0) {
                    updateChart(historique);
                }
            }
        });
}

/**
 * Calcule les coordonnées SVG et met à jour les chemins et points.
 */
function updateChart(data) {
    const polyline = document.getElementById('chart-line');
    const polygon = document.getElementById('chart-polygon');
    const pointsGroup = document.getElementById('chart-points');
    
    // Déterminer les bornes pour le mapping Y (plus on est haut, plus la valeur est élevée)
    // Mais en SVG, Y=0 est en haut. Donc on inverse.
    const h = 50; // Hauteur SVG
    const w = 100; // Largeur SVG
    
    // On trouve le min/max des données pour l'échelle ou on utilise les seuils
    const vals = data.map(d => parseFloat(d.valeur));
    const dMin = Math.min(...vals, minBound) - 2;
    const dMax = Math.max(...vals, maxBound) + 2;
    const range = dMax - dMin;

    let pointsStr = '';
    let circlesHtml = '';
    
    data.forEach((d, i) => {
        const x = (i / (data.length - 1)) * w;
        const y = h - ((parseFloat(d.valeur) - dMin) / range) * h;
        pointsStr += `${x},${y} `;
        circlesHtml += `<circle cx="${x}" cy="${y}" r="2" fill="white" stroke="${mainColor}" stroke-width="1.5" />`;
    });

    polyline.setAttribute('points', pointsStr);
    polygon.setAttribute('points', `${pointsStr} 100,50 0,50`);
    pointsGroup.innerHTML = circlesHtml;
}

// Premier lancement
refreshSensorDetail();
// Polling toutes les 3 secondes
setInterval(refreshSensorDetail, 3000);
</script>

<?php
require 'includes/nav.php';
require 'includes/footer.php';
?>
