<?php
/*
 * Fichier : reglages.php
 * Interface d'administration pour la configuration des seuils opérationnels des capteurs.
 * Interagit avec la base de données pour calibrer le déclenchement des alertes automatiques.
 */
require_once 'config/db.php';
require_once 'includes/auth.php';

forcer_connexion();

// Informations Utilisateur
$user_nom   = $_SESSION['user_nom']   ?? 'Utilisateur';
$user_email = $_SESSION['user_email'] ?? 'administrateur@farmsconnect.com';
$user_role  = 'Super Administrateur';

// Chargement des paramètres système
$stmtS = $pdo->query("SELECT valeur FROM parametres_systeme WHERE cle = 'vitesse_simulation'");
$vitesse_actuelle = $stmtS->fetchColumn() ?: '1.0';

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
<?php
/* Inclusion des fonctions utilitaires partagées d'affichage HTML */
require_once 'includes/functions.php';

$page_title = 'FarmsConnect - Réglages';
$active_nav = 'reglages';

require 'includes/header.php';
?>
      <!-- HEADER -->
      <header class="flex justify-between items-center mt-4 mb-6">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-600">
            <i data-lucide="settings" class="w-6 h-6"></i>
          </div>
          <div><h1 class="text-[1.3rem] font-black text-brand-dark dark:text-white leading-tight">Réglages</h1></div>
        </div>
        <form action="logout.php" method="POST">
            <button type="submit" class="w-10 h-10 bg-white border border-slate-200 rounded-full flex items-center justify-center text-red-500 shadow-sm" title="Déconnexion">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </button>
        </form>
      </header>

      <!-- SECTION MON PROFIL -->
      <div class="card-border p-5 mb-8 bg-gradient-to-br from-[#0f2b46] to-[#1a3b5c] text-white relative overflow-hidden">
        <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-white/5 rounded-full blur-2xl"></div>
        <div class="flex items-center gap-5 relative z-10">
          <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center border border-white/20 shadow-xl">
             <i data-lucide="user" class="w-8 h-8 text-white"></i>
          </div>
          <div class="flex-1">
            <h2 class="text-lg font-black leading-tight"><?= htmlspecialchars($user_nom) ?></h2>
            <p class="text-[11px] text-white/70 font-bold"><?= htmlspecialchars($user_email) ?></p>
            <div class="flex items-center gap-2 mt-2">
                <span class="px-2 py-0.5 bg-green-500/20 text-green-400 text-[9px] font-black uppercase tracking-wider rounded-md border border-green-500/30">En ligne</span>
                <span class="px-2 py-0.5 bg-white/10 text-white/80 text-[9px] font-black uppercase tracking-wider rounded-md border border-white/20"><?= $user_role ?></span>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($message_succes)): ?>
      <div class="bg-green-50 text-green-600 font-bold p-3 rounded-xl mb-4 text-sm flex items-center gap-2 border border-green-200">
          <i data-lucide="check" class="w-4 h-4"></i> <?= $message_succes ?>
      </div>
      <?php endif; ?>

      <!-- PARAMÉTRES DES NOTIFICATIONS (NOTIFICATIONS) -->
      <div class="card-border p-4 mb-6">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-6 h-6 bg-blue-100 rounded-md flex items-center justify-center text-blue-500"><i data-lucide="bell" class="w-3.5 h-3.5"></i></div>
          <h3 class="font-bold text-brand-dark dark:text-white text-sm">Notifications</h3>
        </div>

        <div class="flex justify-between items-center mb-4">
          <div>
            <h4 class="font-bold text-sm text-brand-dark dark:text-white">Activer les alertes</h4>
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
              <h3 class="font-bold text-brand-dark dark:text-white text-sm">Température serre</h3>
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
              <h3 class="font-bold text-brand-dark dark:text-white text-sm">Humidité sol</h3>
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
              <h3 class="font-bold text-brand-dark dark:text-white text-sm">Niveau eau</h3>
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
              <h3 class="font-bold text-brand-dark dark:text-white text-sm">Batterie capteur</h3>
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

      <!-- SECTION SYSTÈME -->
      <div class="card-border p-4 mb-6">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-6 h-6 bg-purple-100 rounded-md flex items-center justify-center text-purple-500"><i data-lucide="zap" class="w-3.5 h-3.5"></i></div>
          <h3 class="font-bold text-brand-dark dark:text-white text-sm">Paramètres Système</h3>
        </div>
        <div class="mb-4">
            <div class="flex justify-between mb-2">
                <label class="text-xs font-black text-slate-500 uppercase">Vitesse de Simulation</label>
                <span id="vitesse-val" class="text-xs font-black text-purple-600"><?= $vitesse_actuelle ?>x</span>
            </div>
            <input type="range" id="sim-speed" min="0.1" max="5.0" step="0.1" value="<?= $vitesse_actuelle ?>" 
                   class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-purple-600"
                   oninput="updateSpeedVal(this.value)" onchange="saveSpeed(this.value)">
            <p class="text-[10px] text-slate-400 font-bold mt-2 italic">Ajuste la vitesse à laquelle les capteurs fluctuent en temps réel.</p>
        </div>
      </div>

      <!-- SECTION PRÉFÉRENCES -->
      <div class="card-border p-4 mb-6">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-6 h-6 bg-orange-100 rounded-md flex items-center justify-center text-orange-500"><i data-lucide="layout" class="w-3.5 h-3.5"></i></div>
          <h3 class="font-bold text-[#0f2b46] text-sm dark:text-white">Préférences d'Affichage</h3>
        </div>
        <div class="flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <span class="text-[12px] font-bold text-slate-600 dark:text-slate-400">Langue de l'interface</span>
                <select id="lang-select" onchange="localStorage.setItem('lang', this.value)" class="text-[11px] font-black text-slate-400 bg-transparent outline-none cursor-pointer">
                    <option value="fr">Français (FR)</option>
                    <option value="en">English (EN)</option>
                </select>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-[12px] font-bold text-slate-600 dark:text-slate-400">Unités de mesure</span>
                <select id="unit-select" onchange="localStorage.setItem('units', this.value)" class="text-[11px] font-black text-slate-400 bg-transparent outline-none cursor-pointer">
                    <option value="metric">Métriques (°C, %)</option>
                    <option value="imperial">Impériales (°F)</option>
                </select>
            </div>
            <div class="flex justify-between items-center">
                <div>
                   <span class="text-[12px] font-bold text-slate-600 dark:text-slate-400">Thème Sombre</span>
                   <p class="text-[9px] text-slate-400 font-bold">Activer l'interface nocturne</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                   <input type="checkbox" id="dark-toggle" class="sr-only peer" onchange="toggleTheme(this.checked)" />
                   <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-dark"></div>
                </label>
            </div>
        </div>
      </div>

      <!-- SECTION SÉCURITÉ -->
      <div class="card-border p-4 mb-6">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-6 h-6 bg-green-100 rounded-md flex items-center justify-center text-green-500"><i data-lucide="shield" class="w-3.5 h-3.5"></i></div>
          <h3 class="font-bold text-[#0f2b46] text-sm dark:text-white">Sécurité</h3>
        </div>
        
        <div id="password-form-container">
            <button onclick="showPasswordForm()" class="w-full p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl text-[11px] font-bold text-slate-600 dark:text-slate-400 flex items-center justify-between">
                Changer le mot de passe <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400"></i>
            </button>
        </div>
      </div>

      <!-- SECTION MAINTENANCE -->
      <div class="card-border p-4 mb-24 bg-red-50/20 border-red-100">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-6 h-6 bg-red-100 rounded-md flex items-center justify-center text-red-500"><i data-lucide="wrench" class="w-3.5 h-3.5"></i></div>
          <h3 class="font-bold text-red-700 text-sm">Maintenance</h3>
        </div>
        <div class="flex flex-col gap-3">
            <button onclick="runMaintenance('clear_alerts')" class="w-full p-4 bg-white border border-red-200 rounded-xl text-[12px] font-black text-red-600 hover:bg-red-50 transition-colors flex items-center justify-center gap-2 shadow-sm">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Supprimer toutes les alertes
            </button>
            <button onclick="runMaintenance('clear_history')" class="w-full p-4 bg-white border border-red-200 rounded-xl text-[12px] font-black text-red-600 hover:bg-red-50 transition-colors flex items-center justify-center gap-2 shadow-sm">
                <i data-lucide="history" class="w-4 h-4"></i> Vider l'historique des données
            </button>
        </div>
      </div>

      <script>
      // Initialiser les sélecteurs
      if (localStorage.getItem('theme') === 'dark') {
          document.getElementById('dark-toggle').checked = true;
      }
      if (localStorage.getItem('lang')) {
          document.getElementById('lang-select').value = localStorage.getItem('lang');
      }
      if (localStorage.getItem('units')) {
          document.getElementById('unit-select').value = localStorage.getItem('units');
      }

      function toggleTheme(isDark) {
          if (isDark) {
              document.documentElement.classList.add('dark');
              localStorage.setItem('theme', 'dark');
          } else {
              document.documentElement.classList.remove('dark');
              localStorage.setItem('theme', 'light');
          }
      }

      function showPasswordForm() {
          document.getElementById('password-form-container').innerHTML = `
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700">
                <form id="pass-form" class="space-y-3">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Mot de passe actuel</label>
                        <input type="password" id="current-pass" class="w-full p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs" required />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nouveau mot de passe</label>
                        <input type="password" id="new-pass" class="w-full p-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-xs" required />
                    </div>
                    <button type="submit" class="w-full p-2 bg-brand-dark text-white rounded-lg text-xs font-black">Mettre à jour</button>
                    <button type="button" onclick="location.reload()" class="w-full p-2 text-slate-400 text-[10px] font-bold">Annuler</button>
                </form>
            </div>
          `;
          
          document.getElementById('pass-form').onsubmit = function(e) {
              e.preventDefault();
              const current = document.getElementById('current-pass').value;
              const next = document.getElementById('new-pass').value;
              
              fetch('api/change_password.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: `current_password=${encodeURIComponent(current)}&new_password=${encodeURIComponent(next)}`
              })
              .then(res => res.json())
              .then(data => {
                  alert(data.message);
                  if(data.success) location.reload();
              });
          };
      }

      function updateSpeedVal(val) {
          document.getElementById('vitesse-val').innerText = val + 'x';
      }

      function saveSpeed(val) {
          fetch('api/save_system_settings.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'vitesse=' + val
          });
      }

      function runMaintenance(action) {
          const labels = {
              'clear_alerts': 'toutes les alertes',
              'clear_history': 'l\'historique des données'
          };
          if(!confirm('Es-tu sûr de vouloir supprimer ' + labels[action] + ' ?')) return;
          
          fetch('api/manage_maintenance.php?action=' + action)
          .then(res => res.json())
          .then(data => {
              if(data.success) {
                  alert(data.message);
                  if(action === 'clear_alerts') location.reload();
              }
          });
      }
      </script>
    </main>

<?php
require 'includes/nav.php';
require 'includes/footer.php';
?>
