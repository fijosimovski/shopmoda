<?php

namespace App\Models;

use App\Models\Concerns\HasTargets;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Coupon extends Model
{
    use HasTargets, SoftDeletes;

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED = 'fixed';

    public const TYPES = [
        self::TYPE_PERCENT => 'Percent off',
        self::TYPE_FIXED => 'Amount off (RSD)',
    ];

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_order_total',
        'max_discount',
        'usage_limit',
        'usage_limit_per_customer',
        'first_order_only',
        'members_only',
        'exclude_sale_items',
        'applies_to_all',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_total' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'usage_limit' => 'integer',
            'usage_limit_per_customer' => 'integer',
            'first_order_only' => 'boolean',
            'members_only' => 'boolean',
            'exclude_sale_items' => 'boolean',
            'applies_to_all' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Codes are always saved in uppercase, so "welcome10" and "WELCOME10" are the same coupon.
    protected function setCodeAttribute(?string $value): void
    {
        $this->attributes['code'] = Str::upper(trim((string) $value));
    }

    // ---------- Relationships ----------

    public function targets()
    {
        return $this->hasMany(CouponTarget::class);
    }

    // Only redemptions that still count (released ones are soft-deleted).
    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    // ---------- Scopes ----------

    // Switched on, inside its time window and not used up.
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')
                ->orWhere('usage_limit', '>', CouponRedemption::selectRaw('count(*)')
                    ->whereColumn('coupon_redemptions.coupon_id', 'coupons.id')
                    ->whereNull('coupon_redemptions.deleted_at')));
    }

    // ---------- Helpers ----------

    public static function findByCode(string $code): ?self
    {
        return static::with('targets')->where('code', Str::upper(trim($code)))->first();
    }

    // A random code that isn't taken yet, e.g. "SM-7KQ2-X9PD".
    public static function generateCode(): string
    {
        do {
            $code = 'SM-' . Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4));
        } while (static::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public function isSingleUse(): bool
    {
        return $this->usage_limit === 1;
    }

    // "10%" or "1.000 RSD"
    public function valueLabel(): string
    {
        return $this->type === self::TYPE_PERCENT
            ? rtrim(rtrim(number_format((float) $this->value, 2, '.', ''), '0'), '.') . '%'
            : Money::format((float) $this->value);
    }

    // "running", "scheduled", "expired", "used up" or "off"
    public function status(?int $used = null): string
    {
        $used ??= $this->redemptions()->count();

        return match (true) {
            !$this->is_active => 'off',
            $this->ends_at && $this->ends_at->isPast() => 'expired',
            $this->starts_at && $this->starts_at->isFuture() => 'scheduled',
            $this->usage_limit !== null && $used >= $this->usage_limit => 'used up',
            default => 'running',
        };
    }
}
