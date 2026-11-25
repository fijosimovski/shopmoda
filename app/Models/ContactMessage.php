<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactMessage extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'name', 'email', 'subject', 'message', 'ip_address'];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    // withTrashed() so a message from a deactivated user still shows who wrote it.
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
