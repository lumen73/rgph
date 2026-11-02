/* ==========================================================================
   RGPH5 — Interactions front-end (vanilla JS, aucune dépendance)
   ========================================================================== */
(function () {
    'use strict';

    /* --- Menu latéral (mobile) ------------------------------------------- */
    function initSidebar() {
        var sidebar = document.querySelector('[data-sidebar]');
        var backdrop = document.querySelector('[data-sidebar-backdrop]');
        var toggles = document.querySelectorAll('[data-sidebar-toggle]');

        if (!sidebar) {
            return;
        }

        function close() {
            sidebar.classList.remove('is-open');
            if (backdrop) {
                backdrop.classList.remove('is-visible');
            }
        }

        toggles.forEach(function (button) {
            button.addEventListener('click', function () {
                var open = sidebar.classList.toggle('is-open');
                if (backdrop) {
                    backdrop.classList.toggle('is-visible', open);
                }
            });
        });

        if (backdrop) {
            backdrop.addEventListener('click', close);
        }

        window.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                close();
            }
        });
    }

    /* --- Menu utilisateur ------------------------------------------------- */
    function initUserMenu() {
        var wrappers = document.querySelectorAll('[data-dropdown]');

        wrappers.forEach(function (wrapper) {
            var toggle = wrapper.querySelector('[data-dropdown-toggle]');

            if (!toggle) {
                return;
            }

            toggle.addEventListener('click', function (event) {
                event.stopPropagation();
                var open = wrapper.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        document.addEventListener('click', function () {
            wrappers.forEach(function (wrapper) {
                wrapper.classList.remove('is-open');
            });
        });

        // Accessibilité clavier : la touche Échap referme le menu.
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }
            wrappers.forEach(function (wrapper) {
                if (wrapper.classList.contains('is-open')) {
                    wrapper.classList.remove('is-open');
                    var toggleBtn = wrapper.querySelector('[data-dropdown-toggle]');
                    if (toggleBtn) {
                        toggleBtn.setAttribute('aria-expanded', 'false');
                        toggleBtn.focus();
                    }
                }
            });
        });
    }

    /* --- Alertes : fermeture + disparition automatique -------------------- */
    function initAlerts() {
        document.querySelectorAll('[data-autodismiss]').forEach(function (alert) {
            var timer = window.setTimeout(function () {
                dismiss(alert);
            }, 6000);

            alert.addEventListener('mouseenter', function () {
                window.clearTimeout(timer);
            });

            var close = alert.querySelector('[data-dismiss]');
            if (close) {
                close.addEventListener('click', function () {
                    window.clearTimeout(timer);
                    dismiss(alert);
                });
            }
        });

        function dismiss(alert) {
            alert.style.transition = 'opacity .2s ease, transform .2s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-4px)';
            window.setTimeout(function () {
                alert.remove();
            }, 220);
        }
    }

    /* --- Confirmation avant suppression ----------------------------------- */
    function initConfirm() {
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var message = form.getAttribute('data-confirm') || 'Confirmer cette action ?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    /* --- Listes déroulantes en cascade (département → commune) ------------ */
    function initCascade() {
        document.querySelectorAll('[data-cascade-url]').forEach(function (source) {
            var targetSelector = source.getAttribute('data-cascade-target');
            var target = targetSelector ? document.querySelector(targetSelector) : null;

            if (!target) {
                return;
            }

            var placeholder = target.getAttribute('data-placeholder') || '— Choisir une commune —';

            source.addEventListener('change', function () {
                var value = source.value;

                target.innerHTML = '<option value="">' + placeholder + '</option>';

                if (!value) {
                    return;
                }

                var url = source.getAttribute('data-cascade-url')
                    + (source.getAttribute('data-cascade-url').indexOf('?') === -1 ? '?' : '&')
                    + 'departement=' + encodeURIComponent(value);

                target.disabled = true;

                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Réponse invalide');
                        }
                        return response.json();
                    })
                    .then(function (payload) {
                        var results = Array.isArray(payload.results) ? payload.results : [];

                        results.forEach(function (item) {
                            var option = document.createElement('option');
                            option.value = String(item.id);
                            option.textContent = String(item.text);
                            target.appendChild(option);
                        });
                    })
                    .catch(function () {
                        var option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Erreur de chargement';
                        target.appendChild(option);
                    })
                    .finally(function () {
                        target.disabled = false;
                    });
            });
        });
    }

    /* --- Auto-remplissage commune → département --------------------------- */
    function initAutoFill() {
        function formatNumber(value) {
            try {
                return new Intl.NumberFormat('fr-FR').format(value);
            } catch (error) {
                return String(value);
            }
        }

        // Commune → département.
        document.querySelectorAll('[data-commune-department]').forEach(function (select) {
            var targetSelector = select.getAttribute('data-department-target');
            var target = targetSelector ? document.querySelector(targetSelector) : null;

            if (!target) {
                return;
            }

            function sync() {
                var option = select.options[select.selectedIndex];
                target.value = option ? (option.getAttribute('data-departement-nom') || '') : '';
            }

            select.addEventListener('change', sync);
            sync();
        });

        // Arrondissement → commune + département.
        document.querySelectorAll('[data-arrondissement-location]').forEach(function (select) {
            var communeSelector = select.getAttribute('data-commune-target');
            var departmentSelector = select.getAttribute('data-department-target');
            var communeSelect = communeSelector ? document.querySelector(communeSelector) : null;
            var departmentInput = departmentSelector ? document.querySelector(departmentSelector) : null;

            if (!communeSelect) {
                return;
            }

            var syncing = false;

            select.addEventListener('change', function () {
                var option = select.options[select.selectedIndex];

                if (!option || !option.value) {
                    return;
                }

                var communeId = option.getAttribute('data-commune-id') || '';
                var departmentName = option.getAttribute('data-departement-nom') || '';

                if (communeId) {
                    syncing = true;
                    communeSelect.value = communeId;
                    syncing = false;
                }

                if (departmentInput) {
                    departmentInput.value = departmentName;
                }
            });

            communeSelect.addEventListener('change', function () {
                var option = communeSelect.options[communeSelect.selectedIndex];

                if (departmentInput) {
                    departmentInput.value = option ? (option.getAttribute('data-departement-nom') || '') : '';
                }

                if (syncing) {
                    return;
                }

                var selectedOption = select.options[select.selectedIndex];
                var arrondissementCommune = selectedOption ? (selectedOption.getAttribute('data-commune-id') || '') : '';

                if (arrondissementCommune !== '' && arrondissementCommune !== communeSelect.value) {
                    select.value = '';
                }
            });

            var initial = communeSelect.options[communeSelect.selectedIndex];

            if (departmentInput && initial) {
                departmentInput.value = initial.getAttribute('data-departement-nom') || '';
            }
        });

        // Capacité d'une ville dans le formulaire du registre.
        document.querySelectorAll('[data-capacity-source]').forEach(function (select) {
            var targetSelector = select.getAttribute('data-capacity-target');
            var target = targetSelector ? document.querySelector(targetSelector) : null;

            if (!target) {
                return;
            }

            function sync() {
                var option = select.options[select.selectedIndex];

                if (!option || !option.value) {
                    target.textContent = 'Sélectionnez une ville pour voir sa capacité.';
                    target.classList.remove('is-danger', 'is-ok');
                    return;
                }

                var capacity = parseInt(option.getAttribute('data-capacity') || '0', 10);
                var registered = parseInt(option.getAttribute('data-registered') || '0', 10);
                var remaining = Math.max(0, capacity - registered);

                target.textContent = 'Capacité : ' + formatNumber(capacity)
                    + ' · Inscrits : ' + formatNumber(registered)
                    + ' · Reste : ' + formatNumber(remaining);

                target.classList.toggle('is-danger', remaining <= 0);
                target.classList.toggle('is-ok', remaining > 0);
            }

            select.addEventListener('change', sync);
            sync();
        });
    }

    /* --- Saisie multiple : assistant pas à pas --------------------------- */
    function initMultiForms() {
        document.querySelectorAll('[data-multi-form]').forEach(function (form) {
            var steps = Array.prototype.slice.call(form.querySelectorAll('[data-step]'));

            if (steps.length < 2) {
                return;
            }

            var startStep = parseInt(form.getAttribute('data-start-step') || '0', 10);

            if (isNaN(startStep) || startStep < 0 || startStep >= steps.length) {
                startStep = 0;
            }

            var current  = startStep;
            var prev     = form.querySelector('[data-multi-prev]');
            var next     = form.querySelector('[data-multi-next]');
            var submit   = form.querySelector('[data-multi-submit]');
            var counter  = form.querySelector('[data-multi-counter]');
            var progress = form.querySelector('[data-multi-progress]');

            form.classList.add('is-enhanced');

            function render() {
                steps.forEach(function (step, index) {
                    step.hidden = index !== current;
                });

                if (prev) {
                    prev.hidden = current === 0;
                }

                if (next) {
                    next.hidden = current === steps.length - 1;
                }

                if (submit) {
                    submit.hidden = current !== steps.length - 1;
                }

                if (counter) {
                    counter.textContent = 'Étape ' + (current + 1) + ' / ' + steps.length;
                }

                if (progress) {
                    progress.style.width = Math.round(((current + 1) / steps.length) * 100) + '%';
                }

                var focusable = steps[current].querySelector('input, select, textarea');

                if (focusable) {
                    window.setTimeout(function () {
                        focusable.focus();
                    }, 30);
                }
            }

            function validateStep(index) {
                var fields = steps[index].querySelectorAll('input, select, textarea');

                for (var i = 0; i < fields.length; i++) {
                    if (!fields[i].checkValidity()) {
                        fields[i].reportValidity();
                        return false;
                    }
                }

                return true;
            }

            function go(index) {
                if (index < 0 || index >= steps.length) {
                    return;
                }

                if (index > current && !validateStep(current)) {
                    return;
                }

                current = index;
                render();
            }

            if (next) {
                next.addEventListener('click', function () {
                    go(current + 1);
                });
            }

            if (prev) {
                prev.addEventListener('click', function () {
                    go(current - 1);
                });
            }

            form.addEventListener('submit', function (event) {
                if (current < steps.length - 1) {
                    event.preventDefault();
                    go(current + 1);
                }
            });

            render();
        });
    }

    /* --- Initialisation --------------------------------------------------- */
    document.addEventListener('DOMContentLoaded', function () {
        initSidebar();
        initUserMenu();
        initAlerts();
        initConfirm();
        initCascade();
        initAutoFill();
        initMultiForms();
    });
})();
