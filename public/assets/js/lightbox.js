/* =========================================================================
   Lightbox galleria (JavaScript vanilla): apertura fullscreen dalle foto
   dell'annuncio, con frecce, tastiera (←/→/Esc) e swipe touch.
   ========================================================================= */
(function () {
    'use strict';

    var gallery = document.querySelector('[data-gallery]');
    if (!gallery) { return; }

    var images = [];
    try { images = JSON.parse(gallery.getAttribute('data-images') || '[]'); } catch (e) { images = []; }
    if (!images.length) { return; }

    var overlay, imgEl, capEl, counterEl, current = 0;

    function build() {
        overlay = document.createElement('div');
        overlay.className = 'lightbox';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Galleria foto');
        overlay.innerHTML =
            '<button class="lb-close" type="button" aria-label="Chiudi">&times;</button>' +
            '<button class="lb-nav lb-prev" type="button" aria-label="Foto precedente">&#8249;</button>' +
            '<figure class="lb-figure">' +
            '<img class="lb-img" alt="">' +
            '<figcaption class="lb-caption"></figcaption>' +
            '</figure>' +
            '<button class="lb-nav lb-next" type="button" aria-label="Foto successiva">&#8250;</button>' +
            '<span class="lb-counter"></span>';
        document.body.appendChild(overlay);

        imgEl = overlay.querySelector('.lb-img');
        capEl = overlay.querySelector('.lb-caption');
        counterEl = overlay.querySelector('.lb-counter');

        overlay.querySelector('.lb-close').addEventListener('click', close);
        overlay.querySelector('.lb-prev').addEventListener('click', function () { go(-1); });
        overlay.querySelector('.lb-next').addEventListener('click', function () { go(1); });
        overlay.addEventListener('click', function (e) { if (e.target === overlay) { close(); } });

        var startX = 0;
        overlay.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
        overlay.addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 45) { go(dx < 0 ? 1 : -1); }
        }, { passive: true });
    }

    function show(i) {
        current = (i + images.length) % images.length;
        var it = images[current];
        imgEl.classList.remove('is-ready');
        imgEl.onload = function () { imgEl.classList.add('is-ready'); };
        imgEl.src = it.src;
        imgEl.alt = it.caption || '';
        capEl.textContent = it.caption || '';
        capEl.style.display = it.caption ? '' : 'none';
        counterEl.textContent = (current + 1) + ' / ' + images.length;
        var single = images.length < 2;
        overlay.querySelector('.lb-prev').style.display = single ? 'none' : '';
        overlay.querySelector('.lb-next').style.display = single ? 'none' : '';
    }

    function go(step) { show(current + step); }

    function open(i) {
        if (!overlay) { build(); }
        show(i || 0);
        overlay.classList.add('is-open');
        document.body.classList.add('no-scroll');
        document.addEventListener('keydown', onKey);
    }

    function close() {
        if (!overlay) { return; }
        overlay.classList.remove('is-open');
        document.body.classList.remove('no-scroll');
        document.removeEventListener('keydown', onKey);
    }

    function onKey(e) {
        if (e.key === 'Escape') { close(); }
        else if (e.key === 'ArrowLeft') { go(-1); }
        else if (e.key === 'ArrowRight') { go(1); }
    }

    gallery.querySelectorAll('[data-gallery-item]').forEach(function (item) {
        item.addEventListener('click', function () {
            open(parseInt(item.getAttribute('data-index') || '0', 10));
        });
    });
    var openBtn = gallery.querySelector('[data-gallery-open]');
    if (openBtn) { openBtn.addEventListener('click', function () { open(0); }); }
})();
