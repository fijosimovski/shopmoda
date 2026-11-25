/*
 * Flash message toasts (fixed/flash.blade.php): shown on page load, hidden after data-bs-delay ms.
 * The thin line at the bottom (.site-toast-bar) shrinks over the same time. Bootstrap pauses the timer while
 * the mouse is over the toast and starts it again from the beginning on leave, so the line does the same.
 */
(function () {
    if (!window.bootstrap) return;

    document.querySelectorAll('[data-site-toast]').forEach(function (el) {
        const bar = el.querySelector('.site-toast-bar');
        el.style.setProperty('--toast-delay', (Number(el.dataset.bsDelay) || 5000) + 'ms');

        function restartBar() {
            bar.classList.remove('is-running');
            void bar.offsetWidth; // reflow, so the animation starts over
            bar.classList.add('is-running');
        }

        el.addEventListener('mouseenter', function () { bar.classList.remove('is-running'); });
        el.addEventListener('mouseleave', restartBar);
        el.addEventListener('hidden.bs.toast', function () { el.remove(); });

        bootstrap.Toast.getOrCreateInstance(el).show();
        restartBar();
    });
})();
