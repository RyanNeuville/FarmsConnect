<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!est_connecte()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vitesse = $_POST['vitesse'] ?? '1.0';
    $stmt = $pdo->prepare("INSERT INTO parametres_systeme (cle, valeur) VALUES ('vitesse_simulation', ?) ON DUPLICATE KEY UPDATE valeur = ?");
    if ($stmt->execute([$vitesse, $vitesse])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la sauvegarde']);
    }
}
