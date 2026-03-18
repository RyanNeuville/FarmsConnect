<?php
// Fichier: api/simulateur.php
// Script caché pour générer des données dynamiques et déclencher des alertes pour la soutenance.
require_once '../config/db.php';

echo "<h1>Lancement du simulateur FarmsConnect</h1>";

// 1. Fluctuaton des capteurs
$stmt = $pdo->query("SELECT * FROM equipements WHERE type = 'capteur'");
$capteurs = $stmt->fetchAll();

foreach ($capteurs as $capteur) {
    // Fluctuation aléatoire (ex: entre -0.5 et +0.5)
    $changement = mt_rand(-5, 5) / 10;
    
    // Cas spécial pour la batterie qui ne fait que descendre (entre -0.1 et -0.5)
    if ($capteur['nom'] === 'Batterie Nord') {
        $changement = mt_rand(-5, -1) / 10;
    }
    
    $nouvelleValeur = max(0, $capteur['valeur_actuelle'] + $changement);
    
    // Vérification des seuils
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
        // Pré-alerte (10% de marge)
        $nouveauStatut = 'alerte';
        $niveauAlerte = 'important';
        $messageAlerte = "Attention, valeur proche du minimum sur ".$capteur['nom'];
    }

    // Mise à jour de l'équipement
    $pdo->prepare("UPDATE equipements SET valeur_actuelle = ?, statut = ? WHERE id = ?")
        ->execute([$nouvelleValeur, $nouveauStatut, $capteur['id']]);
        
    echo "<p>{$capteur['nom']} : {$capteur['valeur_actuelle']} -> {$nouvelleValeur} ({$nouveauStatut})</p>";

    // Insérer dans l'historique
    $pdo->prepare("INSERT INTO historique_donnees (equipement_id, valeur) VALUES (?, ?)")
        ->execute([$capteur['id'], $nouvelleValeur]);

    // Générer une alerte si le statut a changé vers critique ou alerte, et qu'on n'a pas déjà spam les 5 dernières minutes
    if ($niveauAlerte && $nouveauStatut !== $capteur['statut']) {
        // Simple protection anti-spam: vérifier la dernière alerte
        $checkAlerte = $pdo->prepare("SELECT id FROM alertes WHERE equipement_id = ? AND cree_le > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $checkAlerte->execute([$capteur['id']]);
        
        if ($checkAlerte->rowCount() == 0) {
            $pdo->prepare("INSERT INTO alertes (equipement_id, niveau, message) VALUES (?, ?, ?)")
                ->execute([$capteur['id'], $niveauAlerte, $messageAlerte]);
            echo "<p style='color:red;'>=> Alerte générée !</p>";
        }
    }
}

echo "<hr><p>Simulation terminée. Retournez sur <a href='../index.php'>le tableau de bord</a> pour voir les changements.</p>";
?>
