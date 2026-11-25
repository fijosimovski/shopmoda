{{--
    Spam trap: people never see this field, bots fill it in. Checked by HoneypotMiddleware ('honeypot' in routes).
    The data-*ignore attributes keep password managers (browser, 1Password, LastPass, Bitwarden...) from autofilling it;
    the real fields carry autocomplete="username" / "current-password", so browsers don't guess this one as the username.
--}}
<div class="hp-field" aria-hidden="true">
    <label for="{{ \App\Http\Middleware\HoneypotMiddleware::FIELD }}">Veb sajt</label>
    <input type="text" id="{{ \App\Http\Middleware\HoneypotMiddleware::FIELD }}" name="{{ \App\Http\Middleware\HoneypotMiddleware::FIELD }}" value="" tabindex="-1"
           autocomplete="off" data-lpignore="true" data-1p-ignore data-bwignore data-form-type="other">
</div>
