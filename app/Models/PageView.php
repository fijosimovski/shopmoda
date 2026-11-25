<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * One request to the site, written by the TrackPageViews middleware.
 * Shown on the admin Visits page; counted into daily totals by App\Services\VisitStats.
 */
class PageView extends Model
{
    use MassPrunable;

    // Raw rows are kept this many days, then deleted by `model:prune` (scheduled in routes/console.php).
    public const KEEP_DAYS = 60;

    public const DEVICES = ['desktop', 'mobile', 'tablet', 'bot'];

    public const UPDATED_AT = null; // rows are never changed

    protected $fillable = [
        'user_id', 'ip_address', 'visitor_hash', 'method', 'path', 'query', 'route_name', 'product_id',
        'status', 'duration_ms', 'referrer', 'user_agent', 'device',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // What model:prune deletes.
    public function prunable()
    {
        return static::where('created_at', '<', now()->subDays(self::KEEP_DAYS)->startOfDay());
    }

    // A page a person opened: what "views" and "visitors" count.
    public function scopeHumanViews($query)
    {
        return $query->where('method', 'GET')->where('device', '!=', 'bot')->where('status', '<', 400);
    }

    // Path + query as it was requested, e.g. /products?brand=nike
    public function url(): string
    {
        return '/' . ltrim($this->path, '/') . ($this->query ? '?' . $this->query : '');
    }

    public function referrerHost(): ?string
    {
        return $this->referrer ? parse_url($this->referrer, PHP_URL_HOST) : null;
    }
}
