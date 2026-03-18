<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

// Rediriger vers index si déjà connecté
rediriger_si_connecte();

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        // Recherche de l'utilisateur par email
        $stmt = $pdo->prepare('SELECT id, nom, mot_de_passe FROM utilisateurs WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Vérification du mot de passe
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Création de la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            
            // Redirection vers le tableau de bord
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
<!doctype html>
<html lang="fr" class="antialiased">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <title>FarmsConnect - Connexion</title>

    <meta name="theme-color" content="#ffffff" />
    <link rel="manifest" href="manifest.json" />
    <link rel="apple-touch-icon" href="assets/icon.svg" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="css/app.css" />

    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              green: { 500: "#22c55e", 600: "#16a34a" },
              slate: { 50: "#f8fafc", 100: "#f1f5f9", 400: "#94a3b8", 800: "#1e293b" },
            },
            fontFamily: { sans: ["Nunito", "sans-serif"] },
          },
        },
      };
    </script>
    <style>
      .login-bg { background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); }
      .input-field {
        width: 100%;
        padding: 14px 16px 14px 44px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        background-color: #ffffff;
        font-weight: 700;
        color: #0f2b46;
        outline: none;
        transition: all 0.2s;
      }
      .input-field:focus {
        border-color: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
      }
      .input-icon {
        position: absolute;
        top: 50%;
        left: 16px;
        transform: translateY(-50%);
        color: #94a3b8;
        transition: color 0.2s;
      }
      .input-group:focus-within .input-icon { color: #22c55e; }
    </style>
</head>
<body class="flex flex-col h-[100dvh] overflow-hidden login-bg pt-safe pb-safe">

    <!-- TOP HEADER -->
    <div class="p-6 flex justify-end">
      <button class="bg-white border border-slate-200 text-slate-500 font-bold text-[10px] uppercase tracking-wider px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-sm">
        <i data-lucide="help-circle" class="w-3.5 h-3.5"></i> Aide
      </button>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col justify-center px-8 max-w-md mx-auto w-full">
      <!-- LOGO -->
      <div class="flex flex-col items-center mb-10">
        <div class="w-20 h-20 mb-4 rounded-3xl shadow-lg shadow-green-500/20 overflow-hidden flex items-center justify-center bg-white border border-slate-100">
          <img src="assets/icon.svg" alt="FarmsConnect Logo" class="w-20 h-20 object-contain" />
        </div>
        <h1 class="text-3xl font-black text-[#0f2b46] tracking-tight">FarmsConnect</h1>
        <p class="text-sm font-bold text-slate-400 mt-1">Votre exploitation au bout des doigts</p>
      </div>

      <!-- ERROR MESSAGE -->
      <?php if (!empty($error_message)): ?>
      <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm font-bold flex items-center gap-2">
          <i data-lucide="alert-circle" class="w-4 h-4"></i>
          <?= htmlspecialchars($error_message) ?>
      </div>
      <?php endif; ?>

      <!-- LOGIN FORM -->
      <form action="login.php" method="POST" class="space-y-4">
        <div class="relative input-group">
          <i data-lucide="mail" class="w-5 h-5 input-icon"></i>
          <input type="email" name="email" placeholder="Adresse e-mail" required class="input-field shadow-sm" value="jean@ferme.fr" />
        </div>

        <div class="relative input-group">
          <i data-lucide="lock" class="w-5 h-5 input-icon"></i>
          <input type="password" name="password" placeholder="Mot de passe" required class="input-field shadow-sm" value="password123" />
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

      <!-- DIVIDER -->
      <div class="flex items-center gap-4 my-8">
        <div class="flex-1 h-px bg-slate-200"></div>
        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ou utiliser</span>
        <div class="flex-1 h-px bg-slate-200"></div>
      </div>

      <!-- BIOMETRICS -->
      <div class="flex gap-3">
        <button class="flex-1 bg-white border border-slate-200 font-bold text-slate-600 text-sm py-3.5 rounded-2xl shadow-sm flex items-center justify-center gap-2 active:bg-slate-50 transition-colors">
          <i data-lucide="fingerprint" class="w-5 h-5 text-blue-500"></i> Touch ID
        </button>
      </div>
    </main>

    <!-- FOOTER -->
    <div class="p-6 text-center">
      <a href="index.php" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm">
        <i data-lucide="play" class="w-3.5 h-3.5 text-orange-500"></i>
        Essayer le Mode Démo
      </a>
    </div>

    <script>
      lucide.createIcons();
    </script>
</body>
</html>
