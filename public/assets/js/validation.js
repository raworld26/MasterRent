/* =========================================================================
   Validazione lato client (JavaScript vanilla, nessuna libreria).
   Si attiva su ogni <form data-validate>. La validazione server resta
   comunque la fonte di verità: questa migliora solo l'esperienza utente.
   ========================================================================= */
(function () {
    'use strict';

    function setError(input, message) {
        input.classList.add('invalid');
        input.setAttribute('aria-invalid', 'true');
        var holder = input.closest('.field') || input.parentNode;
        var msg = holder.querySelector('.field-error');
        if (!msg) {
            msg = document.createElement('span');
            msg.className = 'field-error';
            holder.appendChild(msg);
        }
        msg.textContent = message;
    }

    function clearError(input) {
        input.classList.remove('invalid');
        input.removeAttribute('aria-invalid');
        var holder = input.closest('.field') || input.parentNode;
        var msg = holder.querySelector('.field-error');
        if (msg) { msg.remove(); }
    }

    var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function validateField(input, form) {
        var value = (input.value || '').trim();

        if (input.hasAttribute('required') && value === '') {
            setError(input, 'Campo obbligatorio.');
            return false;
        }
        if (input.type === 'email' && value !== '' && !EMAIL_RE.test(value)) {
            setError(input, 'Inserisci un indirizzo email valido.');
            return false;
        }
        var min = parseInt(input.getAttribute('minlength') || '0', 10);
        if (min > 0 && value !== '' && value.length < min) {
            setError(input, 'Servono almeno ' + min + ' caratteri.');
            return false;
        }
        var matchName = input.getAttribute('data-match');
        if (matchName) {
            var other = form.querySelector('[name="' + matchName + '"]');
            if (other && value !== other.value) {
                setError(input, 'I valori non coincidono.');
                return false;
            }
        }
        clearError(input);
        return true;
    }

    function wire(form) {
        var fields = form.querySelectorAll('input, select, textarea');

        form.addEventListener('submit', function (event) {
            var ok = true;
            fields.forEach(function (input) {
                if (input.type === 'hidden' || input.type === 'submit') { return; }
                if (!validateField(input, form)) { ok = false; }
            });
            if (!ok) {
                event.preventDefault();
                var firstInvalid = form.querySelector('.invalid');
                if (firstInvalid) { firstInvalid.focus(); }
            }
        });

        fields.forEach(function (input) {
            input.addEventListener('blur', function () { validateField(input, form); });
            input.addEventListener('input', function () {
                if (input.classList.contains('invalid')) { validateField(input, form); }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-validate]').forEach(wire);
    });
})();
