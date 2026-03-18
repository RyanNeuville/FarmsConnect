<?php
/*
 * Fichier : equipements.php
 * Vue récapitulative exhaustive de l'infrastructure robotique en place.
 * Fournit un diagnostic rapide et des points d'accès vers les panneaux détaillés.
 */
require_once 'config/db.php';
require_once 'includes/auth.php';

forcer_connexion();

$stmt = $pdo->query("SELECT * FROM equipements ORDER BY id ASC");
$equipements = $stmt->fetchAll();
$nbTotal = count($equipements);

/* Implémentation du dictionnaire graphique unifié */
require_once 'includes/functions.php';

$page_title = 'FarmsConnect - Équipements';
$active_nav = 'equipements';

require 'includes/header.php';
?>
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

      <!-- BANNIÈRE DE TÉLÉMÉTRIE GLOBALE (STATUS BANNER) -->
      <div class="bg-brand-green-light rounded-xl p-3 mb-6 flex items-center gap-2">
        <i data-lucide="wifi" class="w-4 h-4 text-green-600"></i>
        <span class="font-bold text-green-600 text-[13px]">Connecté – données en temps réel</span>
      </div>

      <!-- ITÉRATION ET AFFICHAGE DES UNITÉS CONNECTÉES -->
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

<?php
require 'includes/nav.php';
require 'includes/footer.php';
?>
