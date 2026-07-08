/* =========================================================================
   Controlli filtro: select custom progressive-enhancement e pannello mobile.
   Le <select> native restano nel DOM per form, fallback e compatibilita JS.
   ========================================================================= */
(function () {
    'use strict';

    var selectId = 0;
    var instances = [];

    function readableLabel(select) {
        var explicit = select.dataset.selectLabel || select.getAttribute('aria-label');
        if (explicit) { return explicit; }

        if (select.id) {
            var label = document.querySelector('label[for="' + select.id + '"]');
            if (label) { return label.textContent.trim(); }
        }

        var wrapped = select.closest('label');
        if (wrapped) {
            var fieldLabel = wrapped.querySelector('.field-label');
            if (fieldLabel) { return fieldLabel.textContent.trim(); }
        }

        return select.name || 'Menu';
    }

    function closeAll(except) {
        instances.forEach(function (instance) {
            if (instance !== except) {
                instance.close();
            }
        });
    }

    function enhanceSelect(select) {
        if (!select || select.dataset.enhancedSelect === '1') { return; }
        select.dataset.enhancedSelect = '1';
        select.classList.add('native-select', 'is-enhanced');

        var idBase = select.id || ('filter-select-' + (++selectId));
        if (!select.id) { select.id = idBase; }

        var root = document.createElement('div');
        root.className = 'custom-select';
        if (select.dataset.selectAlign === 'right') {
            root.classList.add('custom-select-right');
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'custom-select-trigger';
        button.disabled = select.disabled;
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');
        button.setAttribute('aria-controls', idBase + '-menu');
        button.setAttribute('aria-label', readableLabel(select));

        var text = document.createElement('span');
        text.className = 'custom-select-value';
        var chevron = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        chevron.setAttribute('viewBox', '0 0 24 24');
        chevron.setAttribute('aria-hidden', 'true');
        chevron.innerHTML = '<path d="m6 9 6 6 6-6"/>';
        button.appendChild(text);
        button.appendChild(chevron);

        var menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        menu.id = idBase + '-menu';
        menu.setAttribute('role', 'listbox');
        menu.setAttribute('aria-label', button.getAttribute('aria-label'));

        root.appendChild(button);
        root.appendChild(menu);
        select.insertAdjacentElement('afterend', root);

        function options() {
            return Array.prototype.slice.call(select.options);
        }

        function menuButtons() {
            return Array.prototype.slice.call(menu.querySelectorAll('.custom-select-option'));
        }

        function selectedOption() {
            return select.options[select.selectedIndex] || select.options[0];
        }

        function updateButton() {
            var option = selectedOption();
            text.textContent = option ? option.textContent : '';
            root.classList.toggle('has-value', !!(option && option.value !== ''));
        }

        function renderOptions() {
            menu.innerHTML = '';
            options().forEach(function (option, index) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'custom-select-option';
                item.setAttribute('role', 'option');
                item.id = idBase + '-option-' + index;
                item.dataset.value = option.value;
                item.textContent = option.textContent;
                if (option.value === '') {
                    item.classList.add('is-placeholder');
                }
                if (option.selected) {
                    item.classList.add('is-selected');
                    item.setAttribute('aria-selected', 'true');
                } else {
                    item.setAttribute('aria-selected', 'false');
                }

                item.addEventListener('click', function () {
                    choose(index);
                });
                item.addEventListener('keydown', function (event) {
                    var items = menuButtons();
                    var current = items.indexOf(item);
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        (items[current + 1] || items[0]).focus();
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        (items[current - 1] || items[items.length - 1]).focus();
                    } else if (event.key === 'Home') {
                        event.preventDefault();
                        items[0].focus();
                    } else if (event.key === 'End') {
                        event.preventDefault();
                        items[items.length - 1].focus();
                    } else if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        choose(current);
                    } else if (event.key === 'Escape') {
                        event.preventDefault();
                        instance.close();
                        button.focus();
                    }
                });

                menu.appendChild(item);
            });
        }

        function choose(index) {
            var option = select.options[index];
            if (!option || option.disabled) { return; }
            select.selectedIndex = index;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            renderOptions();
            updateButton();
            instance.close();
            button.focus();
        }

        var instance = {
            root: root,
            open: function () {
                closeAll(instance);
                root.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');
            },
            close: function () {
                root.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
            },
            toggle: function () {
                if (root.classList.contains('is-open')) {
                    instance.close();
                } else {
                    instance.open();
                }
            }
        };

        button.addEventListener('click', instance.toggle);
        button.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                instance.open();
                var items = menuButtons();
                var selected = menu.querySelector('.custom-select-option.is-selected');
                (selected || items[0]).focus();
            } else if (event.key === 'Escape') {
                instance.close();
            }
        });

        select.addEventListener('change', function () {
            renderOptions();
            updateButton();
        });

        renderOptions();
        updateButton();
        instances.push(instance);
    }

    function initMobileFilters() {
        var toggle = document.querySelector('[data-filter-toggle]');
        var panel = document.getElementById('search-filter-form');
        if (!toggle || !panel) { return; }

        function setOpen(open) {
            panel.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        toggle.addEventListener('click', function () {
            setOpen(!panel.classList.contains('is-open'));
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && panel.classList.contains('is-open')) {
                setOpen(false);
                toggle.focus();
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.custom-select')) {
            closeAll();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAll();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select:not([multiple]):not([data-native-select])').forEach(function (select) {
            var size = parseInt(select.getAttribute('size') || '1', 10);
            if (size <= 1) { enhanceSelect(select); }
        });
        initMobileFilters();
    });
})();
