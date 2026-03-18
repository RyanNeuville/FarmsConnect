<?php
// Fichier: includes/functions.php

// Helper pour afficher la flèche de tendance (simulé pour le moment)
function getTrendIcon($valeur, $type) {
    if ($type === 'Serre 1' || $type === 'Batterie Nord') return '<i data-lucide="arrow-down" class="w-3 h-3 text-blue-500"></i>';
    if ($type === 'Humidité sol') return '<i data-lucide="arrow-up" class="w-3 h-3 text-red-500"></i>';
    return '<i data-lucide="arrow-down" class="w-3 h-3 text-blue-500"></i>';
}

// Helper pour le badge statut principal
function getStatusBadge($statut) {
    if ($statut === 'normal') {
        return '<span class="pill green"><span class="status-dot green"></span> Normal</span>';
    } elseif ($statut === 'alerte') {
        return '<span class="pill orange"><span class="status-dot orange" style="background-color:#f59e0b;"></span> Alerte</span>';
    } elseif ($statut === 'critique') {
        return '<span class="pill red" style="background-color:#fee2e2;color:#ef4444;"><span class="status-dot red" style="background-color:#ef4444;"></span> Critique</span>';
    } elseif ($statut === 'arret') {
        return '<span class="pill grey"><span class="status-dot grey"></span> Arrêté</span>';
    } elseif ($statut === 'marche') {
        return '<span class="pill green"><span class="status-dot green"></span> Marche</span>';
    }
    return '';
}

// Helper pour le formatage du bouton actionneur
function getActionButton($statut) {
    if ($statut === 'marche') {
        return '<button type="submit" name="action" value="0" class="bg-green-100 text-green-700 font-black text-xs py-2.5 rounded-xl w-full flex items-center justify-center gap-1 shadow-sm border border-green-200"><span class="w-[6px] h-[6px] rounded-full bg-green-500 block"></span> MARCHE</button>';
    } else {
        return '<button type="submit" name="action" value="1" class="bg-slate-200 text-slate-600 font-black text-xs py-2.5 rounded-xl w-full flex items-center justify-center gap-1 shadow-sm"><span class="w-[6px] h-[6px] rounded-full border border-slate-400 bg-transparent block"></span> ARRÊT</button>';
    }
}

// Helper pour les textes/couleurs brutes de statut
function getStatusHelpers($statut) {
    if ($statut === 'normal') return ['bg' => 'green', 'text' => 'Normal'];
    if ($statut === 'alerte') return ['bg' => 'orange', 'text' => 'Alerte'];
    if ($statut === 'critique') return ['bg' => 'red', 'text' => 'Critique'];
    if ($statut === 'arret') return ['bg' => 'grey', 'text' => 'Arrêté'];
    if ($statut === 'marche') return ['bg' => 'green', 'text' => 'Marche'];
    return ['bg' => 'grey', 'text' => 'Inconnu'];
}

// Helper pour le formatage des dates
function formatDate($dateStr) {
    try {
        $d = new DateTime($dateStr);
        return $d->format('d M. à H:i');
    } catch (Exception $e) {
        return $dateStr;
    }
}
?>
