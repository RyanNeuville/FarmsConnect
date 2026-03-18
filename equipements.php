<?php
// Fichier: equipements.php
require_once 'config/db.php';
require_once 'includes/auth.php';

forcer_connexion();

$stmt = $pdo->query("SELECT * FROM equipements ORDER BY id ASC");
$equipements = $stmt->fetchAll();
$nbTotal = count($equipements);

// Helpers pour l'UI
function getStatusHelpers($statut) {
    if ($statut === 'normal') return ['bg' => 'green', 'text' => 'Normal'];
    if ($statut === 'alerte') return ['bg' => 'orange', 'text' => 'Alerte'];
    if ($statut === 'critique') return ['bg' => 'red', 'text' => 'Critique'];
    if ($statut === 'arret') return ['bg' => 'grey', 'text' => 'Arrêté'];
    if ($statut === 'marche') return ['bg' => 'green', 'text' => 'Marche'];
    return ['bg' => 'grey', 'text' => 'Inconnu'];
}
?>
<!doctype html>
<html lang="fr" class="antialiased">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <title>FarmsConnect - Équipements</title>
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
              slate: { 50: "#f8fafc", 100: "#f1f5f9", 400: "#94a3b8", 500: "#64748b", 800: "#1e293b" },
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
      <header class="flex items-center gap-3 mt-4 mb-6">
        <div class="w-12 h-12 bg-brand-green-light rounded-2xl flex items-center justify-center text-green-600">
          <i data-lucide="tractor" class="w-6 h-6"></i>
        </div>
        <div>
          <h1 class="text-[1.3rem] font-black text-[#0f2b46] leading-tight">Équipements</h1>
          <p class="text-xs text-slate-400 font-bold"><?= $nbTotal ?> capteurs / actionneurs</p>
        </div>
      </header>

      <!-- Status Banner -->
      <div class="bg-brand-green-light rounded-xl p-3 mb-6 flex items-center gap-2">
        <i data-lucide="wifi" class="w-4 h-4 text-green-600"></i>
        <span class="font-bold text-green-600 text-[13px]">Connecté – données en temps réel</span>
      </div>

      <!-- LIST -->
      <div class="space-y-3">
        <?php foreach ($equipements as $eq): 
            $helpers = getStatusHelpers($eq['statut']);
            $link = $eq['type'] === 'capteur' ? 'detail.php?id='.$eq['id'] : 'actionneur.php?id='.$eq['id'];
            $iconBg = $eq['type'] === 'capteur' ? $eq['couleur'] : 'bg-slate-100 text-slate-500';
        ?>
        <a href="<?= $link ?>" class="card-border p-3 flex items-center justify-between block active:bg-slate-50 transition-colors">
          <div class="flex items-center gap-4">
            <div class="icon-box <?= $iconBg ?>">
              <i data-lucide="<?= $eq['icone'] ?>" class="w-5 h-5"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-800 mb-1"><?= htmlspecialchars($eq['nom']) ?></h3>
              <div class="flex items-center gap-2">
                <span class="pill <?= $helpers['bg'] ?>" style="padding: 2px 6px; font-size: 10px">
                    <span class="status-dot <?= $helpers['bg'] ?>"></span> <?= $helpers['text'] ?>
                </span>
                <?php if ($eq['type'] === 'capteur'): ?>
                    <span class="text-xs font-bold text-slate-500"><?= $eq['valeur_actuelle'] ?><?= $eq['unite'] ?></span>
                <?php else: ?>
                    <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                      <span class="w-1.5 h-1.5 rounded-full border <?= $eq['statut'] == 'marche' ? 'bg-green-500 border-green-500' : 'border-slate-300' ?> block"></span>
                      <?= $eq['statut'] == 'marche' ? 'Marche' : 'Arrêt' ?>
                    </span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300"></i>
        </a>
        <?php endforeach; ?>
      </div>
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
