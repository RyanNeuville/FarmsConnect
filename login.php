<?php
/*
 * Fichier : login.php
 * Contrôleur d'authentification utilisateur.
 * Gère le formulaire de connexion et la vérification cryptographique des identifiants (Bcrypt).
 */
require_once 'config/db.php';
require_once 'includes/auth.php';

/* Barrière de sécurité : empêche un utilisateur authentifié d'atteindre le formulaire */
rediriger_si_connecte();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        /* 
         * Recherche sécurisée de l'utilisateur par adresse électronique 
         * via une requête préparée prévenant les attaques par injection SQL.
         */
        $stmt = $pdo->prepare('SELECT id, nom, mot_de_passe, email FROM utilisateurs WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        /* 
         * Vérification du hashage cryptographique du mot de passe fourni 
         * avec l'entrelacement stocké de manière sécurisée en base.
         */
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            /* Initialisation de l'environnement de session applicatif de l'opérateur */
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            
            /* Routage automatique vers le tableau de bord post-authentification réussie */
            header('Location: index.php');
            exit;
        } else {
            $error_message = 'Identifiants incorrects.';
        }
    } else {
        $error_message = 'Veuillez remplir tous les champs.';
    }
}
?>
<?php 
/* Intégration du composant de dépendances UI */
require_once 'includes/functions.php';

$page_title = 'FarmsConnect - Connexion';
$body_class = 'flex flex-col h-[100dvh] overflow-hidden login-bg pt-safe pb-safe';
$hide_main = true;

require 'includes/header.php';
?>

    <!-- BARRE D'ENTÊTE SECONDAIRE (TOP HEADER) -->
    <div class="p-6 flex justify-end">
      <button class="bg-white border border-slate-200 text-slate-500 font-bold text-[10px] uppercase tracking-wider px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-sm">
        <i data-lucide="help-circle" class="w-3.5 h-3.5"></i> Aide
      </button>
    </div>

    <!-- CONTENEUR PRINCIPAL D'AUTHENTIFICATION (MAIN CONTENT) -->
    <main class="flex-1 flex flex-col justify-center px-8 max-w-md mx-auto w-full">
      <!-- LOGO -->
      <div class="flex flex-col items-center mb-10">
        <div class="w-20 h-20 mb-4 rounded-3xl shadow-lg shadow-green-500/20 overflow-hidden flex items-center justify-center bg-white border border-slate-100">
          <img src="assets/icon.png" alt="FarmsConnect Logo" class="w-20 h-20 object-contain" />
        </div>
        <h1 class="text-3xl font-black text-brand-dark dark:text-white tracking-tight">FarmsConnect</h1>
        <p class="text-sm font-bold text-slate-400 mt-1">Votre exploitation au bout des doigts</p>
      </div>

      <!-- ZONE D'AFFICHAGE DES ERREURS D'AUTHENTIFICATION (ERROR MESSAGE) -->
      <?php if (!empty($error_message)): ?>
      <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold flex items-center gap-2">
          <i data-lucide="alert-circle" class="w-4 h-4"></i>
          <?= htmlspecialchars($error_message) ?>
      </div>
      <?php endif; ?>

      <!-- FORMULAIRE DE CONNEXION (LOGIN FORM) -->
      <form action="login.php" method="POST" class="space-y-4">
        <div class="relative input-group">
          <i data-lucide="mail" class="w-5 h-5 input-icon"></i>
          <input type="email" name="email" placeholder="Adresse e-mail" required class="input-field shadow-sm" />
        </div>

        <div class="relative input-group">
          <i data-lucide="lock" class="w-5 h-5 input-icon"></i>
          <input type="password" name="password" placeholder="Mot de passe" required class="input-field shadow-sm" />
          <button type="button" onclick="document.querySelector('input[name=password]').type = document.querySelector('input[name=password]').type === 'password' ? 'text' : 'password';" class="absolute top-1/2 right-4 -translate-y-1/2 text-slate-400 p-1">
            <i data-lucide="eye" class="w-4 h-4"></i>
          </button>
        </div>

        <div class="flex justify-end mb-2">
          <a href="#" class="text-xs font-bold text-green-600 hover:text-green-700">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="w-full bg-[#16a34a] text-white font-black text-[15px] py-4 rounded-2xl shadow-lg shadow-green-600/30 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
          Se connecter <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </button>
      </form>

      <!-- SÉPARATEUR VISUEL (DIVIDER) -->
      <div class="flex items-center gap-4 my-8">
        <div class="flex-1 h-px bg-slate-200"></div>
        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ou utiliser</span>
        <div class="flex-1 h-px bg-slate-200"></div>
      </div>

      <!-- BOUTONS D'IDENTIFICATION BIOMÉTRIQUE (BIOMETRICS) -->
      <div class="flex gap-3">
        <button class="flex-1 bg-white border border-slate-200 font-bold text-slate-600 text-sm py-3.5 rounded-2xl shadow-sm flex items-center justify-center gap-2 active:bg-slate-50 transition-colors">
          <i data-lucide="fingerprint" class="w-5 h-5 text-blue-500"></i> Touch ID
        </button>
      </div>
    </main>

    <!-- PIED DE PAGE D'AUTHENTIFICATION (FOOTER) -->
    <div class="p-6 text-center">
      <a href="index.php" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm">
        <i data-lucide="play" class="w-3.5 h-3.5 text-orange-500"></i>
        Essayer le Mode Démo
      </a>
    </div>

<?php
require 'includes/footer.php';
?>
