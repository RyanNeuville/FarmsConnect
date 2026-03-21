<?php
/**
 * Moteur de Simulation Centralisé
 * Ce fichier gère la logique d'évolution de la ferme en arrière-plan.
 * Il est conçu pour être appelé par le Dashboard (polling) sans intervention manuelle.
 */

function runSimulationEngine($pdo) {
    /* 
     * -- LOGIQUE DE COOLDOWN --
     * On ne lance la simulation que si la dernière mise à jour date de plus de 5 secondes
     * pour éviter d'encombrer la base de données lors de pollings fréquents.
     */
    $now = time();
    $cacheFile = __DIR__ . '/../cache/last_sim.txt';
    
    // Création du dossier cache si inexistant
    if (!is_dir(__DIR__ . '/../cache')) {
        mkdir(__DIR__ . '/../cache', 0777, true);
    }

    if (file_exists($cacheFile)) {
        $lastSim = (int)file_get_contents($cacheFile);
        if (($now - $lastSim) < 5) {
            return false; // Trop récent, on ignore
        }
    }
    
    file_put_contents($cacheFile, $now);

    /* 
     * -- PHASE 0 : CHARGEMENT DES PARAMETRES --
     */
    $stmtP = $pdo->query("SELECT valeur FROM parametres_systeme WHERE cle = 'vitesse_simulation'");
    $vitesse = (float)($stmtP->fetchColumn() ?: 1.0);

    /* 
     * -- PHASE 1 : FLUCTUATION DES CAPTEURS --
     */
    $stmt = $pdo->query("SELECT * FROM equipements WHERE type = 'capteur' AND id != 7"); // On ignore le capteur intrusion ici
    $capteurs = $stmt->fetchAll();

    foreach ($capteurs as $capteur) {
        // Dérive aléatoire (±0.5 maximum, multipliée par la vitesse)
        $changement = (mt_rand(-50, 50) / 100) * $vitesse;
        
        // Comportement spécifique : La batterie décline lentement
        if ($capteur['icone'] === 'battery-medium') {
            $changement = - (mt_rand(1, 2) / 100) * $vitesse; 
        }

        $nouvelleValeur = max(0, (float)$capteur['valeur_actuelle'] + $changement);
        
        // Logique de statut basique
        $nouveauStatut = 'normal';
        if ($capteur['seuil_min'] !== null && $nouvelleValeur < $capteur['seuil_min']) {
            $nouveauStatut = 'critique';
        } elseif ($capteur['seuil_max'] !== null && $nouvelleValeur > $capteur['seuil_max']) {
            $nouveauStatut = 'critique';
        }

        // Mise à jour de l'équipement
        $pdo->prepare("UPDATE equipements SET valeur_actuelle = ?, statut = ? WHERE id = ?")
            ->execute([$nouvelleValeur, $nouveauStatut, $capteur['id']]);

        // Archivage historique (1 échantillon sur 3 pour ne pas saturer)
        if (mt_rand(1, 3) === 1) {
            $pdo->prepare("INSERT INTO historique_donnees (equipement_id, valeur) VALUES (?, ?)")
                ->execute([$capteur['id'], $nouvelleValeur]);
        }

        // Déclenchement d'alertes si critique et statut a changé (Anti-spam)
        if ($nouveauStatut === 'critique' && $capteur['statut'] !== 'critique') {
            $msg = "Alerte : " . $capteur['nom'] . " hors seuils (" . $nouvelleValeur . $capteur['unite'] . ")";
            generateAlertIfNotRecent($pdo, $capteur['id'], 'critique', $msg);
        }
    }

    /* 
     * -- PHASE 2 : SIMULATION D'INTRUSION (EVENEMENT RARE) --
     */
    if (mt_rand(1, 50) === 1) { // 2% de chances par cycle
        $pdo->prepare("UPDATE equipements SET valeur_actuelle = 1, statut = 'critique' WHERE id = 7")->execute();
        generateAlertIfNotRecent($pdo, 7, 'critique', 'SÉCURITÉ : Mouvement détecté Zone A !');
    } else {
        // Après 30 secondes d'intrusion, on repasse en normal (reset automatique)
        $pdo->prepare("UPDATE equipements SET valeur_actuelle = 0, statut = 'normal' WHERE id = 7 AND mis_a_jour_le < DATE_SUB(NOW(), INTERVAL 30 SECOND)")->execute();
    }

    return true;
}

/**
 * Empêche de spammer la table des alertes.
 * LOGIQUE PROFESSIONNELLE :
 * Une alerte n'est générée que s'il n'y a AUCUNE alerte non lue pour cet équipement.
 * Cela force l'utilisateur à traiter (ou supprimer) l'alerte avant d'en recevoir une nouvelle identique.
 */
function generateAlertIfNotRecent($pdo, $equipId, $niveau, $message) {
    $stmt = $pdo->prepare("SELECT id FROM alertes WHERE equipement_id = ? AND est_lu = 0 LIMIT 1");
    $stmt->execute([$equipId]);
    if ($stmt->rowCount() === 0) {
        $pdo->prepare("INSERT INTO alertes (equipement_id, niveau, message) VALUES (?, ?, ?)")
            ->execute([$equipId, $niveau, $message]);
    }
}
