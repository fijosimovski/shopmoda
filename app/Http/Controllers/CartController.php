<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequest;
use App\Models\Product;
use App\Services\Cart;
use App\Services\Coupons;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Laravel creates the Cart object for us (dependency injection).
    public function __construct(private Cart $cart)
    {
    }

    public function index(Coupons $coupons)
    {
        return view('pages.cart.index', $this->cartPageData($coupons));
    }

    public function store(CartRequest $request)
    {
        $product = Product::active()->findOrFail($request->product_id);
        $size = $product->sizes()->where('size', $request->size)->first();

        if (!$size) {
            return $this->sizeError($request, 'Ova veličina ne postoji za izabrani model.');
        }

        $alreadyInCart = $this->cart->quantityOf($product->id, $size->size);

        if ($alreadyInCart + $request->quantity > $size->stock) {
            return $this->sizeError($request, 'Nažalost, u veličini ' . $size->size . ' na stanju je još samo ' . $size->stock . '.');
        }

        $this->cart->add($product->id, $size->size, (int) $request->quantity);
        $message = $product->name . ' (veličina ' . $size->size . ') je dodat u korpu.';

        // Sent by public/js/cart-drawer.js: no page reload, the cart drawer opens instead.
        if ($request->expectsJson()) {
            return $this->drawerResponse(Cart::key($product->id, $size->size), $message);
        }

        return redirect()->route('cart.index')->with('success', $message);
    }

    // Contents of the cart drawer, loaded when the bag icon in the navigation is clicked.
    public function drawer(Request $request)
    {
        if (!$request->expectsJson()) {
            return redirect()->route('cart.index');
        }

        return $this->drawerResponse();
    }

    // Quantity change. From the cart page with JavaScript (public/js/cart-page.js) it answers with JSON
    // and the redrawn cart (new row subtotal, promotions and total); without JavaScript it redirects back.
    public function update(Request $request, string $key)
    {
        $validator = validator($request->all(), ['quantity' => 'required|integer|min:1|max:10'], [], ['quantity' => 'količina']);

        if ($validator->fails()) {
            return $request->expectsJson()
                ? $this->cartPageResponse($validator->errors()->first('quantity'), $key, 422)
                : back()->withErrors($validator);
        }

        $item = $this->cart->items()->firstWhere('key', $key);

        if (!$item) {
            // The row is gone (removed in another tab, product hidden): just show the cart as it is now.
            return $request->expectsJson()
                ? $this->cartPageResponse('Ovaj proizvod više nije u korpi.')
                : back();
        }

        if ($request->quantity > $item['stock']) {
            $message = 'Nažalost, u veličini ' . $item['size'] . ' na stanju je još samo ' . $item['stock'] . '.';

            return $request->expectsJson()
                ? $this->cartPageResponse($message, $key, 422)
                : back()->with('error', $message);
        }

        $this->cart->update($key, (int) $request->quantity);

        if ($request->expectsJson()) {
            return $this->cartPageResponse('Korpa je ažurirana.');
        }

        return back()->with('success', 'Korpa je ažurirana.');
    }

    public function destroy(Request $request, string $key)
    {
        $this->cart->remove($key);
        $message = 'Proizvod je uklonjen iz korpe.';

        if ($request->expectsJson()) {
            // "Remove" on the cart page (cart-page.js sends view=page), otherwise inside the cart drawer.
            return $request->input('view') === 'page'
                ? $this->cartPageResponse($message)
                : $this->drawerResponse(message: $message);
        }

        return back()->with('success', $message);
    }

    // Size missing / not enough stock: an inline error for the drawer script, the usual error list otherwise.
    private function sizeError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'errors' => ['size' => [$message]]], 422);
        }

        return back()->withErrors(['size' => $message]);
    }

    // Everything the cart page (and its partial) needs.
    private function cartPageData(?Coupons $coupons = null): array
    {
        $items = $this->cart->items();

        return [
            'items' => $items,
            'totals' => $this->cart->totals($items), // subtotal, cart-level promotions, total
            'couponsAvailable' => ($coupons ?? app(Coupons::class))->anyAvailable(),
        ];
    }

    /**
     * JSON for public/js/cart-page.js:
     *   count    - pieces in the cart (the number next to the bag icon)
     *   html     - the redrawn cart page contents (partials/cart-page.blade.php)
     *   message  - shown / read out to screen readers
     *   errorKey - the row the error belongs to (quantity too high, invalid), or null
     * On an error ($status 422) the cart is unchanged and the html shows the last saved quantity.
     */
    private function cartPageResponse(?string $message = null, ?string $errorKey = null, int $status = 200)
    {
        $data = $this->cartPageData(); // first: items() also drops rows whose product is gone, so the count below matches

        return response()->json([
            'count' => $this->cart->count(),
            'html' => view('partials.cart-page', $data)->render(),
            'message' => $message,
            'errorKey' => $errorKey,
        ], $status);
    }

    /**
     * JSON for public/js/cart-drawer.js:
     *   count   - pieces in the cart (the number next to the bag icon)
     *   html    - the drawer contents (partials/cart-drawer-items.blade.php)
     *   message - read out by screen readers ("... je dodat u korpu.")
     * $addedKey highlights the row that was just added.
     */
    private function drawerResponse(?string $addedKey = null, ?string $message = null)
    {
        $items = $this->cart->items(); // first: it also drops rows whose product is gone, so the count below matches

        return response()->json([
            'count' => $this->cart->count(),
            'html' => view('partials.cart-drawer-items', [
                'items' => $items,
                'totals' => $this->cart->totals($items),
                'addedKey' => $addedKey,
            ])->render(),
            'message' => $message,
        ]);
    }
}
