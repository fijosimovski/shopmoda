<?php

namespace App\Models;

use App\Models\Concerns\HasImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Announcement extends Model
{
    use HasImage, SoftDeletes;

    public const TYPE_DROP = 'drop';
    public const TYPE_RESTOCK = 'restock';
    public const TYPE_PROMO = 'promo';

    public const TYPES = [
        self::TYPE_DROP => 'New drop',
        self::TYPE_RESTOCK => 'Restock',
        self::TYPE_PROMO => 'Promotion',
    ];

    // An image whose file name contains this word is shown as the bar's background instead of a thumbnail.
    public const BACKGROUND_MARK = 'background';

    public const SHOW_HOME = 'home';
    public const SHOW_ALL = 'all';

    public const SHOW_ON = [
        self::SHOW_HOME => 'Homepage only',
        self::SHOW_ALL => 'All shop pages',
    ];

    // What the "Shop now" link can point at => key used in the admin form ("product:12").
    public const LINKABLES = [
        'product' => Product::class,
        'model' => ProductModel::class,
        'promotion' => Promotion::class,
    ];

    protected $fillable = [
        'type',
        'title',
        'body',
        'show_on',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // ---------- Relationships ----------

    public function linkable()
    {
        return $this->morphTo();
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

    // "running", "upcoming", "ended" or "off" (same words as promotions, for partials.discount-status).
    public function status(): string
    {
        return match (true) {
            !$this->is_active => 'off',
            $this->ends_at->isPast() => 'ended',
            $this->starts_at->isFuture() => 'upcoming',
            default => 'running',
        };
    }

    // Badge text in the bar (Serbian), and in the admin list (English locale).
    public function label(): string
    {
        if (app()->isLocale('en')) {
            return match ($this->type) {
                self::TYPE_DROP => 'New drop',
                self::TYPE_RESTOCK => 'Restock',
                default => 'Promo',
            };
        }

        return match ($this->type) {
            self::TYPE_DROP => 'Novo',
            self::TYPE_RESTOCK => 'Ponovo na stanju',
            default => 'Akcija',
        };
    }

    // True when the image's file name contains "background": it fills the bar instead of being a thumbnail.
    public function imageIsBackground(): bool
    {
        return $this->image && Str::contains(Str::lower(basename($this->image)), self::BACKGROUND_MARK);
    }

    // Inline style that hands the image to .announce-slide-bg (shopmoda.css). Quotes, brackets and spaces
    // are percent-encoded, so the URL can't break out of url("...").
    public function backgroundStyle(): string
    {
        $url = str_replace(['"', "'", '(', ')', ' ', '\\'], ['%22', '%27', '%28', '%29', '%20', '%5C'], (string) $this->imageUrl());

        return '--announce-bg: url("' . $url . '")';
    }

    // Text before the countdown.
    public function countdownLabel(): string
    {
        return match ($this->type) {
            self::TYPE_DROP => 'Stiže za',
            self::TYPE_RESTOCK => 'Ponovo za',
            default => 'Ističe za',
        };
    }

    // Shown by countdown.js when the time is up (before the next page load hides the announcement).
    public function countdownDone(): string
    {
        return match ($this->type) {
            self::TYPE_DROP => 'Stiglo je',
            self::TYPE_RESTOCK => 'Ponovo na stanju',
            default => 'Završeno',
        };
    }

    // URL of the linked product / model / promotion, null when there is none (or it was deleted).
    public function url(): ?string
    {
        $target = $this->linkable;

        return match (true) {
            $target instanceof Product => route('products.show', $target),
            $target instanceof ProductModel => route('products.index', ['model' => $target->slug]),
            $target instanceof Promotion => route('promotions.show', $target),
            default => null,
        };
    }

    // "product:12" for the admin form select.
    public function linkKey(): ?string
    {
        $key = array_search($this->linkable_type, self::LINKABLES, true);

        return $key && $this->linkable_id ? $key . ':' . $this->linkable_id : null;
    }

    // Sets linkable_type / linkable_id from "product:12" (empty = no link).
    public function setLink(?string $key): void
    {
        [$type, $id] = array_pad(explode(':', (string) $key, 2), 2, null);

        $this->linkable_type = self::LINKABLES[$type] ?? null;
        $this->linkable_id = $this->linkable_type ? (int) $id : null;
    }

    // Announcements for the bar on the current page, newest start first.
    public static function forPage(bool $isHome)
    {
        return static::running()
            ->when(!$isHome, fn ($q) => $q->where('show_on', self::SHOW_ALL))
            ->with('linkable')
            ->orderByDesc('starts_at')
            ->get();
    }
}
