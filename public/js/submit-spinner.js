/*
 * Every page: spinner on the button that sends a form (login, register, password reset, contact, profile, admin forms...).
 *
 * Runs for POST forms only (search and filter forms use GET and stay as they are). It waits until the submit event
 * is over and does nothing if something stopped it: validation.js on a wrong field, an onsubmit="return confirm(...)"
 * that was cancelled, or a script that sends the form itself with fetch (add to cart, cart page, cart drawer).
 * Then the clicked button gets a spinner (and its data-loading-text, if it has one) and every submit button of the
 * form is disabled, so a double click can't send it twice.
 *
 * Opt out with <form data-no-spinner> (the checkout has its own overlay, public/js/checkout-submit.js).
 */
(function () {
    'use strict';

    const busy = []; // { button, html, disabled } to undo when the page comes back from the browser's cache

    function submitButtons(form) {
        return Array.from(form.elements).filter(function (el) {
            return (el.tagName === 'BUTTON' && el.type === 'submit') || (el.tagName === 'INPUT' && (el.type === 'submit' || el.type === 'image'));
        });
    }

    function start(form, submitter) {
        submitButtons(form).forEach(function (button) {
            busy.push({ button: button, html: button.innerHTML, disabled: button.disabled });

            if (button === submitter && button.tagName === 'BUTTON') {
                const spinner = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
                const text = button.dataset.loadingText;
                if (text) {
                    button.innerHTML = spinner + '<span class="ms-2">' + text + '</span>';
                } else {
                    // Icon-only buttons (e.g. delete) show just the spinner in place of the icon.
                    button.querySelectorAll('i.bi').forEach(function (icon) { icon.classList.add('d-none'); });
                    const hasText = button.textContent.trim() !== '';
                    button.insertAdjacentHTML('afterbegin', spinner.replace('spinner-border-sm', 'spinner-border-sm' + (hasText ? ' me-2' : '')));
                }
                button.setAttribute('aria-busy', 'true');
            }
            button.disabled = true;
        });
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post' || form.hasAttribute('data-no-spinner')) return;

        const submitter = e.submitter || submitButtons(form)[0];

        // After the event: other handlers may still cancel it, and the form data (with the clicked button's
        // name/value) is collected before the button gets disabled.
        setTimeout(function () {
            if (!e.defaultPrevented) start(form, submitter);
        }, 0);
    });

    // Coming back with the browser's Back button can show the page from cache with the buttons still spinning.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        busy.splice(0).forEach(function (item) {
            item.button.innerHTML = item.html;
            item.button.disabled = item.disabled;
            item.button.removeAttribute('aria-busy');
        });
    });
})();
