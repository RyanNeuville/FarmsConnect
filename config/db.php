<?php
/*
 * Fichier : config/db.php
 * Gestion de la connexion à la base de données via l'interface PDO (PHP Data Objects).
 * Ce script est conçu pour être inclus au tout début du cycle de vie de la requête.
 */

$host = 'localhost';
$db_name = 'farmsconnect';
$username = 'root'; /* Paramètre à modifier selon les identifiants de sécurité du serveur MySQL */
$password = ''; /* Paramètre à modifier avec le mot de passe de la base de données en production */

try {
    /* 
     * Définition des options PDO : 
     * - Activation des exceptions en cas d'erreur pour un débogage robuste (ERRMODE_EXCEPTION)
     * - Retour des résultats sous forme de tableau associatif par défaut (FETCH_ASSOC)
     * - Désactivation de l'émulation des requêtes préparées pour la sécurité native MySQL
     */
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    /* 
     * Interception de l'exception pour masquer les détails techniques (credentials) à l'utilisateur final.
     * En situation de production MVP, un message générique est affiché.
     */
    die("<h1>Erreur critique</h1><p>Impossible de se connecter à la base de données. L'équipe technique a été notifiée.</p>");
}
?>
