<?php
// Fichier: api/action.php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Optionnel: API json si AJAX, mais pour le moment on traite en formulaire POST classique
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    forcer_connexion();

    $equipement_id = isset($_POST['equipement_id']) ? (int)$_POST['equipement_id'] : 0;
    $action = isset($_POST['action']) ? (int)$_POST['action'] : 0; // 1 = marche, 0 = arret
    
    if ($equipement_id > 0) {
        $statut = ($action === 1) ? 'marche' : 'arret';
        
        // Mise à jour de la base de données
        $stmt = $pdo->prepare("UPDATE equipements SET statut = ?, valeur_actuelle = ? WHERE id = ? AND type = 'actionneur'");
        $stmt->execute([$statut, $action, $equipement_id]);
        
        // Ajout d'une ligne d'historique ou d'action (Optionnel pour MVP, on peut le faire pour tracker qui a fait l'action)
        $pdo->prepare("INSERT INTO commandes_offline (equipement_id, nouvelle_valeur, synced) VALUES (?, ?, 1)")
            ->execute([$equipement_id, $action]);
    }

    // Redirection vers la page précédente
    if (isset($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: ../actionneur.php?id=" . $equipement_id);
    }
    exit;
}
?>
