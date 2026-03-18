<?php
// Fichier: reglages.php
require_once 'config/db.php';
require_once 'includes/auth.php';

forcer_connexion();

$message_succes = '';

// Si soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $min_1 = (float)$_POST['min_1'];
    $max_1 = (float)$_POST['max_1'];
    $min_2 = (float)$_POST['min_2'];
    $max_2 = (float)$_POST['max_2'];
    $min_3 = (float)$_POST['min_3'];
    $min_4 = (float)$_POST['min_4'];

    // Mise à jour temp (Serre 1)
    $pdo->prepare("UPDATE equipements SET seuil_min = ?, seuil_max = ? WHERE id = 1")->execute([$min_1, $max_1]);
    // Humidite
    $pdo->prepare("UPDATE equipements SET seuil_min = ?, seuil_max = ? WHERE id = 2")->execute([$min_2, $max_2]);
    // Reservoir (Max 100 on touch pas pour l'UI actuel)
    $pdo->prepare("UPDATE equipements SET seuil_min = ? WHERE id = 3")->execute([$min_3]);
    // Batterie
    $pdo->prepare("UPDATE equipements SET seuil_min = ? WHERE id = 4")->execute([$min_4]);

    $message_succes = 'Réglages enregistrés avec succès.';
}

// Récupération des seuils existants
$stmt = $pdo->query("SELECT id, seuil_min, seuil_max FROM equipements WHERE type = 'capteur'");
$capteursDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
$seuils = [];
foreach ($capteursDb as $c) {
    $seuils[$c['id']] = $c;
}
?>
<!doctype html>
<html lang="fr" class="antialiased">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <title>FarmsConnect - Réglages</title>
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
            },
            fontFamily: { sans: ["Nunito", "sans-serif"] },
          },
        },
      };
    </script>
    <style>
      input[type="number"] {
        border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px;
        font-weight: 700; color: #0f2b46; outline: none; width: 100%;
        -moz-appearance: textfield; /* Remove arrows in Firefox */
      }
      input[type="number"]:focus {
        border-color: #22c55e; box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
      }
      input[type=number]::-webkit-inner-spin-button, 
      input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; margin: 0; 
      }
    </style>
</head>
<body class="flex flex-col h-[100dvh] overflow-hidden bg-[#fafbfd]">
    <main class="flex-1 overflow-y-auto px-4 pb-24 pt-safe">
      <!-- HEADER -->
      <header class="flex items-center gap-3 mt-4 mb-6">
        <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-600">
          <i data-lucide="settings" class="w-6 h-6"></i>
        </div>
        <div><h1 class="text-[1.3rem] font-black text-[#0f2b46] leading-tight">Réglages</h1></div>
      </header>

      <?php if (!empty($message_succes)): ?>
      <div class="bg-green-50 text-green-600 font-bold p-3 rounded-xl mb-4 text-sm flex items-center gap-2 border border-green-200">
          <i data-lucide="check" class="w-4 h-4"></i> <?= $message_succes ?>
      </div>
      <?php endif; ?>

      <!-- NOTIFICATIONS -->
      <div class="card-border p-4 mb-6">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-6 h-6 bg-blue-100 rounded-md flex items-center justify-center text-blue-500"><i data-lucide="bell" class="w-3.5 h-3.5"></i></div>
          <h3 class="font-bold text-[#0f2b46] text-sm">Notifications</h3>
        </div>

        <div class="flex justify-between items-center mb-4">
          <div>
            <h4 class="font-bold text-sm text-[#0f2b46]">Activer les alertes</h4>
            <p class="text-[11px] text-slate-400 font-bold">Recevoir les notifications push</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" value="" class="sr-only peer" checked />
            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0f2b46]"></div>
          </label>
        </div>
      </div>

      <form action="reglages.php" method="POST">
          <h2 class="text-xs font-black text-slate-500 mb-3 uppercase tracking-wider px-1">SEUILS D'ALERTE</h2>

          <!-- FORM: TEMPÉRATURE -->
          <div class="card-border p-4 mb-3">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 bg-orange-100 rounded-md flex items-center justify-center text-orange-500"><i data-lucide="thermometer" class="w-3.5 h-3.5"></i></div>
              <h3 class="font-bold text-[#0f2b46] text-sm">Température serre</h3>
            </div>
            <div class="flex gap-4">
              <div class="flex-1">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Min (°C)</label>
                <input type="number" step="0.1" name="min_1" value="<?= $seuils[1]['seuil_min'] ?>" required />
              </div>
              <div class="flex-1">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Max (°C)</label>
                <input type="number" step="0.1" name="max_1" value="<?= $seuils[1]['seuil_max'] ?>" required />
              </div>
            </div>
          </div>

          <!-- FORM: HUMIDITÉ -->
          <div class="card-border p-4 mb-3">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 bg-blue-100 rounded-md flex items-center justify-center text-blue-500"><i data-lucide="droplets" class="w-3.5 h-3.5"></i></div>
              <h3 class="font-bold text-[#0f2b46] text-sm">Humidité sol</h3>
            </div>
            <div class="flex gap-4">
              <div class="flex-1">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Min (%)</label>
                <input type="number" step="0.1" name="min_2" value="<?= $seuils[2]['seuil_min'] ?>" required />
              </div>
              <div class="flex-1">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Max (%)</label>
                <input type="number" step="0.1" name="max_2" value="<?= $seuils[2]['seuil_max'] ?>" required />
              </div>
            </div>
          </div>

          <!-- FORM: RÉSERVOIR -->
          <div class="card-border p-4 mb-3">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 bg-cyan-100 rounded-md flex items-center justify-center text-cyan-500"><i data-lucide="droplet" class="w-3.5 h-3.5"></i></div>
              <h3 class="font-bold text-[#0f2b46] text-sm">Niveau eau</h3>
            </div>
            <div class="flex gap-4">
              <div class="flex-1">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Min (%) avant alerte vide</label>
                <input type="number" step="0.1" name="min_3" value="<?= $seuils[3]['seuil_min'] ?>" required />
              </div>
            </div>
          </div>

          <!-- FORM: BATTERIE -->
          <div class="card-border p-4 mb-6">
            <div class="flex items-center gap-2 mb-3">
              <div class="w-6 h-6 bg-green-100 rounded-md flex items-center justify-center text-green-500"><i data-lucide="battery-medium" class="w-3.5 h-3.5"></i></div>
              <h3 class="font-bold text-[#0f2b46] text-sm">Batterie capteur</h3>
            </div>
            <div class="flex gap-4">
              <div class="flex-1">
                <label class="block text-[10px] font-bold text-slate-500 mb-1">Min (%) avant alerte critique</label>
                <input type="number" step="0.1" name="min_4" value="<?= $seuils[4]['seuil_min'] ?>" required />
              </div>
            </div>
          </div>

          <!-- ACTION BUTTONS -->
          <button type="submit" class="btn-primary mb-3 flex items-center justify-center gap-2 w-full">
            <i data-lucide="save" class="w-4 h-4"></i> Enregistrer les réglages
          </button>
      </form>

      <form action="logout.php" method="POST">
          <button type="submit" class="btn-outline text-red-500 border-red-200 bg-red-50 flex items-center justify-center gap-2 w-full">
            <i data-lucide="log-out" class="w-4 h-4"></i> Déconnexion
          </button>
      </form>
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
          <a href="equipements.php" class="nav-item w-16">
            <div class="p-1.5 flex items-center justify-center"><i data-lucide="tractor" class="w-5 h-5"></i></div>
            <span>Équipements</span>
          </a>
        </li>
        <li>
          <a href="reglages.php" class="nav-item active w-16">
            <div class="bg-brand-green-light rounded-xl p-1.5 flex items-center justify-center"><i data-lucide="settings" class="w-5 h-5 text-green-500"></i></div>
            <span>Réglages</span>
          </a>
        </li>
      </ul>
    </nav>
    <script>lucide.createIcons();</script>
</body>
</html>
