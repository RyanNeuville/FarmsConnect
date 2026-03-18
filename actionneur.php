<?php
// Fichier: actionneur.php
require_once 'config/db.php';
require_once 'includes/auth.php';

forcer_connexion();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM equipements WHERE id = ? AND type = 'actionneur'");
$stmt->execute([$id]);
$actionneur = $stmt->fetch();

if (!$actionneur) {
    header('Location: index.php');
    exit;
}

$estEnMarche = ($actionneur['statut'] === 'marche');

// Definition des etats
$bgColor = $estEnMarche ? '#22c55e' : '#64748b'; // Vert ou Gris
$txtStatut = $estEnMarche ? 'MARCHE' : 'ARRÊT';
$btnColor = $estEnMarche ? 'text-red-500 fill-red-500' : 'text-green-600 fill-green-600';
$btnTxtColor = $estEnMarche ? 'text-red-500' : 'text-green-600';
$btnLibelle = $estEnMarche ? 'Arrêter' : 'Démarrer';
$btnIcon = $estEnMarche ? 'square' : 'play';

$pillBg = $estEnMarche ? '#dcfce7' : '#f1f5f9';
$pillText = $estEnMarche ? '#16a34a' : '#64748b';
$pillDot = $estEnMarche ? '#16a34a' : '#94a3b8';
$pillLabel = $estEnMarche ? 'Marche' : 'Arrêté';
?>
<!doctype html>
<html lang="fr" class="antialiased">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <title>FarmsConnect - Actuateur</title>
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
              slate: { 50: "#f8fafc", 100: "#f1f5f9", 200: "#e2e8f0", 400: "#94a3b8", 500: "#7e8a98", 800: "#1e293b" },
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
            <?= htmlspecialchars($actionneur['nom']) ?>
          </h1>
          <div class="mt-0.5">
            <span class="pill" style="padding: 2px 6px; font-size: 10px; background-color: <?= $pillBg ?>; color: <?= $pillText ?>;">
              <span class="status-dot" style="background-color: <?= $pillDot ?>"></span>
              <?= $pillLabel ?>
            </span>
          </div>
        </div>
        <a href="reglages.php" class="w-10 h-10 bg-white card-border rounded-xl flex items-center justify-center text-slate-400">
          <i data-lucide="settings" class="w-5 h-5"></i>
        </a>
      </header>

      <!-- MAIN ACTUATOR BLOCK -->
      <div class="rounded-[24px] p-6 text-white mb-6 relative overflow-hidden shadow-sm flex flex-col items-center justify-center py-10 transition-colors duration-500" style="background-color: <?= $bgColor ?>">
        <div class="w-full flex items-center gap-3 mb-8">
          <div class="w-11 h-11 bg-white/20 rounded-[14px] flex items-center justify-center backdrop-blur-sm">
            <i data-lucide="<?= $actionneur['icone'] ?>" class="w-6 h-6"></i>
          </div>
          <span class="font-bold text-sm text-slate-100"><?= htmlspecialchars($actionneur['nom']) ?></span>
        </div>

        <div class="w-full text-center mb-8">
          <span class="text-4xl font-black tracking-widest text-white"><?= $txtStatut ?></span>
        </div>

        <form action="api/action.php" method="POST" class="w-full m-0 p-0">
            <input type="hidden" name="equipement_id" value="<?= $id ?>">
            <input type="hidden" name="action" value="<?= $estEnMarche ? '0' : '1' ?>">
            <button type="submit" class="w-full bg-white <?= $btnTxtColor ?> font-extrabold text-[15px] py-4 rounded-2xl shadow-sm active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                <i data-lucide="<?= $btnIcon ?>" class="w-4 h-4 <?= $btnColor ?>"></i>
                <?= $btnLibelle ?>
            </button>
        </form>
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
