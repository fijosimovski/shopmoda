<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use App\Models\Concerns\HasTargets;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasImage, HasTargets, SoftDeletes;

    // Product-level (catalog): change the shoe's price, shown everywhere with the old price crossed out.
    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';
    // Cart-level: depend on what's in the cart, calculated in the cart / at checkout.
    public const TYPE_NTH_ITEM = 'nth_item';
    public const TYPE_CART_THRESHOLD = 'cart_threshold';

    public const TYPES = [
        self::TYPE_PERCENT => 'Percent off the price',
        self::TYPE_FIXED => 'Amount off the price',
        self::TYPE_NTH_ITEM => 'Every n-th pair % off (cheaper pair)',
        self::TYPE_CART_THRESHOLD => 'Amount off when spending at least X',
    ];

    public const CATALOG_TYPES = [self::TYPE_PERCENT, self::TYPE_FIXED];
    public const CART_TYPES = [self::TYPE_NTH_ITEM, self::TYPE_CART_THRESHOLD];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'type',
        'value',
        'nth',
        'min_cart_total',
        'applies_to_all',
        'priority',
        'is_stackable',
        'members_only',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'nth' => 'integer',
            'min_cart_total' => 'decimal:2',
            'applies_to_all' => 'boolean',
            'priority' => 'integer',
            'is_stackable' => 'boolean',
            'members_only' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // ---------- Relationships ----------

    public function targets()
    {
        return $this->hasMany(PromotionTarget::class);
    }

    public function orderDiscounts()
    {
        return $this->hasMany(OrderDiscount::class);
    }

    // ---------- Scopes ----------

    // Switched on and inside its time window right now.
    public function scopeRunning(Builder $query): void
    {
        $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    // ---------- Helpers ----------

    public function isCatalog(): bool
    {
        return in_array($this->type, self::CATALOG_TYPES, true);
    }

    public function isCartLevel(): bool
    {
        return in_array($this->type, self::CART_TYPES, true);
    }

    public function isRunning(): bool
    {
        return $this->is_active && $this->starts_at->isPast() && $this->ends_at->isFuture();
    }

    public function hasEnded(): bool
    {
        return $this->ends_at->isPast();
    }

    public function isUpcoming(): bool
    {
        return $this->starts_at->isFuture();
    }

    // "running", "upcoming", "ended" or "off" (switched off in the admin panel).
    public function status(): string
    {
        return match (true) {
            !$this->is_active => 'off',
            $this->hasEnded() => 'ended',
            $this->isUpcoming() => 'upcoming',
            default => 'running',
        };
    }

    // Applies a catalog promotion (percent / fixed) to a price.
    public function applyTo(float $price): float
    {
        $new = match ($this->type) {
            self::TYPE_PERCENT => $price * (100 - (float) $this->value) / 100,
            self::TYPE_FIXED => $price - (float) $this->value,
            default => $price,
        };

        return round(max(0, $new), 2);
    }

    // Short label for badges, e.g. "-20%", "-1.000 RSD", "2. par -30%", "-1.500 RSD preko 15.000 RSD"
    // (in the admin panel, English locale: "2nd pair -30%", "-1.500 RSD over 15.000 RSD").
    public function badge(): string
    {
        $value = (float) $this->value;
        $en = app()->isLocale('en');

        return match ($this->type) {
            self::TYPE_PERCENT => '-' . $this->percentLabel(),
            self::TYPE_FIXED => '-' . Money::format($value),
            self::TYPE_NTH_ITEM => self::ordinal($this->nth) . ($en ? ' pair -' : ' par -') . $this->percentLabel(),
            self::TYPE_CART_THRESHOLD => '-' . Money::format($value) . ($en ? ' over ' : ' preko ') . Money::format((float) $this->min_cart_total),
            default => $this->name,
        };
    }

    // 20.00 => "20%", 12.50 => "12.5%"
    public function percentLabel(): string
    {
        return rtrim(rtrim(number_format((float) $this->value, 2, '.', ''), '0'), '.') . '%';
    }

    // One sentence explaining the rule, for the promotion page.
    public function howItWorks(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENT, self::TYPE_FIXED => 'Popust je već uračunat u cene ispod.',
            self::TYPE_NTH_ITEM => 'Stavite ' . $this->nth . ' ili više pari iz ove akcije u korpu: svaki ' . self::ordinal($this->nth)
                . ' par dobija ' . $this->percentLabel() . ' popusta. Popust ide na jeftinije parove i obračunava se u korpi.',
            self::TYPE_CART_THRESHOLD => 'Potrošite najmanje ' . Money::format((float) $this->min_cart_total) . ' na proizvode iz ove akcije i dobijate '
                . Money::format((float) $this->value) . ' popusta na porudžbinu. Popust se obračunava u korpi.',
            default => '',
        };
    }

    // Serbian ordinals are written with a dot: 2 => "2."; in English (admin panel) 2 => "2nd".
    public static function ordinal(?int $n): string
    {
        $n = (int) $n;

        if (!app()->isLocale('en')) {
            return $n . '.';
        }

        $suffix = in_array($n % 100, [11, 12, 13]) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');

        return $n . $suffix;
    }
}
