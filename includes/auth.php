<?php
// Fichier: includes/auth.php
// Fonctions d'aide pour l'authentification et la gestion de la session

session_start();

/**
 * Vérifie si l'utilisateur est connecté.
 * @return bool
 */
function est_connecte() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirige l'utilisateur vers la page de login s'il n'est pas connecté.
 * Obligatoire sur toutes les pages sécurisées (dashboard, réglages, capteurs).
 */
function forcer_connexion() {
    // Si on n'est pas sur la page login et qu'on n'est pas connecté
    if (!est_connecte() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirige l'utilisateur vers l'accueil s'il est déjà connecté et essaie
 * de voir la page de connexion.
 */
function rediriger_si_connecte() {
    if (est_connecte() && basename($_SERVER['PHP_SELF']) === 'login.php') {
        header('Location: index.php');
        exit;
    }
}
?>
