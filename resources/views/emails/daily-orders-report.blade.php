{{-- Midnight report for the shop: the day's orders, the full details are in the attached Excel file (inline styles: mail clients strip stylesheets) --}}
<!DOCTYPE html>
<html lang="sr-Latn">
<body style="margin: 0; padding: 0; background: #f5f0eb; font-family: 'Helvetica Neue', Arial, sans-serif; color: #1c1917">
<table width="100%" cellpadding="0" cellspacing="0" style="background: #f5f0eb; padding: 24px 0">
    <tr>
        <td align="center">
            <table width="640" cellpadding="0" cellspacing="0" style="max-width: 640px; width: 100%; background: #ffffff; border-radius: 0">

                {{-- Header --}}
                <tr>
                    <td style="padding: 24px 32px; border-bottom: 1px solid #e7e1db">
                        <span style="font-size: 22px; font-weight: 800; letter-spacing: -0.5px; color: #1c1917">SHOPMODA<span style="color: #c2410c">.</span></span>
                        <span style="color: #57534e; font-size: 14px; margin-left: 8px">Dnevni izveštaj o porudžbinama</span>
                    </td>
                </tr>

                {{-- Summary --}}
                <tr>
                    <td style="padding: 24px 32px 8px">
                        <h2 style="margin: 0 0 8px; font-family: Georgia, 'Times New Roman', serif; font-weight: normal; font-size: 28px">{{ $day->format('d.m.Y.') }}</h2>
                        <p style="margin: 0; line-height: 1.5">
                            @php($pairs = $orders->sum(fn ($o) => $o->items->sum('quantity')))
                            <strong>{{ $orders->count() }}</strong> {{ \App\Support\Sr::plural($orders->count(), 'porudžbina', 'porudžbine', 'porudžbina', false) }},
                            <strong>{{ $pairs }}</strong> {{ \App\Support\Sr::plural($pairs, 'par', 'para', 'pari', false) }},
                            ukupno <strong>@money($orders->sum('total'))</strong>.
                            Svi detalji (adresa, telefon, stavke, popusti) nalaze se u priloženom Excel fajlu.
                        </p>
                    </td>
                </tr>

                {{-- Orders --}}
                <tr>
                    <td style="padding: 16px 32px 24px">
                        <table width="100%" cellpadding="0" cellspacing="0" style="font-size: 13px; border-collapse: collapse">
                            <tr style="background: #f5f0eb">
                                <th align="left" style="padding: 8px; border-bottom: 1px solid #e7e1db">Porudžbina</th>
                                <th align="left" style="padding: 8px; border-bottom: 1px solid #e7e1db">Vreme</th>
                                <th align="left" style="padding: 8px; border-bottom: 1px solid #e7e1db">Kupac</th>
                                <th align="left" style="padding: 8px; border-bottom: 1px solid #e7e1db">Status</th>
                                <th align="right" style="padding: 8px; border-bottom: 1px solid #e7e1db">Ukupno</th>
                            </tr>
                            @foreach($orders as $order)
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #efe7e1"><a href="{{ route('admin.orders.show', $order) }}" style="color: #1c1917">{{ $order->number }}</a></td>
                                    <td style="padding: 8px; border-bottom: 1px solid #efe7e1">{{ $order->created_at->format('H:i') }}</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #efe7e1">{{ $order->name }}<br><span style="color: #57534e">{{ $order->city }}</span></td>
                                    <td style="padding: 8px; border-bottom: 1px solid #efe7e1">{{ \App\Models\Order::statusLabel($order->status) }}</td>
                                    <td align="right" style="padding: 8px; border-bottom: 1px solid #efe7e1; white-space: nowrap">@money($order->total)</td>
                                </tr>
                            @endforeach
                        </table>
                        <p style="margin: 12px 0 0; color: #57534e; font-size: 12px; line-height: 1.5">
                            „Nepotvrđene” porudžbine još čekaju da kupac unese kod iz emaila; automatski se otkazuju ako se ne potvrde na vreme.
                        </p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding: 16px 32px; border-top: 1px solid #e7e1db; color: #57534e; font-size: 12px">
                        Šalje se automatski svake noći u 00:00. Danima bez porudžbina email se ne šalje.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
