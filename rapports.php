<?php
/*
 * Fichier : rapports.php
 * Module de statistiques globales et d'exports de données (CSV/PDF).
 * Fournit une vue analytique complète de l'exploitation agricole connectée.
 */
require_once 'config/db.php';
require_once 'includes/auth.php';

forcer_connexion();

require_once 'includes/functions.php';

$page_title    = 'FarmsConnect - Rapports';
$active_nav    = 'rapports';

// ─── AGRÉGATION DES STATISTIQUES ────────────────────────────────────────────

// Capteurs avec valeurs actuelles et seuils
$capteurs = $pdo->query("SELECT * FROM equipements WHERE type = 'capteur' ORDER BY id")->fetchAll();
$actionneurs = $pdo->query("SELECT * FROM equipements WHERE type = 'actionneur' ORDER BY id")->fetchAll();

// Nombre total d'alertes / critiques / lues
$statsAlertes = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN niveau = 'critique' THEN 1 ELSE 0 END) as critiques,
        SUM(CASE WHEN est_lu = 1 THEN 1 ELSE 0 END) as lues,
        SUM(CASE WHEN est_lu = 0 THEN 1 ELSE 0 END) as non_lues
    FROM alertes
")->fetch();

// Historique des 24 dernières heures (données capteurs)
$historique = $pdo->query("
    SELECT h.*, e.nom, e.unite, e.icone
    FROM historique_donnees h
    JOIN equipements e ON h.equipement_id = e.id
    ORDER BY h.enregistre_le DESC
    LIMIT 200
")->fetchAll();

// Dernières alertes pour le rapport
$dernieresAlertes = $pdo->query("
    SELECT a.*, e.nom as equipement_nom
    FROM alertes a
    JOIN equipements e ON a.equipement_id = e.id
    ORDER BY a.cree_le DESC
    LIMIT 20
")->fetchAll();

// Statut général du système
$nbCritique = 0;
$nbNormal   = 0;
foreach ($capteurs as $c) {
    if ($c['statut'] === 'critique') $nbCritique++;
    else $nbNormal++;
}
$sante_systeme = $nbCritique === 0 ? 'Optimal' : $nbCritique . ' anomalie(s)';
$sante_couleur = $nbCritique === 0 ? 'green' : 'red';

// Actionneur actif/inactif
$nbActifOn = count(array_filter($actionneurs, fn($a) => $a['statut'] === 'marche'));

require 'includes/header.php';
?>

      <!-- HEADER -->
      <header class="flex justify-between items-center mt-4 mb-6">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600">
            <i data-lucide="bar-chart-2" class="w-6 h-6"></i>
          </div>
          <div>
            <h1 class="text-[1.3rem] font-black text-brand-dark dark:text-white leading-tight">Rapports</h1>
            <p class="text-[10px] text-slate-400 font-bold"><?= date('d/m/Y — H:i') ?></p>
          </div>
        </div>
        <form action="logout.php" method="POST">
          <button type="submit" class="w-10 h-10 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-full flex items-center justify-center text-red-500 shadow-sm">
            <i data-lucide="log-out" class="w-5 h-5"></i>
          </button>
        </form>
      </header>

      <!-- SANTÉ DU SYSTÈME -->
      <div class="flex gap-3 mb-6">
        <div class="flex-1 card-border p-4 flex flex-col items-center justify-center gap-1">
          <i data-lucide="<?= $sante_couleur === 'green' ? 'shield-check' : 'shield-alert' ?>" class="w-6 h-6 text-<?= $sante_couleur ?>-500"></i>
          <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Système</p>
          <p class="text-sm font-black text-<?= $sante_couleur ?>-500"><?= $sante_systeme ?></p>
        </div>
        <div class="flex-1 card-border p-4 flex flex-col items-center justify-center gap-1">
          <i data-lucide="bell" class="w-6 h-6 text-orange-500"></i>
          <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Alertes actives</p>
          <p class="text-sm font-black text-orange-500"><?= $statsAlertes['non_lues'] ?> non lues</p>
        </div>
        <div class="flex-1 card-border p-4 flex flex-col items-center justify-center gap-1">
          <i data-lucide="zap" class="w-6 h-6 text-purple-500"></i>
          <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Actionneurs</p>
          <p class="text-sm font-black text-purple-500"><?= $nbActifOn ?> actifs</p>
        </div>
      </div>

      <!-- KPIs DÉTAILLÉS -->
      <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 mb-3 uppercase tracking-wider px-1">Indicateurs Clés</h2>
      <div class="grid grid-cols-2 gap-3 mb-6">

        <?php foreach ($capteurs as $cap): ?>
        <?php
          $isOk = ($cap['statut'] === 'normal');
          $color = $isOk ? 'green' : ($cap['statut'] === 'critique' ? 'red' : 'orange');
        ?>
        <div class="card-border p-4">
          <div class="flex items-center gap-2 mb-2">
            <div class="w-7 h-7 bg-<?= $color ?>-100 dark:bg-<?= $color ?>-900/30 rounded-lg flex items-center justify-center text-<?= $color ?>-500">
              <i data-lucide="<?= htmlspecialchars($cap['icone']) ?>" class="w-3.5 h-3.5"></i>
            </div>
            <span class="text-[10px] font-black text-slate-400 uppercase truncate"><?= htmlspecialchars($cap['nom']) ?></span>
          </div>
          <p class="text-2xl font-black text-slate-800 dark:text-white"><?= $cap['valeur_actuelle'] ?><span class="text-sm font-bold text-slate-400 ml-1"><?= $cap['unite'] ?></span></p>
          <div class="flex items-center justify-between mt-1">
            <span class="text-[9px] font-black text-slate-400">Min <?= $cap['seuil_min'] ?> / Max <?= $cap['seuil_max'] ?? '∞' ?></span>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-<?= $color ?>-100 text-<?= $color ?>-600 font-black dark:bg-<?= $color ?>-900/30 dark:text-<?= $color ?>-400"><?= strtoupper($cap['statut']) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- STATISTIQUES DES ALERTES -->
      <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 mb-3 uppercase tracking-wider px-1">Statistiques des Alertes</h2>
      <div class="card-border p-4 mb-6">
        <div class="grid grid-cols-2 gap-4">
          <div class="text-center">
            <p class="text-3xl font-black text-slate-800 dark:text-white"><?= $statsAlertes['total'] ?></p>
            <p class="text-[10px] font-black text-slate-400 uppercase mt-1">Total</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-black text-red-500"><?= $statsAlertes['critiques'] ?></p>
            <p class="text-[10px] font-black text-slate-400 uppercase mt-1">Critiques</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-black text-green-500"><?= $statsAlertes['lues'] ?></p>
            <p class="text-[10px] font-black text-slate-400 uppercase mt-1">Lues</p>
          </div>
          <div class="text-center">
            <p class="text-3xl font-black text-orange-500"><?= $statsAlertes['non_lues'] ?></p>
            <p class="text-[10px] font-black text-slate-400 uppercase mt-1">Non lues</p>
          </div>
        </div>

        <?php if ($statsAlertes['total'] > 0): ?>
        <div class="mt-4">
          <div class="flex justify-between text-[9px] font-black text-slate-400 mb-1 uppercase">
            <span>Taux de lecture</span>
            <span><?= round(($statsAlertes['lues'] / $statsAlertes['total']) * 100) ?>%</span>
          </div>
          <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
            <div class="h-full bg-green-500 rounded-full transition-all duration-700" style="width:<?= round(($statsAlertes['lues'] / $statsAlertes['total']) * 100) ?>%"></div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- JOURNAL DES ALERTES RÉCENTES -->
      <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 mb-3 uppercase tracking-wider px-1">Journal des Alertes Récentes</h2>
      <div class="card-border mb-6 overflow-hidden">
        <?php if (empty($dernieresAlertes)): ?>
        <div class="p-6 text-center text-slate-400 text-xs font-bold">Aucune alerte enregistrée</div>
        <?php else: ?>
        <?php foreach ($dernieresAlertes as $i => $al): ?>
        <div class="flex items-center justify-between p-3 <?= $i !== count($dernieresAlertes)-1 ? 'border-b border-slate-100 dark:border-slate-700/50' : '' ?>">
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full <?= $al['niveau'] === 'critique' ? 'bg-red-500' : 'bg-orange-400' ?>"></div>
            <div>
              <p class="text-[12px] font-bold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($al['equipement_nom']) ?></p>
              <p class="text-[10px] font-bold text-slate-400 truncate max-w-[160px]"><?= htmlspecialchars($al['message']) ?></p>
            </div>
          </div>
          <span class="text-[9px] font-black text-slate-400 shrink-0"><?= date('d/m H:i', strtotime($al['cree_le'])) ?></span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- EXPORTS -->
      <h2 class="text-xs font-black text-slate-500 dark:text-slate-400 mb-3 uppercase tracking-wider px-1">Exporter les Données</h2>

      <div class="card-border p-4 mb-4">
        <div class="flex items-center gap-2 mb-3">
          <div class="w-6 h-6 bg-emerald-100 dark:bg-emerald-900/30 rounded-md flex items-center justify-center text-emerald-600">
            <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
          </div>
          <div>
            <h3 class="font-bold text-sm text-brand-dark dark:text-white">Export CSV</h3>
            <p class="text-[10px] text-slate-400 font-bold">Données brutes pour Excel / Google Sheets</p>
          </div>
        </div>
        <div class="flex flex-col gap-2">
          <a href="api/export_csv.php?type=sensors" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
            <span class="text-[12px] font-bold text-slate-600 dark:text-slate-400">Rapport capteurs en temps réel</span>
            <i data-lucide="download" class="w-4 h-4 text-emerald-500"></i>
          </a>
          <a href="api/export_csv.php?type=alerts" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
            <span class="text-[12px] font-bold text-slate-600 dark:text-slate-400">Journal des alertes complet</span>
            <i data-lucide="download" class="w-4 h-4 text-emerald-500"></i>
          </a>
          <a href="api/export_csv.php?type=history" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
            <span class="text-[12px] font-bold text-slate-600 dark:text-slate-400">Historique mesures (24h)</span>
            <i data-lucide="download" class="w-4 h-4 text-emerald-500"></i>
          </a>
        </div>
      </div>

      <div class="card-border p-4 mb-24">
        <div class="flex items-center gap-2 mb-3">
          <div class="w-6 h-6 bg-red-100 dark:bg-red-900/30 rounded-md flex items-center justify-center text-red-500">
            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
          </div>
          <div>
            <h3 class="font-bold text-sm text-brand-dark dark:text-white">Export PDF</h3>
            <p class="text-[10px] text-slate-400 font-bold">Rapport professionnel avec logo FarmsConnect</p>
          </div>
        </div>
        <div class="flex flex-col gap-2">
          <a href="api/export_pdf.php?type=global" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
            <div>
              <span class="text-[12px] font-bold text-slate-600 dark:text-slate-400">Rapport global de la ferme</span>
              <p class="text-[9px] text-slate-400 font-bold">Capteurs • Alertes • Actionneurs</p>
            </div>
            <i data-lucide="download" class="w-4 h-4 text-red-500"></i>
          </a>
          <a href="api/export_pdf.php?type=alerts" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
            <div>
              <span class="text-[12px] font-bold text-slate-600 dark:text-slate-400">Rapport des alertes</span>
              <p class="text-[9px] text-slate-400 font-bold">Journal complet avec traçabilité</p>
            </div>
            <i data-lucide="download" class="w-4 h-4 text-red-500"></i>
          </a>
        </div>
      </div>

<?php
require 'includes/nav.php';
require 'includes/footer.php';
?>
