<?php
/**
 * Helper API : Renvoie le HTML d'un bouton d'actionneur selon son statut.
 * Utilisé pour la mise à jour dynamique sans rechargement.
 */
require_once '../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$statut = isset($_GET['statut']) ? $_GET['statut'] : 'arret';

echo getActionButton($statut, $id);
