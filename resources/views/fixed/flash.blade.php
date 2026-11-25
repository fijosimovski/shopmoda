{{--
    Messages sent with ->with('success', '...') or ->with('error', '...'), shown as a toast in the bottom right
    corner that hides itself after a few seconds (public/js/toasts.js, styles under "Messages" in shopmoda.css).
--}}
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    @if(session('success'))
        <div class="toast site-toast" role="status" aria-live="polite" aria-atomic="true" data-site-toast data-bs-delay="5000">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-check-circle site-toast-icon" aria-hidden="true"></i>
                <div class="toast-body p-0 flex-grow-1">{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="{{ app()->getLocale() === 'en' ? 'Close' : 'Zatvori' }}"></button>
            </div>
            <div class="site-toast-bar" aria-hidden="true"></div>
        </div>
    @endif

    @if(session('error'))
        <div class="toast site-toast site-toast-error" role="alert" aria-live="assertive" aria-atomic="true" data-site-toast data-bs-delay="8000">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-circle site-toast-icon" aria-hidden="true"></i>
                <div class="toast-body p-0 flex-grow-1">{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="{{ app()->getLocale() === 'en' ? 'Close' : 'Zatvori' }}"></button>
            </div>
            <div class="site-toast-bar" aria-hidden="true"></div>
        </div>
    @endif
</div>
