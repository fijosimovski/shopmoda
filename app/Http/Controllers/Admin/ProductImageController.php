<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImageController extends Controller
{
    public function destroy(Product $product, ProductImage $image)
    {
        abort_unless((int) $image->product_id === (int) $product->id, 404);

        $wasMain = $image->is_main;
        $image->delete(); // soft delete, the file stays in storage

        // If the main image was removed, the next one becomes main.
        if ($wasMain) {
            $product->images()->first()?->update(['is_main' => true]);
        }

        return back()->with('success', 'Image removed.');
    }

    public function makeMain(Product $product, ProductImage $image)
    {
        abort_unless((int) $image->product_id === (int) $product->id, 404);

        $product->images()->update(['is_main' => false]);
        $image->update(['is_main' => true]);

        return back()->with('success', 'Main image changed.');
    }
}
