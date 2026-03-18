<?php
/*
 * Fichier : api/action.php
 * Contrôleur d'API interne de gestion des actionneurs physiques.
 * Traite les instructions opérationnelles (marche/arrêt) et trace l'activité système.
 */
require_once '../config/db.php';
require_once '../includes/auth.php';

/* Support initial pour flux de données x-www-form-urlencoded (évolutif vers JSON REST) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    forcer_connexion();

    $equipement_id = isset($_POST['equipement_id']) ? (int)$_POST['equipement_id'] : 0;
    $action = isset($_POST['action']) ? (int)$_POST['action'] : 0; /* Index binaire opérationnel : 1 = Activation logicielle, 0 = Coupure */
    
    if ($equipement_id > 0) {
        $statut = ($action === 1) ? 'marche' : 'arret';
        
        /* Exécution de la transition d'état au sein de la matrice matérielle SQL */
        $stmt = $pdo->prepare("UPDATE equipements SET statut = ?, valeur_actuelle = ? WHERE id = ? AND type = 'actionneur'");
        $stmt->execute([$statut, $action, $equipement_id]);
        
        /* 
         * Génération d'une trace d'audit opérationnelle.
         * Stocke les commandes pour une synchronisation cloud potentielle en cas de perte de réseau (offline capability).
         */
        $pdo->prepare("INSERT INTO commandes_offline (equipement_id, nouvelle_valeur, synced) VALUES (?, ?, 1)")
            ->execute([$equipement_id, $action]);
    }

    /* Débranchement de la requête d'API et retour silencieux à l'interface d'origine de l'utilisateur */
    if (isset($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: ../actionneur.php?id=" . $equipement_id);
    }
    exit;
}
?>
