<?php
// Fichier: includes/nav.php
// Variable attendue avant inclusion: $active_nav ('accueil', 'alertes', 'equipements', 'reglages')
if (!isset($active_nav)) {
    $active_nav = 'accueil';
}

function getNavClass($item, $active) {
    return ($item === $active) ? 'nav-item active w-16' : 'nav-item w-16';
}

function getNavInnerContainer($item, $active) {
    if ($item === $active) {
        return '<div class="bg-brand-green-light rounded-xl p-1.5 flex items-center justify-center">';
    }
    return '<div class="p-1.5 flex items-center justify-center">';
}

function getNavIconClass($item, $active, $baseIcons) {
    if ($item === $active) {
        return $baseIcons . ' text-green-500';
    }
    return $baseIcons;
}
?>
    <!-- BOTTOM NAVIGATION -->
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
