<?php
/*
 * Fichier : index.php
 * Contrôleur principal et vue du tableau de bord interactif (Dashboard).
 * Agrége les données temps réel des équipements et l'état général du système.
 */
require_once 'config/db.php';
require_once 'includes/auth.php';

/* Vérification de la validité de session (blocage de l'accès public) */
forcer_connexion();

/* Extraction sécurisée de l'identité de session (par défaut sur Utilisateur si non trouvé) */
$user_nom = $_SESSION['user_nom'] ?? 'Utilisateur';

/*
 * Requête globale de récupération de l'ensemble de l'inventaire matériel
 * afin d'hydrater l'interface de contrôle sans requêtes itératives successives.
 */
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

/*
 * Comptage rapide du volume d'anomalies non purgées 
 * pour le badge de notification global.
 */
$stmtAlertes = $pdo->query("SELECT COUNT(*) as nb FROM alertes WHERE est_lu = 0");
$alertesCount = $stmtAlertes->fetch()['nb'];

/* Inclusion logique UI et encapsulation layout globale */
require_once 'includes/functions.php';

$page_title = 'FarmsConnect - Accueil';
$active_nav = 'accueil';

require 'includes/header.php';
?>

      <!-- HEADER -->
      <header class="flex justify-between items-start mt-4 mb-6">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-brand-green-light border border-brand-green rounded-[14px] flex items-center justify-center text-green-600">
            <img src="assets/icon.png" alt="FarmsConnect Logo" class="w-6 h-6" />
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

      <!-- RÉSUMÉ MÉTÉOROLOGIQUE (WEATHER WIDGET) -->
      <div class="flex items-center gap-1.5 text-sm font-bold text-slate-500 mb-4 px-1">
        <i data-lucide="sun" class="w-5 h-5 text-orange-400"></i>
        <span class="text-slate-800">22°C</span>
        <span class="font-semibold text-slate-400">Ensoleillé</span>
      </div>

      <!-- BANNIÈRE GLOBALE DE STATUT (STATUS BANNER) -->
      <div class="bg-brand-green-light border border-brand-green rounded-xl p-3 mb-6">
        <p class="font-bold text-green-600 text-sm">
          Bonjour <?= htmlspecialchars($user_nom) ?>, Tout va bien 🌾
        </p>
      </div>

      <!-- GRILLE DES CAPTEURS DE MESURE (SENSORS GRID) -->
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

      <!-- GRILLE DES ACTIONNEURS DE CONTRÔLE (ACTUATORS GRID) -->
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

      <!-- HISTORIQUE DES CHANGEMENTS D'ÉTAT (RECENT ACTIVITIES) -->
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

<?php
require 'includes/nav.php';
require 'includes/footer.php';
?>
