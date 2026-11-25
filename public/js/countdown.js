/*
 * Live countdown for every <span data-countdown="2026-12-01T23:59:00+00:00">.
 * Shows e.g. "3d 04:12:09"; when the time is up it shows data-countdown-done (default "Završeno").
 */
(function () {
    'use strict';

    const elements = Array.from(document.querySelectorAll('[data-countdown]'));
    if (!elements.length) return;

    const pad = function (n) { return String(n).padStart(2, '0'); };

    function render() {
        const now = Date.now();

        elements.forEach(function (el) {
            const left = Math.max(0, Math.floor((new Date(el.dataset.countdown).getTime() - now) / 1000));

            if (left === 0) {
                el.textContent = el.dataset.countdownDone || 'Završeno';
                return;
            }

            const days = Math.floor(left / 86400);
            const time = pad(Math.floor(left % 86400 / 3600)) + ':' + pad(Math.floor(left % 3600 / 60)) + ':' + pad(left % 60);
            el.textContent = (days ? days + 'd ' : '') + time;
        });
    }

    render();
    setInterval(render, 1000);
})();
