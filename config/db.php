<?php
// Fichier: config/db.php
// Gestion de la connexion à la base de données via PDO

$host = 'localhost';
$db_name = 'farmsconnect';
$username = 'root'; // À adapter selon ta configuration serveur
$password = ''; // À adapter selon ta configuration serveur

try {
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    // Dans un cas réel de MVP, on affiche une erreur propre sans divulguer les infos
    // d'identification MySQL.
    die("<h1>Erreur critique</h1><p>Impossible de se connecter à la base de données. L'équipe technique a été notifiée.</p>");
}
?>
