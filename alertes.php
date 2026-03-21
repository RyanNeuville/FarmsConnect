<?php
/*
 * Fichier : alertes.php
 * Interface de triage et de supervision des notifications système.
 * Assure la communication des anomalies via un journal d'événements persistant.
 */
require_once 'config/db.php';
require_once 'includes/auth.php';

forcer_connexion();

/*
 * Double jointure implicite pour récupérer les détails de l'alerte
 * ainsi que le contexte matériel affecté, le tout projeté chronologiquement.
 */
$stmt = $pdo->query("SELECT a.*, e.nom as equipement_nom FROM alertes a JOIN equipements e ON a.equipement_id = e.id ORDER BY a.cree_le DESC");
$alertes = $stmt->fetchAll();

$critiques = [];
$importantes = [];
$nonLues = 0;

foreach ($alertes as $a) {
    if (!$a['est_lu']) {
        $nonLues++;
    }
    if ($a['niveau'] === 'critique') {
        $critiques[] = $a;
    } else {
        $importantes[] = $a;
    }
}

/* Intégration des dépendances visuelles partagées de l'application */
require_once 'includes/functions.php';

$page_title = 'FarmsConnect - Alertes';
$active_nav = 'alertes';

require 'includes/header.php';
?>

      <!-- HEADER -->
      <header class="flex justify-between items-center mt-4 mb-6">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center text-red-500">
            <i data-lucide="bell" class="w-6 h-6"></i>
          </div>
          <div>
            <h1 class="text-[1.3rem] font-black text-[#0f2b46] leading-tight">Alertes</h1>
            <p class="text-xs text-slate-400 font-bold"><?= $nonLues ?> non lues</p>
          </div>
        </div>
        
        <div class="flex gap-2">
          <?php if ($nonLues > 0): ?>
          <a href="api/manage_alerts.php?action=mark_all_read" class="w-10 h-10 bg-white card-border rounded-xl flex items-center justify-center text-slate-400 hover:text-green-500 transition-colors" title="Tout lire">
            <i data-lucide="check-check" class="w-5 h-5"></i>
          </a>
          <?php endif; ?>
          <?php if (!empty($alertes)): ?>
          <a href="api/manage_alerts.php?action=delete_all" onclick="return confirm('Voulez-vous vraiment vider tout le journal ?')" class="w-10 h-10 bg-white card-border rounded-xl flex items-center justify-center text-slate-400 hover:text-red-500 transition-colors" title="Tout supprimer">
            <i data-lucide="trash-2" class="w-5 h-5"></i>
          </a>
          <?php endif; ?>
        </div>
      </header>

      <!-- BLOC DES ALERTES DE SÉVÉRITÉ MAXIMALE (CRITIQUES) -->
      <?php if (!empty($critiques)): ?>
      <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
          <span class="w-2 h-2 rounded-full bg-red-500"></span>
          <h2 class="text-[11px] font-black text-red-500 uppercase tracking-wider">Critiques (<?= count($critiques) ?>)</h2>
        </div>

        <div class="space-y-3">
          <?php foreach ($critiques as $alerte): ?>
          <div class="bg-white rounded-xl shadow-[0_2px_4px_rgba(0,0,0,0.02)] border border-slate-100 border-l-4 border-l-red-500 p-4 relative <?= $alerte['est_lu'] ? 'opacity-60' : '' ?>">
            <?php if (!$alerte['est_lu']): ?>
            <a href="api/read_alert.php?id=<?= $alerte['id'] ?>" class="absolute top-3 right-3 text-slate-300 hover:text-red-500">
              <i data-lucide="x" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center text-red-500 shrink-0">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-1.5 mb-1">
                  <?php if (!$alerte['est_lu']): ?><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span><?php endif; ?>
                  <h3 class="text-sm font-bold text-[#0f2b46]"><?= htmlspecialchars($alerte['equipement_nom']) ?></h3>
                </div>
                <p class="text-[11px] text-slate-500 font-semibold mb-3"><?= htmlspecialchars($alerte['message']) ?></p>

                <div class="flex items-center justify-between">
                  <span class="text-[10px] text-slate-400 font-bold"><?= formatDate($alerte['cree_le']) ?></span>
                  <div class="flex gap-2">
                    <?php if (!$alerte['est_lu']): ?>
                    <a href="api/read_alert.php?id=<?= $alerte['id'] ?>" class="bg-slate-100 text-slate-500 px-3 py-1.5 rounded-lg text-[10px] font-bold flex items-center gap-1">
                      <i data-lucide="eye" class="w-3 h-3"></i> Lu
                    </a>
                    <?php endif; ?>
                    <a href="detail.php?id=<?= $alerte['equipement_id'] ?>" class="bg-[#d1fae5] text-[#059669] px-3 py-1.5 rounded-lg text-[10px] font-bold flex items-center gap-1">
                      Voir <i data-lucide="chevron-right" class="w-3 h-3"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- BLOC DES AVERTISSEMENTS ORANGES (IMPORTANTES) -->
      <?php if (!empty($importantes)): ?>
      <div>
        <div class="flex items-center gap-2 mb-3">
          <span class="w-2 h-2 rounded-full bg-orange-400"></span>
          <h2 class="text-[11px] font-black text-orange-400 uppercase tracking-wider">Importants (<?= count($importantes) ?>)</h2>
        </div>

        <div class="space-y-3">
          <?php foreach ($importantes as $alerte): ?>
          <div class="bg-white rounded-xl shadow-[0_2px_4px_rgba(0,0,0,0.02)] border border-slate-100 border-l-4 border-l-orange-400 p-4 relative <?= $alerte['est_lu'] ? 'opacity-60' : '' ?>">
            <?php if (!$alerte['est_lu']): ?>
            <a href="api/read_alert.php?id=<?= $alerte['id'] ?>" class="absolute top-3 right-3 text-slate-300 hover:text-orange-500">
              <i data-lucide="x" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center text-orange-500 shrink-0">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-1.5 mb-1">
                  <?php if (!$alerte['est_lu']): ?><span class="w-2.5 h-2.5 rounded-full bg-orange-400"></span><?php endif; ?>
                  <h3 class="text-sm font-bold text-[#0f2b46]"><?= htmlspecialchars($alerte['equipement_nom']) ?></h3>
                </div>
                <p class="text-[11px] text-slate-500 font-semibold mb-3"><?= htmlspecialchars($alerte['message']) ?></p>

                <div class="flex items-center justify-between">
                  <span class="text-[10px] text-slate-400 font-bold"><?= formatDate($alerte['cree_le']) ?></span>
                  <div class="flex gap-2">
                    <?php if (!$alerte['est_lu']): ?>
                    <a href="api/read_alert.php?id=<?= $alerte['id'] ?>" class="bg-slate-100 text-slate-500 px-3 py-1.5 rounded-lg text-[10px] font-bold flex items-center gap-1">
                      <i data-lucide="eye" class="w-3 h-3"></i> Lu
                    </a>
                    <?php endif; ?>
                    <a href="detail.php?id=<?= $alerte['equipement_id'] ?>" class="bg-[#d1fae5] text-[#059669] px-3 py-1.5 rounded-lg text-[10px] font-bold flex items-center gap-1">
                      Voir <i data-lucide="chevron-right" class="w-3 h-3"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (empty($critiques) && empty($importantes)): ?>
        <div class="text-center py-10 text-slate-400">
            <i data-lucide="check-circle" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
            <p class="font-bold">Aucune alerte pour le moment</p>
        </div>
      <?php endif; ?>
    </main>

<?php
require 'includes/nav.php';
require 'includes/footer.php';
?>
