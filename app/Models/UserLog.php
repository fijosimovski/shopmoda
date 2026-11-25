<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class UserLog extends Model
{
    use MassPrunable;
    use SoftDeletes;

    // Logs are deleted for good after this many months by `model:prune` (weekly, see routes/console.php).
    public const KEEP_MONTHS = 3;

    // Failures saved per IP within FAILURE_WINDOW_SECONDS. A bot hammering the site would otherwise
    // fill this table; after the limit one "log.flood" row is written and the rest is skipped
    // (every request is still on admin > Visits).
    public const MAX_FAILURES_PER_IP = 30;
    public const FAILURE_WINDOW_SECONDS = 600;

    protected $fillable = ['user_id', 'action', 'description', 'is_failure', 'subject_type', 'subject_id', 'ip_address'];

    protected $casts = [
        'is_failure' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    // The thing the action was done on (an Order, a Product...).
    public function subject()
    {
        return $this->morphTo()->withTrashed();
    }

    // What model:prune deletes (also soft-deleted rows; they are removed for good).
    public function prunable()
    {
        return static::withTrashed()->where('created_at', '<', now()->subMonths(self::KEEP_MONTHS));
    }

    /**
     * Save one log row. Call it from controllers, e.g.:
     *   UserLog::record('login', 'Logged in');
     *   UserLog::record('order.created', 'Order ' . $order->number . ' placed', $order);
     * The logged-in user and the IP address are filled in automatically.
     */
    public static function record(string $action, ?string $description = null, ?Model $subject = null): self
    {
        return static::write($action, $description, $subject, false);
    }

    /**
     * Save something important that failed or was refused (not ordinary form mistakes), e.g.:
     *   UserLog::recordFailure('login.failed', 'Wrong password for ana@example.com', $user);
     * Shown in red on the User logs page. Returns null when this IP already hit MAX_FAILURES_PER_IP.
     * Never put passwords or codes in the description.
     */
    public static function recordFailure(string $action, ?string $description = null, ?Model $subject = null): ?self
    {
        $key = 'failure-logs:' . request()->ip();
        $count = RateLimiter::hit($key, self::FAILURE_WINDOW_SECONDS);

        if ($count > self::MAX_FAILURES_PER_IP) {
            return null;
        }

        $log = static::write($action, $description, $subject, true);

        if ($count === self::MAX_FAILURES_PER_IP) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            static::write('log.flood', self::MAX_FAILURES_PER_IP . ' failures from this IP: more are not logged for ' . $minutes . ' ' . Str::plural('minute', $minutes) . ' (see admin > Visits)', null, true);
        }

        return $log;
    }

    private static function write(string $action, ?string $description, ?Model $subject, bool $failure): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description === null ? null : Str::limit($description, 250),
            'is_failure' => $failure,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()->ip(),
        ]);
    }
}
