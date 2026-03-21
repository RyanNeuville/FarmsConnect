<?php
/*
 * Fichier : includes/functions.php
 * Regroupe les fonctions de rendu (helpers UI) pour l'ensemble du projet, permettant de
 * maintenir une architecture de présentation DRY (Don't Repeat Yourself).
 */

/**
 * Calcule et renvoie un indicateur visuel de tendance de valeur pour l'instrumentation.
 * 
 * @param mixed $valeur La valeur courante mesurée
 * @param string $type La désignation du capteur ciblé
 * @return string Balise HTML (icône) indiquant graphiquement la dynamique de la métrique
 */
function getTrendIcon($valeur, $type) {
    if ($type === 'Serre 1' || $type === 'Batterie Nord') return '<i data-lucide="arrow-down" class="w-3 h-3 text-blue-500"></i>';
    if ($type === 'Humidité sol') return '<i data-lucide="arrow-up" class="w-3 h-3 text-red-500"></i>';
    return '<i data-lucide="arrow-down" class="w-3 h-3 text-blue-500"></i>';
}

/**
 * Génère le composant d'interface (badge textuel avec point d'état) correspondant au statut de l'équipement.
 * 
 * @param string $statut La classification de l'état asynchrone (ex: 'normal', 'critique', 'arret')
 * @return string Chaîne HTML prête à l'intégration DOM
 */
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

/**
 * Assemble les classes et propriétés visuelles d'un bouton de commande interactif
 * lié au contrôle d'un actionneur spécifique.
 * 
 * @param string $statut L'état d'opérabilité courant ('marche' ou 'arret')
 * @return string Le code markup configuré du bouton de soumission
 */
function getActionButton($statut, $id = 0) {
    if ($statut === 'marche') {
        return '<button type="button" data-id="'.$id.'" data-action="0" class="actuator-btn bg-green-100 text-green-700 font-black text-xs py-2.5 rounded-xl w-full flex items-center justify-center gap-1 shadow-sm border border-green-200"><span class="w-[6px] h-[6px] rounded-full bg-green-500 block"></span> MARCHE</button>';
    } else {
        return '<button type="button" data-id="'.$id.'" data-action="1" class="actuator-btn bg-slate-200 text-slate-600 font-black text-xs py-2.5 rounded-xl w-full flex items-center justify-center gap-1 shadow-sm"><span class="w-[6px] h-[6px] rounded-full border border-slate-400 bg-transparent block"></span> ARRÊT</button>';
    }
}

/**
 * Évalue le statut de fonctionnement et renvoie le dictionnaire de configuration
 * des propriétés cosmétiques (fond chromatique, format textuel) pour le moteur de rendu.
 * 
 * @param string $statut Sémantique définissant la sévérité du capteur (ex: 'alerte')
 * @return array Tableau associatif comportant les clés 'bg' et 'text'
 */
function getStatusHelpers($statut) {
    if ($statut === 'normal') return ['bg' => 'green', 'text' => 'Normal'];
    if ($statut === 'alerte') return ['bg' => 'orange', 'text' => 'Alerte'];
    if ($statut === 'critique') return ['bg' => 'red', 'text' => 'Critique'];
    if ($statut === 'arret') return ['bg' => 'grey', 'text' => 'Arrêté'];
    if ($statut === 'marche') return ['bg' => 'green', 'text' => 'Marche'];
    return ['bg' => 'grey', 'text' => 'Inconnu'];
}

/**
 * Procède à la conversion d'une chaîne SQL Date/Time standardisée vers un format
 * textuel raccourci propre à l'interface mobile (ex: "18 Mar. à 14:30").
 * 
 * @param string $dateStr La date au format standardisé d'entrée
 * @return string La date transformée lisible par un être humain
 */
function formatDate($dateStr) {
    try {
        $d = new DateTime($dateStr);
        return $d->format('d M. à H:i');
    } catch (Exception $e) {
        return $dateStr;
    }
}
/**
 * Simule des données météo en fonction de l'heure actuelle (cycle circadien).
 * Température plus basse la nuit, pic en milieu d'après-midi.
 * 
 * @return array ['temp', 'condition', 'icon']
 */
function simulateWeather() {
    $hour = (int)date('H');
    
    // Courbe sinusoïdale simple : Min à 4h, Max à 16h
    // Moyenne 21°C, Amplitude +/- 7°C
    $tempBase = 21 + 7 * sin(($hour - 10) * pi() / 12);
    $temp = round($tempBase + (mt_rand(-5, 5) / 10), 1); // Petite variation aléatoire
    
    $isNight = ($hour > 19 || $hour < 7);
    
    return [
        'temp' => $temp . '°C',
        'condition' => $isNight ? 'Nuit étoilée' : ($temp > 25 ? 'Chaleur intense' : 'Ensoleillé'),
        'icon' => $isNight ? 'moon' : 'sun'
    ];
}
?>
