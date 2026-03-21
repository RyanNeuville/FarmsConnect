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
$stmt = $pdo->prepare("SELECT * FROM equipements ORDER BY id ASC");
$stmt->execute();
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
$stmtAlertes = $pdo->prepare("SELECT COUNT(*) as nb FROM alertes WHERE est_lu = ?");
$stmtAlertes->execute([0]);
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
            <h1 class="text-[1.1rem] font-black text-brand-dark dark:text-white leading-tight">FarmsConnect</h1>
            <p class="text-xs text-slate-400 font-bold" id="last-update-time">Mis à jour à <?= date('H:i') ?></p>
          </div>
        </div>

        <div class="flex gap-2">
          <a href="alertes.php" class="w-10 h-10 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-600 shadow-sm block relative">
            <i data-lucide="bell" class="w-5 h-5"></i>
            <span id="alert-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-[18px] h-[18px] flex items-center justify-center rounded-full border-2 border-[#fafbfd] <?= ($alertesCount > 0) ? '' : 'hidden' ?>">
              <?= $alertesCount > 9 ? '9+' : $alertesCount ?>
            </span>
          </a>
          <a href="logout.php" class="w-10 h-10 bg-white border border-slate-200 rounded-full flex items-center justify-center text-red-500 shadow-sm" title="Déconnexion">
            <i data-lucide="log-out" class="w-5 h-5"></i>
          </a>
        </div>
      </header>

      <!-- RÉSUMÉ MÉTÉOROLOGIQUE (WEATHER WIDGET) -->
      <?php $weather = simulateWeather(); ?>
      <div id="weather-widget" class="flex items-center gap-1.5 text-sm font-bold text-slate-500 dark:text-slate-400 mb-4 px-1">
        <i id="weather-icon" data-lucide="<?= $weather['icon'] ?>" class="w-5 h-5 <?= $weather['icon'] === 'sun' ? 'text-orange-400' : 'text-blue-400' ?>"></i>
        <span id="weather-temp" class="text-slate-800 dark:text-slate-200"><?= $weather['temp'] ?></span>
        <span id="weather-desc" class="font-semibold text-slate-400 dark:text-slate-500"><?= $weather['condition'] ?></span>
      </div>

      <!-- BANNIÈRE GLOBALE DE STATUT (STATUS BANNER) -->
      <div id="status-banner" class="bg-brand-green-light border border-brand-green rounded-xl p-3 mb-6 transition-all duration-500">
        <p id="status-message" class="font-bold text-green-600 text-sm">
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
          <h3 class="text-xs font-bold text-slate-800 dark:text-slate-300 mb-1"><?= htmlspecialchars($cap['nom']) ?></h3>
          <div class="flex items-baseline gap-1">
            <span class="text-xl font-black text-black dark:text-white sensor-value"><?= $cap['valeur_actuelle'] ?></span>
            <span class="text-xs font-bold text-slate-400 dark:text-slate-500"><?= $cap['unite'] ?></span>
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
          <h3 class="text-xs font-bold text-slate-800 dark:text-slate-300 mb-3"><?= htmlspecialchars($act['nom']) ?></h3>
          <div class="actuator-control-container" data-id="<?= $act['id'] ?>">
            <?= getActionButton($act['statut'], $act['id']) ?>
          </div>
          <a href="actionneur.php?id=<?= $act['id'] ?>" class="text-[10px] text-slate-400 font-bold text-center mt-3 block flex items-center justify-center gap-1">
            Détails <i data-lucide="chevron-right" class="w-3 h-3"></i>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- HISTORIQUE DES CHANGEMENTS D'ÉTAT (RECENT ACTIVITIES) -->
      <h2 class="text-xs font-black text-slate-500 mb-3 uppercase tracking-wider px-1">Activités récentes</h2>
      <div class="card-border mb-4" id="activities-container">
        <!-- Les activités seront injectées ici par le script JS -->
        <div class="p-8 text-center text-slate-400 text-xs font-bold">Initialisation du flux...</div>
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

                    // Mise à jour spécifique pour les actionneurs (boutons)
                    const actuatorCont = document.querySelector(`.actuator-control-container[data-id="${eq.id}"]`);
                    if (actuatorCont) {
                        // On vérifie si le bouton actuel correspond au nouveau statut
                        const btn = actuatorCont.querySelector('.actuator-btn');
                        const currentAction = btn.getAttribute('data-action'); // 1=OFF->ON, 0=ON->OFF
                        const expectedAction = (eq.statut === 'marche') ? "0" : "1";
                        
                        if (currentAction !== expectedAction) {
                            fetch(`api/get_button_html.php?id=${eq.id}&statut=${eq.statut}`)
                                .then(res => res.text())
                                .then(html => {
                                    actuatorCont.innerHTML = html;
                                    if (window.lucide) lucide.createIcons();
                                });
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

                // Mise à jour de la Météo
                if (data.weather) {
                    document.getElementById('weather-temp').innerText = data.weather.temp;
                    document.getElementById('weather-desc').innerText = data.weather.condition;
                    const wIcon = document.getElementById('weather-icon');
                    wIcon.setAttribute('data-lucide', data.weather.icon);
                    wIcon.classList.toggle('text-orange-400', data.weather.icon === 'sun');
                    wIcon.classList.toggle('text-blue-400', data.weather.icon === 'moon');
                }

                // Mise à jour de la Bannière de Statut
                if (data.system_status) {
                    const banner = document.getElementById('status-banner');
                    const msg = document.getElementById('status-message');
                    msg.innerText = `Bonjour ${"<?= htmlspecialchars($user_nom) ?>"}, ${data.system_status.message}`;
                    
                    banner.className = `border rounded-xl p-3 mb-6 transition-all duration-500 ${data.system_status.class}`;
                    const msgClass = data.system_status.is_ok ? 'text-green-600' : 'text-red-600';
                    msg.className = `font-bold text-sm ${msgClass}`;
                }

                // Mise à jour de la section Activités Récentes
                const activitiesContainer = document.getElementById('activities-container');
                if (data.activites && data.activites.length > 0) {
                    let html = '';
                    data.activites.forEach(act => {
                        html += `
                        <a href="alertes.php" class="flex justify-between items-center p-4 border-b border-slate-100 hover:bg-slate-50 transition-colors block">
                          <div class="flex items-center gap-3">
                            <i data-lucide="${act.icone}" class="w-5 h-5 ${act.niveau === 'critique' ? 'text-red-500' : 'text-blue-500'}"></i>
                            <p class="text-[13px] font-bold text-slate-700">${act.message}</p>
                          </div>
                          <div class="flex items-center gap-1 text-[11px] font-bold text-slate-400">
                            <i data-lucide="clock" class="w-3 h-3"></i> 
                            ${new Date(act.cree_le).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                          </div>
                        </a>`;
                    });
                    activitiesContainer.innerHTML = html;
                    // On demande à Lucide de re-scanné le nouveau HTML pour générer les icônes SVGs
                    if (window.lucide) {
                        lucide.createIcons();
                    }
                }
            }
        })
        .catch(err => console.error('Erreur de polling:', err));
}

// Lancement du polling toutes les 3 secondes
setInterval(refreshDashboard, 3000);

/**
 * Gestionnaire de clics sur les boutons d'actionneurs (AJAX)
 */
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.actuator-btn');
    if (btn) {
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        
        // Feedback visuel immédiat (Loading state)
        btn.classList.add('opacity-50', 'pointer-events-none');
        
        const formData = new FormData();
        formData.append('equipement_id', id);
        formData.append('action', action);
        
        fetch('api/action.php?ajax=1', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // On force un rafraîchissement immédiat pour voir le changement
                refreshDashboard();
            }
        })
        .catch(err => console.error('Action error:', err))
        .finally(() => {
            btn.classList.remove('opacity-50', 'pointer-events-none');
        });
    }
});
</script>

<?php
require 'includes/footer.php';
?>
