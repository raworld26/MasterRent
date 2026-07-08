/* =========================================================================
   Ricerca live (JavaScript vanilla): filtro rapido testuale, filtro per
   distanza massima dai poli e ordinamento, applicati istantaneamente sulle
   card già caricate. La ricerca server-side (form GET) resta il fallback.
   ========================================================================= */
(function () {
    'use strict';

    var grid = document.querySelector('[data-room-grid]');
    if (!grid) { return; }

    var cards = Array.prototype.slice.call(grid.querySelectorAll('.room-card'));
    var quick = document.getElementById('quick-filter');
    var distance = document.getElementById('max-distance');
    var distanceOut = document.getElementById('max-distance-value');
    var sortSel = document.getElementById('client-sort');
    var counter = document.querySelector('[data-result-count]');
    var liveEmpty = document.querySelector('[data-live-empty]');
    var skeleton = document.querySelector('[data-search-skeleton]');
    var skeletonTimer = null;
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Skeleton loader: mostra i segnaposto per un attimo mentre la lista
       viene ricalcolata, così l'aggiornamento è percepibile. */
    function withSkeleton(update) {
        if (!skeleton || reducedMotion) { update(); return; }
        grid.setAttribute('aria-busy', 'true');
        grid.hidden = true;
        skeleton.hidden = false;
        clearTimeout(skeletonTimer);
        skeletonTimer = setTimeout(function () {
            update();
            skeleton.hidden = true;
            grid.hidden = false;
            grid.removeAttribute('aria-busy');
        }, 220);
    }

    function updateDistanceDisplay() {
        if (!distance) { return; }
        var value = parseInt(distance.value, 10);
        var min = parseInt(distance.min || '0', 10);
        var max = parseInt(distance.max || '100', 10);
        var percent = max > min ? ((value - min) / (max - min)) * 100 : 100;

        distance.style.setProperty('--range-progress', Math.max(0, Math.min(100, percent)) + '%');
        if (distanceOut) {
            distanceOut.textContent = value >= 60 ? 'qualsiasi' : (value + ' min');
        }
    }

    function apply() {
        var text = (quick && quick.value || '').trim().toLowerCase();
        var maxDist = distance ? parseInt(distance.value, 10) : 999;
        var visible = 0;

        cards.forEach(function (card) {
            var hay = card.getAttribute('data-search') || '';
            var dist = parseInt(card.getAttribute('data-distance') || '999', 10);
            var matchText = text === '' || hay.indexOf(text) !== -1;
            var matchDist = isNaN(maxDist) || maxDist >= 60 || dist <= maxDist;
            var show = matchText && matchDist;
            card.style.display = show ? '' : 'none';
            if (show) { visible++; }
        });

        if (counter) { counter.textContent = visible; }
        if (liveEmpty) {
            liveEmpty.hidden = !(cards.length > 0 && visible === 0);
        }
        updateDistanceDisplay();
    }

    function sortCards() {
        if (!sortSel) { return; }
        var mode = sortSel.value;
        var sorted = cards.slice().sort(function (a, b) {
            var pa = parseFloat(a.getAttribute('data-price') || '0');
            var pb = parseFloat(b.getAttribute('data-price') || '0');
            var da = parseInt(a.getAttribute('data-distance') || '999', 10);
            var db = parseInt(b.getAttribute('data-distance') || '999', 10);
            var ca = parseInt(a.getAttribute('data-created') || '0', 10);
            var cb = parseInt(b.getAttribute('data-created') || '0', 10);
            if (mode === 'price_desc') { return pb - pa; }
            if (mode === 'distance') { return da - db; }
            if (mode === 'newest') { return cb - ca; }
            return pa - pb;
        });
        sorted.forEach(function (card) { grid.appendChild(card); });
    }

    if (quick) { quick.addEventListener('input', function () { withSkeleton(apply); }); }
    if (distance) {
        // Il feedback del cursore resta immediato; lo skeleton solo a rilascio.
        distance.addEventListener('input', apply);
    }
    if (sortSel) { sortSel.addEventListener('change', function () { withSkeleton(function () { sortCards(); apply(); }); }); }

    apply();
})();
