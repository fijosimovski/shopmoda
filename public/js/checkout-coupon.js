/*
 * Checkout: "Apply" button next to the coupon field.
 * Asks the server (POST /checkout/coupon) what the code is worth and updates the order summary.
 * Nothing is saved: the code is sent with the order form and checked again when the order is placed.
 */
(function () {
    'use strict';

    const box = document.querySelector('[data-coupon]');
    if (!box) return;

    const form = box.closest('form');
    const input = box.querySelector('#coupon_code');
    const button = box.querySelector('[data-coupon-apply]');
    const error = box.querySelector('[data-coupon-error]');
    const message = box.querySelector('[data-coupon-message]');
    const totals = document.querySelector('[data-totals]');
    const baseTotal = box.dataset.baseTotal; // total without a coupon, already formatted

    // Puts the coupon line into the order summary (or removes it when line is null).
    function showLine(line) {
        totals.querySelectorAll('[data-coupon-line]').forEach(function (el) { el.remove(); });

        if (line) {
            const row = document.createElement('div');
            row.className = 'totals-line totals-discount';
            row.dataset.couponLine = '';
            const name = document.createElement('span');
            name.textContent = 'Kupon ' + line.code;
            const amount = document.createElement('span');
            amount.className = 'text-nowrap ms-2';
            amount.textContent = line.discount;
            row.append(name, amount);
            totals.querySelector('[data-total]').parentElement.before(row);
        }

        const total = line ? line.total : baseTotal;
        totals.querySelector('[data-total]').textContent = total;
        document.querySelectorAll('[data-order-total]').forEach(function (el) { el.textContent = total; });
    }

    function setState(ok, text) {
        input.classList.toggle('is-invalid', !ok);
        input.classList.toggle('is-valid', ok);
        error.textContent = ok ? '' : text;
        message.textContent = ok ? text : '';
    }

    function apply() {
        const code = input.value.trim();

        if (!code) {
            setState(false, 'Unesite kupon kod.');
            showLine(null);
            return;
        }

        button.disabled = true;

        fetch(box.dataset.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
            },
            body: JSON.stringify({ coupon_code: code, email: form.querySelector('[name="email"]').value }),
        })
            .then(function (response) {
                if (response.status === 429) {
                    return { valid: false, message: 'Previše pokušaja. Sačekajte minut i pokušajte ponovo.' };
                }
                return response.json().then(function (data) {
                    // Laravel validation errors come back as { errors: { coupon_code: [...] } }
                    if (data.errors) return { valid: false, message: Object.values(data.errors)[0][0] };
                    return data;
                });
            })
            .then(function (data) {
                setState(data.valid, data.message);
                showLine(data.valid ? data : null);
                if (data.valid) input.value = data.code;
            })
            .catch(function () {
                setState(false, 'Kupon trenutno nije moguće proveriti. Biće proveren kada pošaljete porudžbinu.');
            })
            .finally(function () {
                button.disabled = false;
            });
    }

    button.addEventListener('click', apply);

    // Enter in the coupon field applies the coupon instead of placing the order.
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            apply();
        }
    });

    // Changing the code after applying removes the old discount from the summary until it is applied again.
    input.addEventListener('input', function () {
        if (totals.querySelector('[data-coupon-line]')) {
            showLine(null);
            setState(true, '');
            input.classList.remove('is-valid');
        }
    });
})();
