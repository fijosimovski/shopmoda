<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    // Placed, but the customer hasn't entered the code from the email yet.
    public const STATUS_UNCONFIRMED = 'unconfirmed';
    public const STATUS_NEW = 'new';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    // Never confirmed: the email link ran out (set by the orders:expire command).
    public const STATUS_EXPIRED = 'expired';

    // Status names shown to customers and in the admin panel (the keys stay English in the database).
    public const STATUS_LABELS = [
        self::STATUS_UNCONFIRMED => 'Nepotvrđena',
        self::STATUS_NEW => 'Nova',
        self::STATUS_PROCESSING => 'U obradi',
        self::STATUS_SHIPPED => 'Poslata',
        self::STATUS_COMPLETED => 'Završena',
        self::STATUS_CANCELLED => 'Otkazana',
        self::STATUS_EXPIRED => 'Istekla',
    ];

    // All statuses in order, e.g. for a <select> in the admin panel.
    public const STATUSES = [
        self::STATUS_UNCONFIRMED,
        self::STATUS_NEW,
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    // Counted as sales in analytics: confirmed and being handled, or done.
    public const SALE_STATUSES = [
        self::STATUS_PROCESSING,
        self::STATUS_SHIPPED,
        self::STATUS_COMPLETED,
    ];

    // Not (yet) a sale: shown in their own section of the analytics.
    public const NON_SALE_STATUSES = [
        self::STATUS_UNCONFIRMED,
        self::STATUS_NEW,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    // The sneakers are back in stock and the coupon use is released.
    public const RELEASED_STATUSES = [
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'note',
        'subtotal',
        'discount_total',
        'coupon_code',
        'total',
        'status',
        'ip_address',
    ];

    // Order number: prefix + random characters. No 0/O, 1/I/L, so it's easy to read out on the phone.
    public const NUMBER_PREFIX = 'SM-';
    public const NUMBER_LENGTH = 8;
    private const NUMBER_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    // Never send the code hash to the browser / JSON.
    protected $hidden = ['confirmation_code'];

    // Every new order gets a random number (shown instead of the id to customers and in emails).
    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->number ??= static::newNumber();
        });
    }

    // A random, unused order number, e.g. "SM-7K3QX9P2" (31^8 combinations, so a repeat is very unlikely).
    public static function newNumber(): string
    {
        do {
            $number = self::NUMBER_PREFIX;
            for ($i = 0; $i < self::NUMBER_LENGTH; $i++) {
                $number .= self::NUMBER_ALPHABET[random_int(0, strlen(self::NUMBER_ALPHABET) - 1)];
            }
        } while (static::withTrashed()->where('number', $number)->exists());

        return $number;
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    // Empty for guest orders. withTrashed() so the order still shows a soft-deleted user.
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Cart-level promotions and the coupon applied at checkout.
    public function discounts()
    {
        return $this->hasMany(OrderDiscount::class);
    }

    // withTrashed(): a released redemption (cancelled order) is soft-deleted.
    public function couponRedemption()
    {
        return $this->hasOne(CouponRedemption::class)->withTrashed();
    }

    // Usage: Order::sales()->sum('total')
    public function scopeSales(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->whereIn('orders.status', self::SALE_STATUSES);
    }

    // "shipped" => "Poslata" in the shop, "Shipped" in the admin panel (English locale, see AdminLocale).
    public static function statusLabel(?string $status): string
    {
        if (app()->isLocale('en')) {
            return ucfirst((string) $status);
        }

        return self::STATUS_LABELS[$status] ?? ucfirst((string) $status);
    }

    public static function isReleasedStatus(string $status): bool
    {
        return in_array($status, self::RELEASED_STATUSES, true);
    }

    public function isUnconfirmed(): bool
    {
        return $this->status === self::STATUS_UNCONFIRMED;
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }
}
