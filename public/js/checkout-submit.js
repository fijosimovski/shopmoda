/*
 * Checkout: "Place order" spinner.
 * Once the form passes validation (validation.js runs first and stops the submit when a field is wrong),
 * the button gets a spinner and is disabled, and a full-page "Placing your order..." overlay is shown.
 * Disabling the button also stops a double click from sending the order twice.
 */
(function () {
    const form = document.querySelector('[data-checkout-form]');
    if (!form) return;

    const button = form.querySelector('[data-checkout-submit]');
    const spinner = form.querySelector('[data-checkout-spinner]');
    const overlay = document.querySelector('[data-checkout-overlay]');

    function setLoading(loading) {
        button.disabled = loading;
        spinner.classList.toggle('d-none', !loading);
        overlay.classList.toggle('d-none', !loading);
        overlay.classList.toggle('d-flex', loading);
    }

    form.addEventListener('submit', function (e) {
        if (e.defaultPrevented) return;
        setLoading(true);
    });

    // Coming back with the browser's Back button can show the page from cache, still "loading".
    window.addEventListener('pageshow', function () {
        setLoading(false);
    });
})();
