{{-- "Forgot password" email with the reset code (inline styles: mail clients strip stylesheets) --}}
<!DOCTYPE html>
<html lang="sr-Latn">
<body style="margin: 0; padding: 0; background: #f5f0eb; font-family: 'Helvetica Neue', Arial, sans-serif; color: #1c1917">
<table width="100%" cellpadding="0" cellspacing="0" style="background: #f5f0eb; padding: 24px 0">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background: #ffffff; border-radius: 0">

                {{-- Header --}}
                <tr>
                    <td style="padding: 24px 32px; border-bottom: 1px solid #e7e1db">
                        <a href="{{ route('home') }}" style="font-size: 22px; font-weight: 800; letter-spacing: -0.5px; color: #1c1917; text-decoration: none">SHOPMODA<span style="color: #c2410c">.</span></a>
                    </td>
                </tr>

                {{-- Text --}}
                <tr>
                    <td style="padding: 32px 32px 8px">
                        <h2 style="margin: 0 0 12px; font-family: Georgia, 'Times New Roman', serif; font-weight: normal; font-size: 28px">Promena lozinke, {{ $user->name }}</h2>
                        <p style="margin: 0 0 8px; line-height: 1.5">
                            Neko (nadamo se vi) zatražio je promenu lozinke za vaš ShopModa nalog.
                            Unesite ovaj kod na stranici za promenu lozinke, zajedno sa novom lozinkom:
                        </p>
                    </td>
                </tr>

                {{-- Code --}}
                <tr>
                    <td align="center" style="padding: 16px 32px">
                        <div style="display: inline-block; padding: 16px 28px; background: #f5f0eb; border: 1px solid #1c1917; border-radius: 0; font-size: 32px; font-weight: bold; letter-spacing: 8px; font-family: 'Courier New', monospace">
                            {{ $code }}
                        </div>
                    </td>
                </tr>

                {{-- Button --}}
                <tr>
                    <td align="center" style="padding: 8px 32px 24px">
                        <a href="{{ route('password.reset') }}" style="display: inline-block; padding: 14px 28px; background: #1c1917; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #ffffff; text-decoration: none; border-radius: 0; font-weight: bold">
                            Postavi novu lozinku
                        </a>
                        <p style="margin: 16px 0 0; color: #57534e; font-size: 13px; line-height: 1.5">
                            Kod važi {{ \App\Http\Controllers\PasswordResetController::CODE_VALID_MINUTES }} minuta.
                            Ako niste vi tražili promenu, zanemarite ovaj email: lozinka ostaje ista. Nikome ne dajte ovaj kod.
                        </p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding: 16px 32px; border-top: 1px solid #e7e1db; color: #57534e; font-size: 12px">
                        &copy; {{ date('Y') }} ShopModa
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
