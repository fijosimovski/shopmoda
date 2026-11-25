/*
 * Admin promotion / coupon form:
 *  - [data-show-for="nth_item cart_threshold"] is only visible for those types (from [data-type-select])
 *  - the targets ([data-targets]) are hidden while "Whole shop" ([data-whole-shop]) is on
 *  - [data-single-use] fills the usage limits with 1 (coupons)
 *  - [data-generate-code] puts a random code into #code (coupons)
 */
(function () {
    'use strict';

    const form = document.querySelector('[data-type-form]');
    if (!form) return;

    const typeSelect = form.querySelector('[data-type-select]');
    const wholeShop = form.querySelector('[data-whole-shop]');
    const targets = form.querySelector('[data-targets]');

    function toggleType() {
        if (!typeSelect) return;
        form.querySelectorAll('[data-show-for]').forEach(function (el) {
            el.classList.toggle('d-none', el.dataset.showFor.split(' ').indexOf(typeSelect.value) === -1);
        });
    }

    function toggleTargets() {
        if (wholeShop && targets) targets.classList.toggle('d-none', wholeShop.checked);
    }

    typeSelect?.addEventListener('change', toggleType);
    wholeShop?.addEventListener('change', toggleTargets);
    toggleType();
    toggleTargets();

    // Coupons: "Single use" = can be redeemed by one order only.
    const singleUse = form.querySelector('[data-single-use]');
    singleUse?.addEventListener('change', function () {
        const total = form.querySelector('#usage_limit');
        const perCustomer = form.querySelector('#usage_limit_per_customer');
        total.readOnly = perCustomer.readOnly = singleUse.checked;
        if (singleUse.checked) {
            total.value = 1;
            perCustomer.value = 1;
        }
    });
    if (singleUse?.checked) singleUse.dispatchEvent(new Event('change'));

    // Coupons: random code like "SM-7KQ2-X9PD" (the server checks it is unique).
    form.querySelector('[data-generate-code]')?.addEventListener('click', function () {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O or 1/I, easier to type
        const part = function () {
            let s = '';
            const random = crypto.getRandomValues(new Uint32Array(4));
            random.forEach(function (n) { s += chars[n % chars.length]; });
            return s;
        };
        form.querySelector('#code').value = 'SM-' + part() + '-' + part();
    });
})();
