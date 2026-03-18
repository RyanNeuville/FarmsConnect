<?php
/*
 * Fichier : includes/nav.php
 * Composant de navigation inférieur fixe (Bottom Navigation Bar) de l'application.
 * 
 * Variable attendue avant l'inclusion :
 * @var string $active_nav Identifiant de l'onglet actif ('accueil', 'alertes', 'equipements', 'reglages')
 */
if (!isset($active_nav)) {
    $active_nav = 'accueil';
}

/**
 * Détermine la classe CSS de l'élément de navigation général selon son état actif.
 * 
 * @param string $item L'identifiant clé de cet onglet
 * @param string $active L'identifiant de l'onglet actuellement actif
 * @return string La classe CSS finale pour le rendu
 */
function getNavClass($item, $active) {
    return ($item === $active) ? 'nav-item active w-16' : 'nav-item w-16';
}

/**
 * Renvoie le conteneur HTML stylisé spécifiquement si l'onglet est actif
 * (intégration d'un fond de surbrillance asymétrique).
 * 
 * @param string $item
 * @param string $active
 * @return string Conteneur div interne
 */
function getNavInnerContainer($item, $active) {
    if ($item === $active) {
        return '<div class="bg-brand-green-light rounded-xl p-1.5 flex items-center justify-center">';
    }
    return '<div class="p-1.5 flex items-center justify-center">';
}

/**
 * Détermine la couleur contextuelle de l'icône vectorielle Lucide.
 * 
 * @param string $item
 * @param string $active
 * @param string $baseIcons Les classes de base structurelles de l'icône
 * @return string
 */
function getNavIconClass($item, $active, $baseIcons) {
    if ($item === $active) {
        return $baseIcons . ' text-green-500';
    }
    return $baseIcons;
}
?>
    <!-- COMPOSANT DE NAVIGATION BASSE (BOTTOM NAVIGATION) -->
    <nav class="absolute bottom-0 w-full bottom-nav pt-3 pb-safe z-50">
      <ul class="flex justify-around items-center px-2">
        <li>
          <a href="index.php" class="<?= getNavClass('accueil', $active_nav) ?>">
            <?= getNavInnerContainer('accueil', $active_nav) ?>
              <i data-lucide="home" class="<?= getNavIconClass('accueil', $active_nav, 'w-5 h-5') ?>"></i>
            </div>
            <span>Accueil</span>
          </a>
        </li>
        <li>
          <a href="alertes.php" class="<?= getNavClass('alertes', $active_nav) ?>">
            <?= getNavInnerContainer('alertes', $active_nav) ?>
              <i data-lucide="bell" class="<?= getNavIconClass('alertes', $active_nav, 'w-5 h-5') ?>"></i>
            </div>
            <span>Alertes</span>
          </a>
        </li>
        <li>
          <a href="equipements.php" class="<?= getNavClass('equipements', $active_nav) ?>">
            <?= getNavInnerContainer('equipements', $active_nav) ?>
              <i data-lucide="tractor" class="<?= getNavIconClass('equipements', $active_nav, 'w-5 h-5') ?>"></i>
            </div>
            <span>Équipements</span>
          </a>
        </li>
        <li>
          <a href="reglages.php" class="<?= getNavClass('reglages', $active_nav) ?>">
            <?= getNavInnerContainer('reglages', $active_nav) ?>
              <i data-lucide="settings" class="<?= getNavIconClass('reglages', $active_nav, 'w-5 h-5') ?>"></i>
            </div>
            <span>Réglages</span>
          </a>
        </li>
      </ul>
    </nav>
