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
            <p class="text-xs text-slate-400 font-bold" id="last-update-time">Mis à jour à <?= date('H:i') ?></p>
          </div>
        </div>

        <div class="relative">
          <a href="alertes.php" class="w-10 h-10 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-600 shadow-sm block relative">
            <i data-lucide="bell" class="w-5 h-5"></i>
            <span id="alert-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-[18px] h-[18px] flex items-center justify-center rounded-full border-2 border-[#fafbfd] <?= ($alertesCount > 0) ? '' : 'hidden' ?>">
              <?= $alertesCount > 9 ? '9+' : $alertesCount ?>
            </span>
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
        <div class="card-border p-3 flex flex-col justify-between sensor-card" data-id="<?= $cap['id'] ?>">
          <div class="flex justify-between items-start mb-2">
            <div class="icon-box <?= $cap['couleur'] ?> sensor-icon">
              <i data-lucide="<?= $cap['icone'] ?>" class="w-5 h-5"></i>
            </div>
            <div class="sensor-status"><?= getStatusBadge($cap['statut']) ?></div>
          </div>
          <h3 class="text-xs font-bold text-slate-800 mb-1"><?= htmlspecialchars($cap['nom']) ?></h3>
          <div class="flex items-baseline gap-1">
            <span class="text-xl font-black text-black sensor-value"><?= $cap['valeur_actuelle'] ?></span>
            <span class="text-xs font-bold text-slate-400"><?= $cap['unite'] ?></span>
            <div class="sensor-trend"><?= getTrendIcon($cap['valeur_actuelle'], $cap['nom']) ?></div>
          </div>
          <div class="progress-track">
            <?php 
                $percent = $cap['valeur_actuelle']; 
                if ($cap['nom'] == 'Serre 1') $percent = min(100, ($cap['valeur_actuelle']/40)*100); 
            ?>
            <div class="progress-fill <?= $cap['couleur'] ?> sensor-progress" style="width: <?= $percent ?>%"></div>
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


      <!-- INTRUSION OVERLAY (CACHÉ PAR DÉFAUT) -->
      <div id="intrusion-overlay" class="fixed inset-0 bg-red-600/20 backdrop-blur-[2px] z-50 flex items-center justify-center pointer-events-none hidden">
        <div class="bg-white p-6 rounded-[32px] shadow-2xl border-4 border-red-500 text-center animate-bounce">
          <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center text-red-600 mx-auto mb-4">
             <i data-lucide="shield-alert" class="w-10 h-10"></i>
          </div>
          <h2 class="text-2xl font-black text-red-600 mb-1">INTRUSION !</h2>
          <p class="text-sm font-bold text-slate-600">Mouvement détecté Zone A</p>
        </div>
      </div>

<?php
require 'includes/nav.php';
?>

<script>
/**
 * Logique de rafraîchissement dynamique du Dashboard
 * Interroge l'API interne toutes les 3 secondes pour mettre à jour les capteurs
 * sans rechargement de la page.
 */
function refreshDashboard() {
    fetch('api/get_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Mise à jour de l'heure
                document.getElementById('last-update-time').innerText = 'Mis à jour à ' + data.timestamp;

                // Mise à jour du badge d'alertes
                const alertBadge = document.getElementById('alert-badge');
                if (data.alertes_count > 0) {
                    alertBadge.innerText = data.alertes_count > 9 ? '9+' : data.alertes_count;
                    alertBadge.classList.remove('hidden');
                } else {
                    alertBadge.classList.add('hidden');
                }

                // Parcours et mise à jour des équipements
                data.equipements.forEach(eq => {
                    const card = document.querySelector(`.sensor-card[data-id="${eq.id}"]`);
                    if (card) {
                        const valElem = card.querySelector('.sensor-value');
                        const oldVal = valElem.innerText;
                        
                        // Si la valeur a changé, on applique l'animation de pulsation
                        if (oldVal != eq.valeur_actuelle) {
                            valElem.innerText = eq.valeur_actuelle;
                            valElem.classList.remove('animate-pulse-quick');
                            void valElem.offsetWidth; // Trigger reflow
                            valElem.classList.add('animate-pulse-quick');
                        }

                        // Mise à jour du badge de statut
                        const statusContainer = card.querySelector('.sensor-status');
                        // On ré-injecte le HTML du badge (simplifié ici pour la démo JS)
                        let badgeHtml = `<span class="pill ${eq.statut === 'normal' ? 'green' : (eq.statut === 'arret' ? 'grey' : 'orange')}">
                            <span class="status-dot ${eq.statut === 'normal' ? 'green' : (eq.statut === 'arret' ? 'grey' : 'red')}"></span>
                            ${eq.statut.toUpperCase()}
                        </span>`;
                        statusContainer.innerHTML = badgeHtml;

                        // Mise à jour de la barre de progression
                        const progress = card.querySelector('.sensor-progress');
                        if (progress) {
                            let percent = eq.valeur_actuelle;
                            if (eq.nom === 'Serre 1') percent = Math.min(100, (eq.valeur_actuelle / 40) * 100);
                            progress.style.width = percent + '%';
                        }
                    }

                    // Gestion spécifique de l'intrusion (ID 7)
                    if (eq.id == 7) {
                        const overlay = document.getElementById('intrusion-overlay');
                        if (eq.statut === 'critique' || eq.valeur_actuelle == 1) {
                            overlay.classList.remove('hidden');
                        } else {
                            overlay.classList.add('hidden');
                        }
                    }
                });
            }
        })
        .catch(err => console.error('Erreur de polling:', err));
}

// Lancement du polling toutes les 3 secondes
setInterval(refreshDashboard, 3000);
</script>

<?php
require 'includes/footer.php';
?>
