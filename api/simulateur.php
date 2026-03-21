<?php
/*
 * Fichier : api/simulateur.php
 * Journal d'activité du moteur de simulation.
 * Ce fichier est devenu un simple moniteur passif car la simulation est désormais 
 * pilotée intelligemment par le Dashboard lui-même.
 */
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!est_connecte()) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Moniteur de Simulation';
require_once '../includes/header.php';
?>

<div class="p-6">
    <div class="flex items-center gap-4 mb-8">
        <a href="../index.php" class="w-10 h-10 bg-white border border-slate-200 rounded-full flex items-center justify-center text-slate-600 shadow-sm">
            <i data-lucide="chevron-left" class="w-6 h-6"></i>
        </a>
        <h1 class="text-2xl font-black text-brand-dark">Moniteur de Simulation</h1>
    </div>

    <div class="bg-brand-green-light border border-brand-green rounded-2xl p-6 mb-6 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-green-600 mx-auto mb-4">
            <i data-lucide="cpu" class="w-10 h-10"></i>
        </div>
        <h2 class="text-xl font-black text-green-700 mb-2">Auto-Pilote Intelligent Actif</h2>
        <p class="text-sm text-green-600 font-bold max-w-xs mx-auto">
            Le moteur de simulation est désormais synchronisé avec ton Dashboard. 
            Il tourne silencieusement en arrière-plan toutes les 5 secondes.
        </p>
    </div>

    <h2 class="text-xs font-black text-slate-500 mb-3 uppercase tracking-wider px-1">Derniers événements injectés</h2>
    <div class="card-border bg-white overflow-hidden">
        <?php
        $stmt = $pdo->query("SELECT a.*, e.nom as equip_nom FROM alertes a JOIN equipements e ON a.equipement_id = e.id ORDER BY a.cree_le DESC LIMIT 10");
        $logs = $stmt->fetchAll();

        if (empty($logs)): ?>
            <div class="p-10 text-center text-slate-400 font-bold">Aucune activité récente détectée.</div>
        <?php else:
            foreach ($logs as $log): ?>
                <div class="flex justify-between items-center p-4 border-b border-slate-50">
                    <div>
                        <p class="text-[13px] font-black <?= $log['niveau'] === 'critique' ? 'text-red-600' : 'text-slate-800' ?>">
                            <?= htmlspecialchars($log['message']) ?>
                        </p>
                        <p class="text-[10px] text-slate-400 font-bold"><?= $log['cree_le'] ?></p>
                    </div>
                    <div class="icon-box <?= $log['niveau'] === 'critique' ? 'red' : 'green' ?> scale-75">
                        <i data-lucide="<?= $log['niveau'] === 'critique' ? 'alert-triangle' : 'info' ?>" class="w-5 h-5"></i>
                    </div>
                </div>
            <?php endforeach; 
        endif; ?>
    </div>

    <div class="mt-8 p-4 bg-slate-100 rounded-xl border border-dashed border-slate-300">
        <p class="text-[11px] text-slate-500 font-bold leading-relaxed">
            Note technique : La simulation est déclenchée par le fichier <code>api/get_stats.php</code> 
            via la fonction <code>runSimulationEngine()</code>. Plus de bouton manuel requis.
        </p>
    </div>
</div>

<?php 
require_once '../includes/footer.php';
?>
