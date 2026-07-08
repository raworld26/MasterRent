/* =========================================================================
   Micro-interazioni UI (JavaScript vanilla):
   - toggle tema chiaro/scuro (preferenza in localStorage)
   - stato "loading" sui bottoni submit
   - blur-up / reveal delle immagini al caricamento
   - menu utente a tendina nell'header
   ========================================================================= */
(function () {
    'use strict';

    /* ---- Tema chiaro/scuro ----
       Il valore iniziale è applicato da uno script inline in <head>
       (per evitare flash); qui gestiamo solo il toggle e il salvataggio. */
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('masterent-theme', next); } catch (e) { /* storage non disponibile */ }
        });
    });

    /* ---- Stato loading sui submit (dopo la validazione client) ---- */
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) { return; }
            var btn = form.querySelector('button[type="submit"], button:not([type])');
            if (!btn || btn.classList.contains('is-loading')) { return; }
            btn.classList.add('is-loading');
            btn.disabled = true;
            // Fallback: se la navigazione non parte, riabilita il bottone.
            setTimeout(function () { btn.disabled = false; btn.classList.remove('is-loading'); }, 8000);
        });
    });

    /* ---- Reveal immagini (skeleton shimmer -> fade-in) ---- */
    function reveal(img) {
        var media = img.closest('.is-loading');
        if (media) { media.classList.remove('is-loading'); }
        img.classList.add('is-loaded');
    }
    document.querySelectorAll('img[data-img-reveal]').forEach(function (img) {
        if (img.complete && img.naturalWidth > 0) { reveal(img); }
        else {
            img.addEventListener('load', function () { reveal(img); });
            img.addEventListener('error', function () { reveal(img); });
        }
    });

    /* ---- Menu utente (header) ---- */
    var menu = document.querySelector('[data-user-menu]');
    if (menu) {
        var trigger = menu.querySelector('.user-menu-btn');

        function setOpen(open) {
            menu.classList.toggle('is-open', open);
            trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        trigger.addEventListener('click', function () {
            setOpen(!menu.classList.contains('is-open'));
        });
        document.addEventListener('click', function (event) {
            if (!menu.contains(event.target)) { setOpen(false); }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && menu.classList.contains('is-open')) {
                setOpen(false);
                trigger.focus();
            }
        });
    }
})();
