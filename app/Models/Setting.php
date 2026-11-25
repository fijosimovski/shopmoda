<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use SoftDeletes;

    public const ORDER_EMAIL = 'order_email';
    // Receives contact page messages. Empty = the order email is used.
    public const CONTACT_EMAIL = 'contact_email';
    // Shown on the contact page.
    public const CONTACT_PHONE = 'contact_phone';
    // Shop's Instagram profile; the navigation shows an Instagram icon when it is set.
    public const INSTAGRAM_URL = 'instagram_url';

    protected $fillable = ['key', 'value'];

    // Where contact page messages are mailed.
    public static function contactEmail(): string
    {
        return static::getValue(self::CONTACT_EMAIL)
            ?: static::getValue(self::ORDER_EMAIL)
            ?: config('mail.from.address');
    }

    // Setting::getValue('order_email')
    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    // Setting::setValue('order_email', 'orders@shopmoda.test')
    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
