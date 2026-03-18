<?php
/*
 * Fichier : actionneur.php
 * Interface de contrôle direct pour un dispositif actionneur matériel.
 * Charge l'état de l'actionneur et offre l'interface de commutation (marche/arrêt).
 */
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

/*
 * Mappage dynamique des états visuels de l'interface graphique selon l'état binaire (marche/arrêt) 
 * pour fluidifier le rendu côté client et assurer un code maintenable.
 */
$bgColor = $estEnMarche ? '#22c55e' : '#64748b'; /* Code hexadécimal de fond (Vert actif, Gris inactif) */
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
/* Importation des dépendances UI communes pour préserver la structure DRY */
require_once 'includes/functions.php';

$page_title = 'FarmsConnect - Actuateur ' . $actionneur['nom'];
$active_nav = 'equipements';

require 'includes/header.php';
?>
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

      <!-- BLOC PRINCIPAL DE CONTRÔLE DE L'ACTIONNEUR (MAIN ACTUATOR BLOCK) -->
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

<?php
require 'includes/nav.php';
require 'includes/footer.php';
?>
