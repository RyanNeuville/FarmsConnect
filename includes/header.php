<?php
// Fichier: includes/header.php
// Variables attendues avant l'inclusion: $page_title, $body_class, $hide_main
if (!isset($page_title)) {
    $page_title = 'FarmsConnect';
}
if (!isset($body_class)) {
    $body_class = 'flex flex-col h-[100dvh] overflow-hidden bg-[#fafbfd]';
}
?>
<!doctype html>
<html lang="fr" class="antialiased">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <title><?= htmlspecialchars($page_title) ?></title>
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
              slate: { 50: "#f8fafc", 100: "#f1f5f9", 200: "#e2e8f0", 400: "#94a3b8", 500: "#64748b", 800: "#1e293b" },
            },
            fontFamily: { sans: ["Nunito", "sans-serif"] },
          },
        },
      };
    </script>
</head>
<body class="<?= htmlspecialchars($body_class) ?>">
<?php if (!isset($hide_main)): ?>
    <main class="flex-1 overflow-y-auto px-4 pb-24 pt-safe">
<?php endif; ?>
