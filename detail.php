<?php
// Fichier: detail.php
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

// Helpers pour le statut
$statusColors = [
    'normal' => ['bg' => '#dcfce7', 'text' => '#16a34a', 'dot' => '#16a34a', 'label' => 'Normal'],
    'alerte' => ['bg' => '#fef3c7', 'text' => '#d97706', 'dot' => '#f59e0b', 'label' => 'Alerte'],
    'critique' => ['bg' => '#fee2e2', 'text' => '#ef4444', 'dot' => '#ef4444', 'label' => 'Critique']
];

$couleurStatus = $statusColors[$capteur['statut']] ?? $statusColors['normal'];

// Gestion de la couleur principale en fonction de la table DB
$mainColorMap = [
    'green' => '#22c55e',
    'orange' => '#f59e0b',
    'red' => '#ef4444',
    'blue' => '#3b82f6',
    'grey' => '#64748b'
];
$mainBgColor = $mainColorMap[$capteur['couleur']] ?? '#22c55e';

// Simulation simple de la jauge (calcul du pourcentage)
$percent = 50;
if ($capteur['seuil_min'] !== null && $capteur['seuil_max'] !== null && $capteur['seuil_max'] > $capteur['seuil_min']) {
    $percent = (($capteur['valeur_actuelle'] - $capteur['seuil_min']) / ($capteur['seuil_max'] - $capteur['seuil_min'])) * 100;
    $percent = max(0, min(100, $percent)); // Clamp entre 0 et 100
} elseif ($capteur['unite'] == '%') {
    $percent = $capteur['valeur_actuelle'];
}
?>
<!doctype html>
<html lang="fr" class="antialiased">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <title>FarmsConnect - Détail <?= htmlspecialchars($capteur['nom']) ?></title>
    <meta name="theme-color" content="#ffffff" />
    <link rel="manifest" href="manifest.json" />
    <link rel="apple-touch-icon" href="assets/icon.svg" />
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="css/app.css" />

    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              green: { 500: "#22c55e", 600: "#16a34a" },
              slate: { 50: "#f8fafc", 100: "#f1f5f9", 200: "#e2e8f0", 400: "#94a3b8", 500: "#64748b", 800: "#1e293b" },
              brand: { green: "#16a34a" },
            },
            fontFamily: { sans: ["Nunito", "sans-serif"] },
          },
        },
      };
    </script>
</head>
<body class="flex flex-col h-[100dvh] overflow-hidden bg-[#fafbfd]">
    <main class="flex-1 overflow-y-auto px-4 pb-24 pt-safe">
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
            <span class="pill" style="padding: 2px 6px; font-size: 10px; background-color: <?= $couleurStatus['bg'] ?>; color: <?= $couleurStatus['text'] ?>;">
                <span class="status-dot" style="background-color: <?= $couleurStatus['dot'] ?>"></span>
                <?= $couleurStatus['label'] ?>
            </span>
          </div>
        </div>
        <a href="reglages.php" class="w-10 h-10 bg-white card-border rounded-xl flex items-center justify-center text-slate-400">
          <i data-lucide="settings" class="w-5 h-5"></i>
        </a>
      </header>

      <!-- MAIN SENSOR BLOCK -->
      <div class="rounded-[24px] p-5 text-white mb-6 relative overflow-hidden shadow-sm" style="background-color: <?= $mainBgColor ?>">
        <div class="flex items-center gap-3 mb-6 relative z-10">
          <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
            <i data-lucide="<?= $capteur['icone'] ?>" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-sm opacity-90"><?= htmlspecialchars($capteur['nom']) ?></span>
        </div>

        <div class="flex items-baseline gap-1 mb-6 relative z-10">
          <span class="text-5xl font-black tracking-tight"><?= htmlspecialchars($capteur['valeur_actuelle']) ?></span>
          <span class="text-xl font-bold opacity-80"><?= htmlspecialchars($capteur['unite']) ?></span>
        </div>

        <div class="w-full h-1.5 bg-white/30 rounded-full mb-2 relative z-10">
          <div class="h-1.5 bg-white rounded-full" style="width: <?= $percent ?>%"></div>
        </div>

        <div class="flex justify-between text-[10px] font-bold opacity-80 relative z-10">
          <span>Min: <?= $capteur['seuil_min'] ?? '-' ?><?= $capteur['unite'] ?></span>
          <span>Max: <?= $capteur['seuil_max'] ?? '-' ?><?= $capteur['unite'] ?></span>
        </div>
      </div>

      <!-- CHART (SVG) -->
      <div class="card-border p-5 mb-5 rounded-2xl">
        <h3 class="text-xs font-black text-[#0f2b46] mb-4 text-left">Historique 7 jours</h3>
        <div class="h-28 w-full mt-4 flex flex-col justify-end relative">
          <svg viewBox="0 0 100 50" class="w-full h-full overflow-visible" preserveAspectRatio="none">
            <defs>
              <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="<?= $mainBgColor ?>" stop-opacity="0.3" />
                <stop offset="100%" stop-color="<?= $mainBgColor ?>" stop-opacity="0" />
              </linearGradient>
            </defs>
            <polygon points="10,25 25,18 40,15 55,18 70,10 85,15 100,15 100,50 10,50" fill="url(#chartGradient)" />
            <polyline points="10,25 25,18 40,15 55,18 70,10 85,15 100,15" fill="none" stroke="<?= $mainBgColor ?>" stroke-width="1.5" />
            <circle cx="10" cy="25" r="2" fill="white" stroke="<?= $mainBgColor ?>" stroke-width="1.5" />
            <circle cx="25" cy="18" r="2" fill="white" stroke="<?= $mainBgColor ?>" stroke-width="1.5" />
            <circle cx="40" cy="15" r="2" fill="white" stroke="<?= $mainBgColor ?>" stroke-width="1.5" />
            <circle cx="55" cy="18" r="2" fill="white" stroke="<?= $mainBgColor ?>" stroke-width="1.5" />
            <circle cx="70" cy="10" r="2" fill="white" stroke="<?= $mainBgColor ?>" stroke-width="1.5" />
            <circle cx="85" cy="15" r="2" fill="white" stroke="<?= $mainBgColor ?>" stroke-width="1.5" />
            <circle cx="100" cy="15" r="2" fill="white" stroke="<?= $mainBgColor ?>" stroke-width="1.5" />
          </svg>

          <div class="flex justify-between text-[9px] font-bold text-slate-400 mt-2 px-1">
            <span>Lun</span><span>Mar</span><span>Mer</span><span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
          </div>
        </div>
      </div>

      <!-- SEUILS -->
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

    <!-- BOTTOM NAVIGATION -->
    <nav class="absolute bottom-0 w-full bottom-nav pt-3 pb-safe z-50">
      <ul class="flex justify-around items-center px-2">
        <li>
          <a href="index.php" class="nav-item w-16">
            <div class="p-1.5 flex items-center justify-center"><i data-lucide="home" class="w-5 h-5"></i></div>
            <span>Accueil</span>
          </a>
        </li>
        <li>
          <a href="alertes.php" class="nav-item w-16">
            <div class="p-1.5 flex items-center justify-center"><i data-lucide="bell" class="w-5 h-5"></i></div>
            <span>Alertes</span>
          </a>
        </li>
        <li>
          <a href="equipements.php" class="nav-item active w-16">
            <div class="bg-brand-green-light rounded-xl p-1.5 flex items-center justify-center"><i data-lucide="tractor" class="w-5 h-5 text-green-500"></i></div>
            <span>Équipements</span>
          </a>
        </li>
        <li>
          <a href="reglages.php" class="nav-item w-16">
            <div class="p-1.5 flex items-center justify-center"><i data-lucide="settings" class="w-5 h-5"></i></div>
            <span>Réglages</span>
          </a>
        </li>
      </ul>
    </nav>
    <script>lucide.createIcons();</script>
</body>
</html>
