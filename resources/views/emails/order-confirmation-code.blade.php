{{-- Email with the code the customer enters to confirm the order (inline styles: mail clients strip stylesheets) --}}
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
                        @if($reminder)
                            <h2 style="margin: 0 0 12px; font-family: Georgia, 'Times New Roman', serif; font-weight: normal; font-size: 28px">Vaša porudžbina još čeka, {{ $order->name }}</h2>
                            <p style="margin: 0 0 8px; line-height: 1.5">
                                Juče ste poručili <strong>{{ $order->number }}</strong> (<strong>@money($order->total)</strong>),
                                ali porudžbina još nije potvrđena, pa je nismo počeli pripremati. Unesite novi kod ispod na stranici za potvrdu
                                da biste je zadržali. <strong>Ovaj kod zamenjuje kod iz prvog emaila.</strong>
                            </p>
                        @else
                            <h2 style="margin: 0 0 12px; font-family: Georgia, 'Times New Roman', serif; font-weight: normal; font-size: 28px">Potvrdite porudžbinu, {{ $order->name }}</h2>
                            <p style="margin: 0 0 8px; line-height: 1.5">
                                Hvala što kupujete u ShopModa prodavnici! Da bismo bili sigurni da porudžbina <strong>{{ $order->number }}</strong>
                                (<strong>@money($order->total)</strong>) zaista dolazi od vas, unesite kod ispod na stranici za potvrdu.
                                Pripremu počinjemo tek nakon potvrde.
                            </p>
                        @endif
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
                        <a href="{{ $link }}" style="display: inline-block; padding: 14px 28px; background: #1c1917; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #ffffff; text-decoration: none; border-radius: 0; font-weight: bold">
                            Potvrdi porudžbinu
                        </a>
                        <p style="margin: 16px 0 0; color: #57534e; font-size: 13px; line-height: 1.5">
                            Link važi do {{ $expiresAt->timezone(config('app.timezone'))->format('d.m.Y. \u H:i') }}.
                            @if($reminder)
                                Nakon toga porudžbina se otkazuje, a patike se vraćaju na stanje.
                            @endif
                            Ako niste vi poručili, slobodno zanemarite ovaj email.
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
