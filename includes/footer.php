<?php
/*
 * Fichier : includes/footer.php
 * Ferme la structure HTML globale et initialise la configuration du rendu des icônes côté client.
 */
if (!isset($hide_main)): ?>
    </main>
<?php endif; ?>

    <script>
      lucide.createIcons();
    </script>
</body>
</html>
