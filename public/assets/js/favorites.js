/* =========================================================================
   Preferiti — toggle asincrono (JavaScript vanilla, fetch API).
   Il salvataggio e' disponibile solo per account studente autenticati.
   ========================================================================= */
(function () {
    'use strict';

    function token() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function endpoint() {
        return document.body.getAttribute('data-favorite-url') || 'favorite.php';
    }

    function isStudent() {
        return document.body.getAttribute('data-is-student') === '1';
    }

    function notifyBlocked() {
        if (window.toast) {
            window.toast('Accedi con un account studente per salvare nei preferiti.', 'info');
        }
    }

    function toggle(btn) {
        var roomId = btn.getAttribute('data-room-id');
        if (!roomId || btn.classList.contains('is-loading')) { return; }

        if (!isStudent()) {
            notifyBlocked();
            return;
        }

        btn.classList.add('is-loading');

        var body = new URLSearchParams();
        body.append('room_id', roomId);
        body.append('csrf_token', token());

        fetch(endpoint(), {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body,
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.ok) {
                    btn.classList.toggle('is-fav', !!data.favorite);
                    btn.classList.add('just-toggled');
                    setTimeout(function () { btn.classList.remove('just-toggled'); }, 320);
                    btn.setAttribute('aria-pressed', data.favorite ? 'true' : 'false');
                    document.querySelectorAll('[data-fav-count]').forEach(function (el) {
                        el.textContent = data.count;
                    });
                    if (window.toast) {
                        window.toast(data.favorite ? 'Aggiunto ai preferiti' : 'Rimosso dai preferiti',
                            data.favorite ? 'success' : 'info');
                    }
                } else if (window.toast) {
                    if (data && (data.error === 'auth_required' || data.error === 'student_required')) {
                        notifyBlocked();
                    } else {
                        window.toast('Azione non riuscita. Riprova.', 'danger');
                    }
                }
            })
            .catch(function () {
                if (window.toast) { window.toast('Errore di rete. Riprova.', 'danger'); }
            })
            .finally(function () { btn.classList.remove('is-loading'); });
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-fav]');
        if (btn) {
            event.preventDefault();
            toggle(btn);
        }
    });
})();
