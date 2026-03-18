<?php
/*
 * Fichier : reglages.php
 * Interface d'administration pour la configuration des seuils opérationnels des capteurs.
 * Interagit avec la base de données pour calibrer le déclenchement des alertes automatiques.
 */
require_once 'config/db.php';
require_once 'includes/auth.php';

forcer_connexion();

$message_succes = '';

/* Interception et traitement du flux POST lors de la soumission du formulaire de configuration */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $min_1 = (float)$_POST['min_1'];
    $max_1 = (float)$_POST['max_1'];
    $min_2 = (float)$_POST['min_2'];
    $max_2 = (float)$_POST['max_2'];
    $min_3 = (float)$_POST['min_3'];
    $min_4 = (float)$_POST['min_4'];

    /* Application récursive des nouveaux paramètres de seuil pour la température (ID 1) */
    $pdo->prepare("UPDATE equipements SET seuil_min = ?, seuil_max = ? WHERE id = 1")->execute([$min_1, $max_1]);
    /* Application des paramètres de seuil pour le capteur d'humidité du sol (ID 2) */
    $pdo->prepare("UPDATE equipements SET seuil_min = ?, seuil_max = ? WHERE id = 2")->execute([$min_2, $max_2]);
    /* Seuil bas du réservoir d'eau (Le paramètre max est figé structurellement à 100%) (ID 3) */
    $pdo->prepare("UPDATE equipements SET seuil_min = ? WHERE id = 3")->execute([$min_3]);
    /* Seuil minimal critique de tension pour les batteries (ID 4) */
    $pdo->prepare("UPDATE equipements SET seuil_min = ? WHERE id = 4")->execute([$min_4]);

    $message_succes = 'Réglages enregistrés avec succès.';
}

/* Synchronisation initiale des valeurs actuelles conservées en base pour pré-remplir le formulaire */
$stmt = $pdo->query("SELECT id, seuil_min, seuil_max FROM equipements WHERE type = 'capteur'");
$capteursDb = $stmt->fetchAll(PDO::FETCH_ASSOC);
$seuils = [];
foreach ($capteursDb as $c) {
    $seuils[$c['id']] = $c;
}
?>
/* Inclusion des fonctions utilitaires partagées d'affichage HTML */
require_once 'includes/functions.php';

$page_title = 'FarmsConnect - Réglages';
$active_nav = 'reglages';

require 'includes/header.php';
?>
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

      <!-- PARAMÉTRES DES NOTIFICATIONS (NOTIFICATIONS) -->
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

          <!-- FORMULAIRE CIBLÉ : TEMPÉRATURE AMBIANTE -->
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

          <!-- FORMULAIRE CIBLÉ : HUMIDITÉ DU SOL -->
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

          <!-- FORMULAIRE CIBLÉ : NIVEAU HYDRIQUE -->
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

          <!-- FORMULAIRE CIBLÉ : AUTONOMIE ÉNERGÉTIQUE -->
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

          <!-- ZONES D'ACTIONS DE SAUVEGARDE ET DE CONNEXION (ACTION BUTTONS) -->
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

<?php
require 'includes/nav.php';
require 'includes/footer.php';
?>
