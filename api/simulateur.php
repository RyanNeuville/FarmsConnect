<?php
/*
 * Fichier : api/simulateur.php
 * Générateur de données synthétiques (Moteur de simulation).
 * Ce script isolé injecte des variations stochastiques sur l'échantillonnage matériel virtuel 
 * afin d'éprouver la matrice d'alerting et fournir une démonstration temps-réel (Soutenance).
 */
require_once '../config/db.php';

echo "<h1>Lancement du simulateur FarmsConnect</h1>";

/* -- PHASE 1 : Analyse des deltas de mesure par application de bruit aléatoire -- */
$stmt = $pdo->query("SELECT * FROM equipements WHERE type = 'capteur'");
$capteurs = $stmt->fetchAll();

foreach ($capteurs as $capteur) {
    /* Application d'une dérive stochastique modérée (amplitude : ±0.5 unité de mesure) */
    $changement = mt_rand(-5, 5) / 10;
    
    /* Scénario d'exception : L'accumulateur d'énergie subit un déchargement asymétrique exclusif */
    if ($capteur['nom'] === 'Batterie Nord') {
        $changement = mt_rand(-5, -1) / 10;
    }
    
    $nouvelleValeur = max(0, $capteur['valeur_actuelle'] + $changement);
    
    /* -- PHASE 2 : Évaluation conditionnelle contre la table de vérité des seuils -- */
    $nouveauStatut = 'normal';
    $niveauAlerte = null;
    $messageAlerte = '';

    if ($capteur['seuil_min'] !== null && $nouvelleValeur < $capteur['seuil_min']) {
        $nouveauStatut = 'critique';
        $niveauAlerte = 'critique';
        $messageAlerte = "Valeur anormalement basse (".$nouvelleValeur.$capteur['unite'].") detectée sur ".$capteur['nom'];
    } elseif ($capteur['seuil_max'] !== null && $nouvelleValeur > $capteur['seuil_max']) {
        $nouveauStatut = 'critique';
        $niveauAlerte = 'critique';
        $messageAlerte = "Valeur anormalement haute (".$nouvelleValeur.$capteur['unite'].") detectée sur ".$capteur['nom'];
    } elseif ($capteur['seuil_min'] !== null && $nouvelleValeur < ($capteur['seuil_min'] + ($capteur['seuil_min']*0.1))) {
        /* Seuil d'avertissement anticipatif à ±10% de tolérance du seuil d'intervention physique */
        $nouveauStatut = 'alerte';
        $niveauAlerte = 'important';
        $messageAlerte = "Attention, valeur proche du minimum sur ".$capteur['nom'];
    }

    /* Synchonisation de l'état nominal de la métrique en base */
    $pdo->prepare("UPDATE equipements SET valeur_actuelle = ?, statut = ? WHERE id = ?")
        ->execute([$nouvelleValeur, $nouveauStatut, $capteur['id']]);
        
    echo "<p>{$capteur['nom']} : {$capteur['valeur_actuelle']} -> {$nouvelleValeur} ({$nouveauStatut})</p>";

    /* Persistance de l'échantillon pour analyse de série temporelle (Graphique analytique) */
    $pdo->prepare("INSERT INTO historique_donnees (equipement_id, valeur) VALUES (?, ?)")
        ->execute([$capteur['id'], $nouvelleValeur]);

    /* 
     * -- PHASE 3 : Dispatching intelligent des incidents --
     * Modèle de rate-limiting basique : bloque l'émission d'une nouvelle notification matérielle 
     * identique durant une fenêtre de throttling (5 minutes).
     */
    if ($niveauAlerte && $nouveauStatut !== $capteur['statut']) {
        /* Vérification du cache du système d'événements pour l'amortissement des alertes */
        $checkAlerte = $pdo->prepare("SELECT id FROM alertes WHERE equipement_id = ? AND cree_le > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $checkAlerte->execute([$capteur['id']]);
        
        if ($checkAlerte->rowCount() == 0) {
            $pdo->prepare("INSERT INTO alertes (equipement_id, niveau, message) VALUES (?, ?, ?)")
                ->execute([$capteur['id'], $niveauAlerte, $messageAlerte]);
            echo "<p style='color:red;'>=> Alerte générée !</p>";
        }
    }
}

/* 
 * -- PHASE EXTRA : Simulation d'événements de sécurité (Intrusion) --
 * Probabilité d'incident : 20% de chances de déclencher une intrusion lors de l'exécution du script.
 */
if (mt_rand(1, 10) <= 2) {
    echo "<h2 style='color:orange;'>Simulation de sécurité en cours...</h2>";
    
    // Détection de mouvement suspect (Equipement ID 7)
    $valeurMouvement = 1; // 1 = Mouvement détecté
    $pdo->prepare("UPDATE equipements SET valeur_actuelle = 1, statut = 'critique' WHERE id = 7")->execute();
    
    // Génération de l'alerte d'intrusion
    $pdo->prepare("INSERT INTO alertes (equipement_id, niveau, message) VALUES (7, 'critique', 'ALERTE : Intrusion détectée dans la Zone A !')")
        ->execute();
        
    echo "<p style='color:red; font-weight:bold;'>!!! INTRUSION DÉTECTÉE !!!</p>";
} else {
    // Réinitialisation du capteur de mouvement si aucune intrusion
    $pdo->prepare("UPDATE equipements SET valeur_actuelle = 0, statut = 'normal' WHERE id = 7")->execute();
}

echo "<hr><p>Simulation terminée. Retournez sur <a href='../index.php'>le tableau de bord</a> pour voir les changements.</p>";
?>
