/*
 * ShopModa cart drawer: "Add to cart" without a page reload.
 *
 * - form[data-cart-add] (product page): sent with fetch(). The product photo (data-fly-image) flies
 *   to the bag icon, the number next to it bumps, the button shows "Added" and the cart drawer opens.
 *   Errors (e.g. "only 2 left in size 42") are shown in [data-cart-error] inside the form.
 * - a[data-cart-drawer-toggle] (bag icon in the navigation): opens the drawer instead of going to /cart.
 * - form[data-cart-remove] (inside the drawer): removes a row and redraws the drawer.
 *
 * The server answers with JSON { count, html, message } (CartController::drawerResponse()).
 * Anything unexpected (session expired, network error) falls back to sending the form normally,
 * and without JavaScript every form and link works as a plain page.
 */
(function () {
    'use strict';

    const drawerEl = document.getElementById('cartDrawer');
    if (!drawerEl || !window.bootstrap || !window.fetch) return;

    const drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
    const body = drawerEl.querySelector('[data-cart-drawer-body]');
    const status = drawerEl.querySelector('[data-cart-status]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let loaded = false;

    // ---------- talking to the server ----------

    function send(url, options) {
        return fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }, options)).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                return { ok: response.ok, status: response.status, data: data };
            });
        });
    }

    // Puts the new drawer contents and cart count on the page.
    function render(data) {
        body.innerHTML = data.html;
        loaded = true;
        updateCount(data.count);
        if (data.message) status.textContent = data.message;
    }

    function updateCount(count) {
        document.querySelectorAll('[data-cart-count]').forEach(function (el) {
            el.textContent = '(' + count + ')';
            el.classList.toggle('d-none', count === 0);
        });
    }

    // ---------- animation ----------

    // The bag icon that is visible right now; on phones it sits in the closed menu, so the menu button instead.
    function flyTarget() {
        const visible = function (el) { return el && el.getClientRects().length && el.getBoundingClientRect().width > 0; };
        const icon = Array.from(document.querySelectorAll('[data-cart-icon]')).find(visible);
        if (icon) return icon;
        const toggler = document.querySelector('.shop-nav .navbar-toggler');
        return visible(toggler) ? toggler : null;
    }

    // A copy of the product photo flies in an arc to the bag icon and shrinks into it.
    function fly(form) {
        const image = form.dataset.flyImage && document.querySelector(form.dataset.flyImage);
        const target = flyTarget();
        if (reduceMotion || !image || !target || !image.animate) return Promise.resolve(target);

        const from = image.getBoundingClientRect();
        const to = target.getBoundingClientRect();
        const clone = image.cloneNode(true);
        clone.removeAttribute('id');
        clone.className = 'cart-fly';
        clone.alt = '';
        clone.style.left = from.left + 'px';
        clone.style.top = from.top + 'px';
        clone.style.width = from.width + 'px';
        clone.style.height = from.height + 'px';
        document.body.appendChild(clone);

        const dx = to.left + to.width / 2 - (from.left + from.width / 2);
        const dy = to.top + to.height / 2 - (from.top + from.height / 2);
        const endScale = Math.max(20 / from.width, 0.02);

        const animation = clone.animate([
            { transform: 'translate(0, 0) scale(1)', opacity: 1 },
            { transform: 'translate(' + dx * 0.3 + 'px, ' + (dy * 0.3 - 90) + 'px) scale(0.5)', opacity: 1, offset: 0.45 },
            { transform: 'translate(' + dx + 'px, ' + dy + 'px) scale(' + endScale + ')', opacity: 0.35 },
        ], { duration: 750, easing: 'cubic-bezier(.55, 0, .35, 1)' });

        return animation.finished.catch(function () {}).then(function () {
            clone.remove();
            return target;
        });
    }

    // Short "pop" of the bag icon (or menu button) when the item lands.
    function bump(el) {
        if (!el || reduceMotion) return;
        el.classList.remove('cart-bump');
        void el.offsetWidth; // restart the animation if it is still running
        el.classList.add('cart-bump');
    }

    // ---------- "Add to cart" on the product page ----------

    function initAddForm(form) {
        const button = form.querySelector('[data-cart-add-button]');
        const errorBox = form.querySelector('[data-cart-error]');
        if (!button) return;
        const buttonHtml = button.innerHTML;
        let busy = false;

        function setButton(state) {
            button.classList.toggle('is-added', state === 'added');
            button.disabled = state !== 'idle';
            if (state === 'loading') {
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + button.dataset.loadingText;
            } else if (state === 'added') {
                button.innerHTML = '<i class="bi bi-check2 me-2" aria-hidden="true"></i>' + button.dataset.addedText;
            } else {
                button.innerHTML = buttonHtml;
            }
        }

        function showError(message) {
            errorBox.textContent = message || '';
            errorBox.classList.toggle('d-none', !message);
        }

        // validation.js listens first: an invalid form is stopped there and never reaches this listener.
        form.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return;
            e.preventDefault();
            if (busy) return;

            busy = true;
            showError(null);
            setButton('loading');

            send(form.action, { method: 'POST', body: new FormData(form) })
                .then(function (result) {
                    if (result.ok) {
                        return fly(form).then(function (target) {
                            render(result.data);
                            bump(target);
                            setButton('added');
                            setTimeout(function () { setButton('idle'); }, 1600);
                            setTimeout(function () { drawer.show(); }, reduceMotion ? 0 : 200);
                        });
                    }

                    if (result.status === 422) {
                        const errors = result.data.errors || {};
                        const first = Object.keys(errors).map(function (key) { return errors[key][0]; })[0];
                        showError(first || result.data.message);
                        setButton('idle');
                        return;
                    }

                    form.submit(); // e.g. 419 session expired: the normal page shows what happened
                })
                .catch(function () { form.submit(); })
                .finally(function () { busy = false; });
        });
    }

    document.querySelectorAll('form[data-cart-add]').forEach(initAddForm);

    // ---------- bag icon in the navigation ----------

    document.querySelectorAll('[data-cart-drawer-toggle]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return; // new tab etc. still opens /cart
            e.preventDefault();
            drawer.show();

            // Always fetch fresh contents: stock and promotions may have changed since the last look.
            send(drawerEl.dataset.drawerUrl)
                .then(function (result) {
                    if (!result.ok) throw new Error('drawer');
                    render(result.data);
                })
                .catch(function () {
                    if (!loaded) window.location.href = link.href;
                });
        });
    });

    // ---------- "Remove" inside the drawer ----------

    body.addEventListener('submit', function (e) {
        const form = e.target.closest('form[data-cart-remove]');
        if (!form) return;
        e.preventDefault();

        const row = form.closest('.cart-drawer-item');
        if (row) row.classList.add('is-removing');

        send(form.action, { method: 'POST', body: new FormData(form) }) // @method('DELETE') is in the form data
            .then(function (result) {
                if (!result.ok) throw new Error('remove');
                render(result.data);
            })
            .catch(function () { form.submit(); });
    });
})();
