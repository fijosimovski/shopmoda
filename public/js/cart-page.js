/*
 * ShopModa cart page (/cart): change quantities and remove sneakers without a page reload.
 *
 * - The - / + buttons and typing in the quantity field save the new quantity after a short pause
 *   (PATCH /cart/{key}); the "update" button is only for browsers without JavaScript and is hidden here.
 * - "Remove" (form[data-cart-page-remove]) sends DELETE /cart/{key} with view=page.
 *
 * The server answers with JSON { count, html, message, errorKey } (CartController::cartPageResponse()):
 * html is the whole redrawn cart (partials/cart-page.blade.php), because a promotion such as
 * "2nd pair -20%" can change other rows and the total too. errorKey = the row an error belongs to
 * ("only 2 left in size 42"); that row then shows the message and keeps the last saved quantity.
 *
 * Requests are sent one after another (never two at once), so two quick changes can't overwrite
 * each other in the session. Anything unexpected (session expired, network error) sends the form normally.
 */
(function () {
    'use strict';

    const page = document.querySelector('[data-cart-page]');
    if (!page || !window.fetch) return;

    const status = document.querySelector('[data-cart-page-status]');
    const DELAY = 450;  // ms after the last click / key press before the quantity is saved
    const MAX = 10;     // same as the "max:10" rule in CartController::update()
    const FLASH = 900;  // ms, length of the .cart-flash animation in shopmoda.css

    let queue = Promise.resolve();
    const timers = {};   // row key => pending save timer
    const pending = {};  // row key => quantity typed but not saved yet (kept when the cart is redrawn)

    // ---------- talking to the server ----------

    function send(form, extra) {
        const data = new FormData(form);
        Object.keys(extra || {}).forEach(function (name) { data.set(name, extra[name]); });

        return fetch(form.action, {
            method: 'POST', // @method('PATCH') / @method('DELETE') is in the form data
            body: data,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (response) {
            return response.json().catch(function () { return null; }).then(function (json) {
                if (!json || typeof json.html !== 'string') throw new Error('cart');
                return { ok: response.ok, data: json };
            });
        });
    }

    // One request at a time, in the order they were made.
    function enqueue(job) {
        queue = queue.then(job, job);
        return queue;
    }

    function setBusy(busy) {
        page.setAttribute('aria-busy', busy ? 'true' : 'false');
        page.classList.toggle('is-updating', busy);
    }

    // ---------- drawing ----------

    function rowOf(el) {
        return el.closest('[data-cart-row]');
    }

    function rowByKey(key) {
        return Array.from(page.querySelectorAll('[data-cart-row]')).find(function (row) {
            return row.dataset.cartRow === key;
        });
    }

    // Which control had focus, so it gets it back after the cart is redrawn.
    function focusedControl() {
        const el = document.activeElement;
        if (!el || !page.contains(el)) return null;
        const row = rowOf(el);
        if (!row) return { key: null };
        return { key: row.dataset.cartRow, step: el.dataset.cartStep || null };
    }

    function restoreFocus(focus) {
        if (!focus) return;
        const row = focus.key && rowByKey(focus.key);
        if (!row) {
            const fallback = page.querySelector('input[name="quantity"], a');
            if (fallback) fallback.focus();
            return;
        }
        let el = focus.step ? row.querySelector('[data-cart-step="' + focus.step + '"]') : null;
        if (!el || el.disabled) el = row.querySelector('input[name="quantity"]');
        if (el) el.focus();
    }

    // Every price on the page, so render() can tell which ones changed:
    // { "row:<key>": row subtotal, "line:<label>": a totals line (subtotal, each discount, total) }
    function readPrices() {
        const prices = new Map();
        page.querySelectorAll('[data-cart-row]').forEach(function (row) {
            const el = row.querySelector('[data-cart-row-subtotal]');
            if (el) prices.set('row:' + row.dataset.cartRow, { el: el, text: el.textContent.trim() });
        });
        page.querySelectorAll('[data-totals] .totals-line, [data-totals] .totals-total').forEach(function (line) {
            const label = line.firstElementChild;
            const el = line.lastElementChild;
            if (label && el && label !== el) prices.set('line:' + label.textContent.trim(), { el: el, text: el.textContent.trim() });
        });
        return prices;
    }

    // Red sweep over each price that is new or different (.cart-flash in shopmoda.css).
    function flashChanges(before) {
        readPrices().forEach(function (now, id) {
            const old = before.get(id);
            if (old && old.text === now.text) return;
            now.el.classList.add('cart-flash');
            setTimeout(function () { now.el.classList.remove('cart-flash'); }, FLASH);
        });
    }

    function render(data) {
        const focus = focusedControl();
        const before = readPrices();
        page.innerHTML = data.html;
        prepare();
        flashChanges(before);

        // Quantities typed in other rows while this request was running stay in their fields.
        Object.keys(pending).forEach(function (key) {
            const row = rowByKey(key);
            if (!row) { delete pending[key]; return; }
            row.querySelector('input[name="quantity"]').value = pending[key];
            syncButtons(row);
        });

        updateCount(data.count);
        if (data.message && status) status.textContent = data.message;
        if (data.errorKey) showRowError(rowByKey(data.errorKey), data.message);
        restoreFocus(focus);
    }

    // Same as in cart-drawer.js: the number next to the bag icon.
    function updateCount(count) {
        document.querySelectorAll('[data-cart-count]').forEach(function (el) {
            el.textContent = '(' + count + ')';
            el.classList.toggle('d-none', count === 0);
        });
    }

    function showRowError(row, message) {
        if (!row) return;
        const box = row.querySelector('[data-cart-row-error]');
        box.textContent = message || '';
        box.classList.toggle('d-none', !message);
        row.querySelector('input[name="quantity"]').classList.toggle('is-invalid', !!message);
    }

    // The highest quantity allowed in this row (stock, but never above 10).
    function maxOf(input) {
        return Math.min(MAX, Number(input.max) || MAX);
    }

    // "-" is off at 1, "+" is off at the stock / 10.
    function syncButtons(row) {
        const input = row.querySelector('input[name="quantity"]');
        const value = parseInt(input.value, 10);
        row.querySelector('[data-cart-step="-1"]').disabled = !(value > 1);
        row.querySelector('[data-cart-step="1"]').disabled = !(value < maxOf(input));
    }

    // After every redraw: hide the no-JavaScript "update" buttons.
    function prepare() {
        page.querySelectorAll('[data-cart-update-button]').forEach(function (button) {
            button.classList.add('d-none');
        });
    }

    // ---------- quantity ----------

    function checkQuantity(value, input) {
        if (!/^\d+$/.test(value)) return 'Unesite ceo broj.';
        const n = Number(value);
        if (n < 1) return 'Količina mora biti najmanje 1. Za brisanje kliknite „Ukloni”.';
        if (n > MAX) return 'Najviše ' + MAX + ' pari istog modela po porudžbini.';
        if (n > maxOf(input)) return 'Na stanju je još samo ' + maxOf(input) + '.';
        return null;
    }

    function scheduleSave(row, delay) {
        const key = row.dataset.cartRow;
        const input = row.querySelector('input[name="quantity"]');
        const value = input.value.trim();
        clearTimeout(timers[key]);

        const error = checkQuantity(value, input);
        showRowError(row, error);
        syncButtons(row);
        if (error) { delete pending[key]; return; }

        pending[key] = value;
        timers[key] = setTimeout(function () { save(key); }, delay);
    }

    function save(key) {
        delete timers[key];
        if (!(key in pending)) return;

        enqueue(function () {
            const row = rowByKey(key);
            const value = pending[key];
            delete pending[key];
            if (!row || value === undefined) return;

            const form = row.querySelector('form[data-cart-quantity]');
            setBusy(true);
            return send(form, { quantity: value })
                .then(function (result) { render(result.data); })
                .catch(function () { HTMLFormElement.prototype.submit.call(form); })
                .finally(function () { setBusy(false); });
        });
    }

    page.addEventListener('click', function (e) {
        const button = e.target.closest('[data-cart-step]');
        if (!button || !page.contains(button)) return;

        const row = rowOf(button);
        const input = row.querySelector('input[name="quantity"]');
        const current = parseInt(input.value, 10) || 1;
        const next = Math.min(maxOf(input), Math.max(1, current + Number(button.dataset.cartStep)));
        if (next === current) return;

        input.value = next;
        scheduleSave(row, DELAY);
    });

    page.addEventListener('input', function (e) {
        if (e.target.name !== 'quantity') return;
        const row = rowOf(e.target);
        if (e.target.value.trim() === '') { clearTimeout(timers[row.dataset.cartRow]); return; } // still typing
        scheduleSave(row, DELAY + 300);
    });

    // Leaving an empty field: show why it isn't saved.
    page.addEventListener('focusout', function (e) {
        if (e.target.name === 'quantity' && e.target.value.trim() === '') scheduleSave(rowOf(e.target), 0);
    });

    page.addEventListener('submit', function (e) {
        const quantityForm = e.target.closest('form[data-cart-quantity]');
        const removeForm = e.target.closest('form[data-cart-page-remove]');
        if (!quantityForm && !removeForm) return;
        e.preventDefault();

        const row = rowOf(e.target);

        // Enter in the quantity field: save right away.
        if (quantityForm) {
            scheduleSave(row, 0);
            return;
        }

        // "Remove": forget a pending quantity for this row, fade it out, redraw the cart.
        const key = row.dataset.cartRow;
        clearTimeout(timers[key]);
        delete pending[key];
        row.classList.add('is-removing');

        enqueue(function () {
            setBusy(true);
            return send(removeForm, { view: 'page' })
                .then(function (result) {
                    if (!result.ok) throw new Error('remove');
                    render(result.data);
                })
                .catch(function () { HTMLFormElement.prototype.submit.call(removeForm); })
                .finally(function () { setBusy(false); });
        });
    });

    prepare();
})();
