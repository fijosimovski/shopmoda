<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

// For models with an "image" column (Brand, ProductModel).
// Use in Blade: <img src="{{ $brand->imageUrl() }}">
trait HasImage
{
    // Seeded placeholders are full URLs, uploaded images are files in storage.
    public function imageUrl(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (Str::startsWith($this->image, ['http://', 'https://'])) {
            return $this->image;
        }

        return asset('storage/' . $this->image);
    }
}
