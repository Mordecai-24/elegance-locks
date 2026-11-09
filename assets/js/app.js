/* Elegance Locks - small progressive-enhancement script. */
(function () {
    'use strict';

    var root = document.documentElement;
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');

    // ---------- Sidebar: desktop collapse (remembered) ----------
    var collapseBtn = document.getElementById('sidebar-collapse');
    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            var collapsed = root.getAttribute('data-sidebar') === 'collapsed';
            if (collapsed) {
                root.removeAttribute('data-sidebar');
            } else {
                root.setAttribute('data-sidebar', 'collapsed');
            }
            try {
                localStorage.setItem('el_sidebar', collapsed ? 'expanded' : 'collapsed');
            } catch (e) { /* storage may be unavailable */ }
        });
    }

    // ---------- Sidebar: mobile open/close ----------
    function openMenu() {
        if (!sidebar) return;
        sidebar.classList.add('is-open');
        if (overlay) overlay.hidden = false;
    }
    function closeMenu() {
        if (!sidebar) return;
        sidebar.classList.remove('is-open');
        if (overlay) overlay.hidden = true;
    }

    var openBtn = document.getElementById('sidebar-open');
    if (openBtn) openBtn.addEventListener('click', openMenu);
    if (overlay) overlay.addEventListener('click', closeMenu);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeMenu();
    });

    // ---------- Confirm before risky actions: <form data-confirm="Are you sure?"> ----------
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    // ---------- OTP input: digits only ----------
    document.querySelectorAll('input[data-digits-only]').forEach(function (input) {
        input.addEventListener('input', function () {
            input.value = input.value.replace(/\D/g, '').slice(0, 6);
        });
    });

    // ---------- Resend countdown: <button data-countdown="45"> ----------
    document.querySelectorAll('[data-countdown]').forEach(function (button) {
        var remaining = parseInt(button.getAttribute('data-countdown'), 10) || 0;
        if (remaining <= 0) return;

        var label = button.getAttribute('data-label') || 'Resend code';
        button.disabled = true;
        button.classList.add('opacity-60', 'cursor-not-allowed');

        var timer = setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(timer);
                button.disabled = false;
                button.classList.remove('opacity-60', 'cursor-not-allowed');
                button.textContent = label;
            } else {
                button.textContent = label + ' (' + remaining + 's)';
            }
        }, 1000);
        button.textContent = label + ' (' + remaining + 's)';
    });

    // ---------- Show/hide password: <button data-toggle-password="#id"> ----------
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.querySelector(button.getAttribute('data-toggle-password'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.textContent = show ? 'Hide' : 'Show';
        });
    });
})();
