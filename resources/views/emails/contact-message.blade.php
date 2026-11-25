{{-- Email sent to the contact email from Admin > Settings when a customer uses the contact page --}}
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
                        <span style="float: right; color: #57534e; font-size: 14px; line-height: 28px">Poruka sa kontakt forme</span>
                    </td>
                </tr>

                {{-- Message --}}
                <tr>
                    <td style="padding: 32px 32px 8px">
                        <h2 style="margin: 0 0 12px; font-family: Georgia, 'Times New Roman', serif; font-weight: normal; font-size: 28px">{{ $contactMessage->subject }}</h2>
                        <p style="margin: 0 0 16px; color: #57534e; font-size: 14px">
                            Od <strong style="color: #1c1917">{{ $contactMessage->name }}</strong>
                            &lt;<a href="mailto:{{ $contactMessage->email }}" style="color: #1c1917">{{ $contactMessage->email }}</a>&gt;
                            &middot; {{ $contactMessage->created_at->format('d.m.Y. \u H:i') }}
                        </p>
                        <table width="100%" cellpadding="16" cellspacing="0" style="background: #f5f0eb; border-radius: 0">
                            <tr>
                                <td style="line-height: 1.6; white-space: pre-line">{{ $contactMessage->message }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Call to action --}}
                <tr>
                    <td align="center" style="padding: 24px 32px">
                        <a href="{{ route('admin.messages.show', $contactMessage) }}" style="display: inline-block; padding: 14px 28px; background: #1c1917; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #ffffff; text-decoration: none; border-radius: 0">Otvori u admin panelu</a>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding: 20px 32px; border-top: 1px solid #e7e1db; color: #57534e; font-size: 13px; line-height: 1.5">
                        Odgovorite na ovaj email da biste direktno odgovorili kupcu.<br>
                        Ovu poruku primate jer je ova adresa podešena kao kontakt email u Admin &rsaquo; Podešavanja.<br>
                        &copy; {{ date('Y') }} ShopModa
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
