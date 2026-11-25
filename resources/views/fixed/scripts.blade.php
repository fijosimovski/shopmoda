<!-- Bootstrap JS (dropdowns, navbar toggle, toasts) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Flash message toasts (fixed/flash.blade.php) -->
<script src="{{ asset('js/toasts.js') }}"></script>

<!-- Form validation for every <form data-validate> -->
<script src="{{ asset('js/validation.js') }}"></script>

<!-- Spinner on the button of every form that sends something (after validation.js) -->
<script src="{{ asset('js/submit-spinner.js') }}"></script>

<!-- Page specific scripts (e.g. the sizes table in the admin product form) -->
@stack('scripts')
