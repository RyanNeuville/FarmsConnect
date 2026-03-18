<?php
// Fichier: index.php
require_once 'config/db.php';
require_once 'includes/auth.php';

// Obliger l'utilisateur à être connecté
forcer_connexion();

// Récupérer le nom de l'utilisateur
$user_nom = $_SESSION['user_nom'] ?? 'Utilisateur';

// Récupérer tous les équipements
$stmt = $pdo->query("SELECT * FROM equipements ORDER BY id ASC");
$equipements = $stmt->fetchAll();

$capteurs = [];
$actionneurs = [];
foreach ($equipements as $eq) {
    if ($eq['type'] === 'capteur') {
        $capteurs[$eq['id']] = $eq;
    } else {
        $actionneurs[$eq['id']] = $eq;
    }
}

// Récupérer le nombre d'alertes non lues
$stmtAlertes = $pdo->query("SELECT COUNT(*) as nb FROM alertes WHERE est_lu = 0");
$alertesCount = $stmtAlertes->fetch()['nb'];

// Fonction Helper pour afficher la flèche de tendance
function getTrendIcon($valeur, $type) {
    // Une simulation simple de tendance basée sur la valeur actuelle
    // Dans un vrai système, on comparerait avec l'historique
    if ($type === 'Serre 1' || $type === 'Batterie Nord') return '<i data-lucide="arrow-down" class="w-3 h-3 text-blue-500"></i>';
    if ($type === 'Humidité sol') return '<i data-lucide="arrow-up" class="w-3 h-3 text-red-500"></i>';
    return '<i data-lucide="arrow-down" class="w-3 h-3 text-blue-500"></i>';
}

// Fonction Helper pour le badge statut
function getStatusBadge($statut) {
    if ($statut === 'normal') {
        return '<span class="pill green"><span class="status-dot green"></span> Normal</span>';
    } elseif ($statut === 'alerte') {
        return '<span class="pill orange"><span class="status-dot orange" style="background-color:#f59e0b;"></span> Alerte</span>';
    } elseif ($statut === 'critique') {
        return '<span class="pill red" style="background-color:#fee2e2;color:#ef4444;"><span class="status-dot red" style="background-color:#ef4444;"></span> Critique</span>';
    } elseif ($statut === 'arret') {
        return '<span class="pill grey"><span class="status-dot grey"></span> Arrêté</span>';
    } elseif ($statut === 'marche') {
        return '<span class="pill green"><span class="status-dot green"></span> Marche</span>';
    }
    return '';
}

// Helper pour le formatage du bouton actionneur
function getActionButton($statut) {
    if ($statut === 'marche') {
        return '<button class="bg-green-100 text-green-700 font-black text-xs py-2.5 rounded-xl w-full flex items-center justify-center gap-1 shadow-sm border border-green-200"><span class="w-[6px] h-[6px] rounded-full bg-green-500 block"></span> MARCHE</button>';
    } else {
        return '<button class="bg-slate-200 text-slate-600 font-black text-xs py-2.5 rounded-xl w-full flex items-center justify-center gap-1 shadow-sm"><span class="w-[6px] h-[6px] rounded-full border border-slate-400 bg-transparent block"></span> ARRÊT</button>';
    }
}
?>
<!doctype html>
<html lang="fr" class="antialiased">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <title>FarmsConnect - Accueil</title>

    <meta name="theme-color" content="#ffffff" />
    <link rel="manifest" href="manifest.json" />
    <link rel="apple-touch-icon" href="assets/icon.svg" />

    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/app.css" />

    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              green: { 500: "#22c55e", 600: "#16a34a" },
              slate: { 50: "#f8fafc", 100: "#f1f5f9", 400: "#94a3b8", 800: "#1e293b" },
            },
            fontFamily: { sans: ["Nunito", "sans-serif"] },
          },
        },
      };
    </script>
  </head>
  <body class="flex flex-col h-[100dvh] overflow-hidden bg-[#fafbfd]">
    <!-- Scrollable Content -->
    <main class="flex-1 overflow-y-auto px-4 pb-24 pt-safe">
      <!-- HEADER -->
      <header class="flex justify-between items-start mt-4 mb-6">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-brand-green-light border border-brand-green rounded-[14px] flex items-center justify-center text-green-600">
            <img src="assets/icon.svg" alt="FarmsConnect Logo" class="w-6 h-6" />
          </div>
          <div>
            <h1 class="text-[1.1rem] font-black text-[#0f2b46] leading-tight">FarmsConnect</h1>
            <p class="text-xs text-slate-400 font-bold">Mis à jour à <?= date('H:i') ?></p>
          </div>
        </div>

        <div class="relative">
          <a href="alertes.php" class="w-10 h-10 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-600 shadow-sm block relative">
            <i data-lucide="bell" class="w-5 h-5"></i>
            <?php if ($alertesCount > 0): ?>
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-[18px] h-[18px] flex items-center justify-center rounded-full border-2 border-[#fafbfd]">
              <?= $alertesCount > 9 ? '9+' : $alertesCount ?>
            </span>
            <?php endif; ?>
          </a>
        </div>
      </header>

      <!-- Weather summary -->
      <div class="flex items-center gap-1.5 text-sm font-bold text-slate-500 mb-4 px-1">
        <i data-lucide="sun" class="w-5 h-5 text-orange-400"></i>
        <span class="text-slate-800">22°C</span>
        <span class="font-semibold text-slate-400">Ensoleillé</span>
      </div>

      <!-- Global Status Banner -->
      <div class="bg-brand-green-light border border-brand-green rounded-xl p-3 mb-6">
        <p class="font-bold text-green-600 text-sm">
          Bonjour <?= htmlspecialchars($user_nom) ?>, Tout va bien 🌾
        </p>
      </div>

      <!-- SENSORS GRID -->
      <div class="grid grid-cols-2 gap-3 mb-4">
        <?php foreach ($capteurs as $cap): ?>
        <div class="card-border p-3 flex flex-col justify-between">
          <div class="flex justify-between items-start mb-2">
            <div class="icon-box <?= $cap['couleur'] ?>">
              <i data-lucide="<?= $cap['icone'] ?>" class="w-5 h-5"></i>
            </div>
            <?= getStatusBadge($cap['statut']) ?>
          </div>
          <h3 class="text-xs font-bold text-slate-800 mb-1"><?= htmlspecialchars($cap['nom']) ?></h3>
          <div class="flex items-baseline gap-1">
            <span class="text-xl font-black text-black"><?= $cap['valeur_actuelle'] ?></span>
            <span class="text-xs font-bold text-slate-400"><?= $cap['unite'] ?></span>
            <?= getTrendIcon($cap['valeur_actuelle'], $cap['nom']) ?>
          </div>
          <div class="progress-track">
            <?php 
                $percent = $cap['valeur_actuelle']; 
                if ($cap['nom'] == 'Serre 1') $percent = min(100, ($cap['valeur_actuelle']/40)*100); 
            ?>
            <div class="progress-fill <?= $cap['couleur'] ?>" style="width: <?= $percent ?>%"></div>
          </div>
          <a href="detail.php?id=<?= $cap['id'] ?>" class="text-[10px] text-slate-400 font-bold text-center mt-3 block flex items-center justify-center gap-1">
            Détails <i data-lucide="chevron-right" class="w-3 h-3"></i>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- ACTUATORS GRID -->
      <div class="grid grid-cols-2 gap-3 mb-8">
        <?php foreach ($actionneurs as $act): ?>
        <div class="card-border p-3">
          <div class="flex justify-between items-start mb-2">
            <div class="icon-box <?= $act['statut'] == 'marche' ? 'green' : 'bg-slate-100 text-slate-500' ?>">
              <i data-lucide="<?= $act['icone'] ?>" class="w-5 h-5"></i>
            </div>
            <?= getStatusBadge($act['statut']) ?>
          </div>
          <h3 class="text-xs font-bold text-slate-800 mb-3"><?= htmlspecialchars($act['nom']) ?></h3>
          <?= getActionButton($act['statut']) ?>
          <a href="actionneur.php?id=<?= $act['id'] ?>" class="text-[10px] text-slate-400 font-bold text-center mt-3 block flex items-center justify-center gap-1">
            Détails <i data-lucide="chevron-right" class="w-3 h-3"></i>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- RECENT ACTIVITIES (Statics for now, could be dynamic) -->
      <h2 class="text-xs font-black text-slate-500 mb-3 uppercase tracking-wider px-1">Activités récentes</h2>
      <div class="card-border mb-4">
        <div class="flex justify-between items-center p-4 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <i data-lucide="droplet" class="w-5 h-5 text-blue-500 fill-blue-500/20"></i>
            <p class="text-[13px] font-bold text-slate-700">Pompe arrosage activée</p>
          </div>
          <div class="flex items-center gap-1 text-[11px] font-bold text-slate-400">
            <i data-lucide="clock" class="w-3 h-3"></i> 08:42
          </div>
        </div>
        <div class="flex justify-between items-center p-4 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <i data-lucide="thermometer" class="w-5 h-5 text-red-500"></i>
            <p class="text-[13px] font-bold text-slate-700">Température serre : 24.2°C</p>
          </div>
          <div class="flex items-center gap-1 text-[11px] font-bold text-slate-400">
            <i data-lucide="clock" class="w-3 h-3"></i> 08:30
          </div>
        </div>
      </div>
    </main>

    <!-- BOTTOM NAVIGATION -->
    <nav class="absolute bottom-0 w-full bottom-nav pt-3 pb-safe z-50">
      <ul class="flex justify-around items-center px-2">
        <li>
          <a href="index.php" class="nav-item active w-16">
            <div class="bg-brand-green-light rounded-xl p-1.5 flex items-center justify-center">
              <i data-lucide="home" class="w-5 h-5 text-green-500"></i>
            </div>
            <span>Accueil</span>
          </a>
        </li>
        <li>
          <a href="alertes.php" class="nav-item w-16">
            <div class="p-1.5 flex items-center justify-center">
              <i data-lucide="bell" class="w-5 h-5"></i>
            </div>
            <span>Alertes</span>
          </a>
        </li>
        <li>
          <a href="equipements.php" class="nav-item w-16">
            <div class="p-1.5 flex items-center justify-center">
              <i data-lucide="tractor" class="w-5 h-5"></i>
            </div>
            <span>Équipements</span>
          </a>
        </li>
        <li>
          <a href="reglages.php" class="nav-item w-16">
            <div class="p-1.5 flex items-center justify-center">
              <i data-lucide="settings" class="w-5 h-5"></i>
            </div>
            <span>Réglages</span>
          </a>
        </li>
      </ul>
    </nav>

    <script>
      lucide.createIcons();
    </script>
  </body>
</html>
