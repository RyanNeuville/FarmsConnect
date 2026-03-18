<?php
/*
 * Fichier : includes/auth.php
 * Module centralisant les utilitaires de vérification et de session liés à l'authentification.
 */

session_start();

/**
 * Vérifie la présence d'un identifiant utilisateur persistant dans l'objet de session.
 * 
 * @return bool Retourne true si la session courante contient un identifiant valide.
 */
function est_connecte() {
    return isset($_SESSION['user_id']);
}

/**
 * Garantit l'accès aux pages soumises à une autorisation stricte (Dashboard, Réglages...).
 * Déclenche une redirection immédiate vers le formulaire d'identification en cas de session invalide.
 * 
 * @return void
 */
function forcer_connexion() {
    /* Prévention des boucles de redirection au niveau du routeur statique */
    if (!est_connecte() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: login.php');
        exit;
    }
}

/**
 * Entrave l'accès aux interfaces de connexion pour un utilisateur bénéficiant déjà 
 * d'un environnement de session établi. Le redirige vers le tableau de bord par défaut.
 * 
 * @return void
 */
function rediriger_si_connecte() {
    if (est_connecte() && basename($_SERVER['PHP_SELF']) === 'login.php') {
        header('Location: index.php');
        exit;
    }
}
?>
