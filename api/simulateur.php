<?php
/* Point d'entrée pour les requêtes AJAX de simulation */
if (isset($_GET['ajax'])) {
    runSimulation($pdo);
    exit;
}

function runSimulation($pdo) {
    /* -- PHASE 1 : Analyse des deltas de mesure par application de bruit aléatoire -- */
    $stmt = $pdo->query("SELECT * FROM equipements WHERE type = 'capteur'");
    $capteurs = $stmt->fetchAll();

    foreach ($capteurs as $capteur) {
        $changement = mt_rand(-5, 5) / 10;
        if ($capteur['nom'] === 'Batterie Nord') {
            $changement = mt_rand(-5, -1) / 10;
        }
        
        $nouvelleValeur = max(0, $capteur['valeur_actuelle'] + $changement);
        
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
        }

        $pdo->prepare("UPDATE equipements SET valeur_actuelle = ?, statut = ? WHERE id = ?")
            ->execute([$nouvelleValeur, $nouveauStatut, $capteur['id']]);

        $pdo->prepare("INSERT INTO historique_donnees (equipement_id, valeur) VALUES (?, ?)")
            ->execute([$capteur['id'], $nouvelleValeur]);

        if ($niveauAlerte && $nouveauStatut !== $capteur['statut']) {
            $checkAlerte = $pdo->prepare("SELECT id FROM alertes WHERE equipement_id = ? AND cree_le > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
            $checkAlerte->execute([$capteur['id']]);
            if ($checkAlerte->rowCount() == 0) {
                $pdo->prepare("INSERT INTO alertes (equipement_id, niveau, message) VALUES (?, ?, ?)")
                    ->execute([$capteur['id'], $niveauAlerte, $messageAlerte]);
            }
        }
    }

    if (mt_rand(1, 10) <= 2) {
        $pdo->prepare("UPDATE equipements SET valeur_actuelle = 1, statut = 'critique' WHERE id = 7")->execute();
        $pdo->prepare("INSERT INTO alertes (equipement_id, niveau, message) VALUES (7, 'critique', 'ALERTE : Intrusion détectée dans la Zone A !')")
            ->execute();
    } else {
        $pdo->prepare("UPDATE equipements SET valeur_actuelle = 0, statut = 'normal' WHERE id = 7")->execute();
    }
}
?>
<?php
echo "<!DOCTYPE html><html><head><title>Simulateur FarmsConnect</title>";
echo "<style>body{font-family:sans-serif; text-align:center; padding:50px; background:#f0fdf4;} .btn{padding:15px 30px; font-weight:bold; cursor:pointer; border-radius:10px; border:none; color:white;} .start{background:#16a34a;} .stop{background:#dc2626; display:none;}</style>";
echo "</head><body>";
echo "<h1>Moteur de Simulation FarmsConnect</h1>";
echo "<p>Ce script met à jour la base de données en temps réel.</p>";
echo "<button id='toggleBtn' class='btn start'>Démarrer l'Auto-Pilote</button>";
echo "<button id='stopBtn' class='btn stop'>Arrêter l'Auto-Pilote</button>";
echo "<div id='status' style='margin-top:20px; font-weight:bold;'>État : En attente...</div>";

echo "<script>
let interval = null;
const status = document.getElementById('status');
const toggleBtn = document.getElementById('toggleBtn');
const stopBtn = document.getElementById('stopBtn');

function trigger() {
    fetch('simulateur.php?ajax=1')
        .then(() => {
            status.innerText = 'Dernière mise à jour : ' + new Date().toLocaleTimeString();
            status.style.color = '#16a34a';
        })
        .catch(err => console.error(err));
}

toggleBtn.onclick = () => {
    interval = setInterval(trigger, 3000);
    toggleBtn.style.display = 'none';
    stopBtn.style.display = 'inline-block';
    status.innerText = 'Auto-Pilote ACTIF (3s)...';
};

stopBtn.onclick = () => {
    clearInterval(interval);
    toggleBtn.style.display = 'inline-block';
    stopBtn.style.display = 'none';
    status.innerText = 'Auto-Pilote ARRÊTÉ.';
    status.style.color = 'black';
};
</script></body></html>";
?>
<?php
exit;
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
