{{-- Email sent to the customer's email address after they place an order --}}
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

                {{-- Greeting --}}
                <tr>
                    <td style="padding: 32px 32px 8px">
                        <h2 style="margin: 0 0 12px; font-family: Georgia, 'Times New Roman', serif; font-weight: normal; font-size: 28px">Hvala na porudžbini, {{ $order->name }}!</h2>
                        <p style="margin: 0 0 8px; line-height: 1.5">
                            Primili smo vašu porudžbinu <strong>{{ $order->number }}</strong> i pripremamo je.
                            Iznos od <strong>@money($order->total)</strong> plaćate u gotovini prilikom preuzimanja paketa.
                        </p>
                        <p style="margin: 0; color: #57534e; font-size: 14px">
                            Poručeno {{ $order->created_at->format('d.m.Y. \u H:i') }}
                        </p>
                    </td>
                </tr>

                {{-- Items --}}
                <tr>
                    <td style="padding: 24px 32px 8px">
                        <h3 style="margin: 0 0 12px; font-size: 16px">Vaši proizvodi</h3>
                        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse">
                            @foreach($order->items as $item)
                                @php
                                    $image = $item->product->mainImage;
                                    $isRemote = $image && Str::startsWith($image->path, ['http://', 'https://']);
                                    $file = $image ? storage_path('app/public/' . $image->path) : null;
                                @endphp
                                <tr style="border-bottom: 1px solid #efe7e1">
                                    <td width="80" valign="top">
                                        {{-- Gmail can't load localhost URLs, so uploaded images are embedded inside the email --}}
                                        @if($isRemote)
                                            <img src="{{ $image->path }}" width="72" alt="{{ $item->product->name }}" style="display: block; border-radius: 0">
                                        @elseif($image && file_exists($file))
                                            <img src="{{ $message->embed($file) }}" width="72" alt="{{ $item->product->name }}" style="display: block; border-radius: 0">
                                        @endif
                                    </td>
                                    <td valign="top">
                                        <strong>{{ $item->product->name }}</strong><br>
                                        <span style="color: #57534e; font-size: 14px">
                                            Veličina {{ $item->size }} &middot; Kol. {{ $item->quantity }} &times; @money($item->unit_price)
                                        </span>
                                    </td>
                                    <td align="right" valign="top" style="white-space: nowrap">@money($item->subtotal())</td>
                                </tr>
                            @endforeach
                            {{-- Cart-level promotions and the coupon --}}
                            @if($order->discounts->isNotEmpty())
                                <tr>
                                    <td colspan="2" align="right" style="padding-top: 16px">Međuzbir</td>
                                    <td align="right" style="padding-top: 16px; white-space: nowrap">@money($order->subtotal)</td>
                                </tr>
                                @foreach($order->discounts as $discount)
                                    <tr>
                                        <td colspan="2" align="right" style="color: #b42318">{{ $discount->name }}</td>
                                        <td align="right" style="color: #b42318; white-space: nowrap">-@money($discount->amount)</td>
                                    </tr>
                                @endforeach
                            @endif
                            <tr>
                                <td colspan="2" align="right" style="padding-top: 16px"><strong>Ukupno za plaćanje pouzećem</strong></td>
                                <td align="right" style="padding-top: 16px; white-space: nowrap"><strong>@money($order->total)</strong></td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Delivery details --}}
                <tr>
                    <td style="padding: 24px 32px 8px">
                        <h3 style="margin: 0 0 12px; font-size: 16px">Podaci za dostavu</h3>
                        <table width="100%" cellpadding="12" cellspacing="0" style="background: #f5f0eb; border-radius: 0">
                            <tr>
                                <td style="line-height: 1.5">
                                    {{ $order->name }}<br>
                                    {{ $order->address }}, {{ $order->city }}<br>
                                    {{ $order->phone }}
                                    @if($order->note)
                                        <br><br><span style="color: #57534e">Vaša napomena:</span> {{ $order->note }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                        <p style="margin: 12px 0 0; color: #57534e; font-size: 14px; line-height: 1.5">
                            Proverite da li su podaci tačni. Kurir može da vas pozove na navedeni broj pre dostave.
                        </p>
                    </td>
                </tr>

                {{-- Call to action --}}
                <tr>
                    <td align="center" style="padding: 24px 32px">
                        @if($order->isGuest())
                            <a href="{{ route('products.index') }}" style="display: inline-block; padding: 14px 28px; background: #1c1917; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #ffffff; text-decoration: none; border-radius: 0">Nastavi kupovinu</a>
                        @else
                            <a href="{{ route('account.orders.show', $order) }}" style="display: inline-block; padding: 14px 28px; background: #1c1917; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #ffffff; text-decoration: none; border-radius: 0">Pogledaj porudžbinu</a>
                        @endif
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding: 20px 32px; border-top: 1px solid #e7e1db; color: #57534e; font-size: 13px; line-height: 1.5">
                        Imate pitanje o porudžbini? Odgovorite na ovaj email i navedite broj porudžbine {{ $order->number }}.<br>
                        &copy; {{ date('Y') }} ShopModa
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
