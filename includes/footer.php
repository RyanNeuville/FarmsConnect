<?php
/*
 * Fichier : includes/footer.php
 * Ferme la structure HTML globale et initialise la configuration du rendu des icônes côté client.
 */
if (!isset($hide_main)): ?>
    </main>
<?php endif; ?>

    <!-- SYSTÈME DE TOAST GLOBAL -->
    <div id="toast-container" class="fixed top-5 left-1/2 -translate-x-1/2 z-[9999] flex flex-col gap-2 w-[90%] max-w-sm pointer-events-none"></div>

    <!-- MODAL DE CONFIRMATION GLOBAL -->
    <div id="confirm-overlay" class="fixed inset-0 z-[9998] bg-black/50 backdrop-blur-sm hidden flex items-end justify-center p-4">
      <div id="confirm-modal" class="w-full max-w-sm bg-white dark:bg-slate-800 rounded-2xl shadow-2xl p-5 pointer-events-auto transform transition-all duration-300 translate-y-4 opacity-0">
        <p id="confirm-message" class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-5 text-center leading-relaxed"></p>
        <div class="flex gap-3">
          <button id="confirm-cancel" class="flex-1 py-3 rounded-xl border border-slate-200 dark:border-slate-600 text-slate-500 dark:text-slate-400 font-bold text-sm">Annuler</button>
          <button id="confirm-ok" class="flex-1 py-3 rounded-xl bg-red-500 text-white font-black text-sm">Confirmer</button>
        </div>
      </div>
    </div>

    <script>
      lucide.createIcons();

      // ─── SYSTÈME DE TOAST ───────────────────────────────────────────────────
      function showToast(message, type = 'success') {
        const icons = { success: 'check-circle', error: 'x-circle', warning: 'alert-triangle', info: 'info' };
        const colors = {
          success: 'bg-green-500',
          error:   'bg-red-500',
          warning: 'bg-orange-400',
          info:    'bg-blue-500'
        };

        const toast = document.createElement('div');
        toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl text-white text-[13px] font-bold ${colors[type]} transform transition-all duration-300 translate-y-[-8px] opacity-0`;
        toast.innerHTML = `
          <i data-lucide="${icons[type]}" class="w-4 h-4 shrink-0"></i>
          <span class="flex-1">${message}</span>
        `;

        const container = document.getElementById('toast-container');
        container.appendChild(toast);
        if (window.lucide) lucide.createIcons();

        // Animation entrée
        requestAnimationFrame(() => {
          toast.classList.remove('translate-y-[-8px]', 'opacity-0');
        });

        // Suppression automatique après 3.5s
        setTimeout(() => {
          toast.classList.add('translate-y-[-8px]', 'opacity-0');
          setTimeout(() => toast.remove(), 300);
        }, 3500);
      }

      // ─── MODAL DE CONFIRMATION ───────────────────────────────────────────────
      function showConfirm(message, onConfirm) {
        const overlay = document.getElementById('confirm-overlay');
        const modal   = document.getElementById('confirm-modal');
        const msg     = document.getElementById('confirm-message');

        msg.textContent = message;
        overlay.classList.remove('hidden');
        overlay.style.display = 'flex';

        requestAnimationFrame(() => {
          modal.classList.remove('translate-y-4', 'opacity-0');
        });

        function close() {
          modal.classList.add('translate-y-4', 'opacity-0');
          setTimeout(() => { overlay.style.display = 'none'; overlay.classList.add('hidden'); }, 300);
        }

        document.getElementById('confirm-cancel').onclick = close;
        document.getElementById('confirm-ok').onclick = function() {
          close();
          onConfirm();
        };
        overlay.onclick = function(e) { if (e.target === overlay) close(); };
      }
    </script>
</body>
</html>
