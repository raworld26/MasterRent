/* =========================================================================
   Toast / notifiche (JavaScript vanilla, nessuna libreria).
   API globale: window.toast(messaggio, tipo)  tipo = success|info|warning|danger
   ========================================================================= */
(function () {
    'use strict';

    var ICONS = {
        success: 'M20 6 9 17l-5-5',
        info: 'M12 8h.01M11 12h1v4h1',
        warning: 'M12 9v4M12 17h.01',
        danger: 'M15 9l-6 6M9 9l6 6'
    };

    function stack() {
        var el = document.getElementById('toast-stack');
        if (!el) {
            el = document.createElement('div');
            el.id = 'toast-stack';
            el.className = 'toast-stack';
            el.setAttribute('aria-live', 'polite');
            document.body.appendChild(el);
        }
        return el;
    }

    function dismiss(toast) {
        if (!toast || toast.classList.contains('is-leaving')) { return; }
        toast.classList.add('is-leaving');
        toast.addEventListener('transitionend', function () { toast.remove(); }, { once: true });
        setTimeout(function () { if (toast.parentNode) { toast.remove(); } }, 400);
    }

    window.toast = function (message, type) {
        if (!message) { return; }
        type = ICONS[type] ? type : 'info';

        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.setAttribute('role', type === 'danger' ? 'alert' : 'status');

        toast.innerHTML =
            '<span class="toast-icon" aria-hidden="true">' +
            '<svg viewBox="0 0 24 24"><path d="' + ICONS[type] + '"/></svg></span>' +
            '<span class="toast-msg"></span>' +
            '<button class="toast-close" type="button" aria-label="Chiudi">&times;</button>';
        toast.querySelector('.toast-msg').textContent = message;

        var host = stack();
        host.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add('is-in'); });

        toast.querySelector('.toast-close').addEventListener('click', function () { dismiss(toast); });
        var timer = setTimeout(function () { dismiss(toast); }, 4200);
        toast.addEventListener('mouseenter', function () { clearTimeout(timer); });
        toast.addEventListener('mouseleave', function () { timer = setTimeout(function () { dismiss(toast); }, 1800); });

        return toast;
    };

    /* Promuove i flash server (contenitore data-flashes) a toast.
       Senza JS gli avvisi restano inline: progressive enhancement. */
    document.addEventListener('DOMContentLoaded', function () {
        var area = document.querySelector('[data-flashes]');
        if (!area) { return; }
        var alerts = area.querySelectorAll('[data-flash]');
        alerts.forEach(function (a) {
            window.toast(a.textContent.trim(), a.getAttribute('data-flash'));
        });
        area.remove();
    });
})();
