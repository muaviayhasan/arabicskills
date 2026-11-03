<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Option extends Model
{
    use HasFactory;

    /**
     * Where the settings are remembered between requests.
     */
    public const CACHE_KEY = 'app.options';

    protected $fillable = [
        'key', 'value',
    ];

    /**
     * Every admin setting, key to value.
     *
     * Ten rows that change a few times a year, read on every web request to
     * fill config('options.*'). They are remembered so that serving a page
     * does not need the database for them at all. The long window is only a
     * safety net: any write through this model clears the memo below, so a
     * change on the Settings page is live on the next request.
     *
     * @return array<string, string|null>
     */
    public static function settings(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addHours(24),
            fn () => static::query()->pluck('value', 'key')->all()
        );
    }

    /**
     * Copy the settings into config, so views can read config('options.logo')
     * without a query. Kept here beside the cache rather than inline in the
     * service provider, so the behaviour can be exercised directly: a test
     * always counts as running in console, and the provider skips console.
     */
    public static function applyToConfig(): void
    {
        foreach (static::settings() as $key => $value) {
            config()->set("options.{$key}", $value);
        }
    }

    public static function forgetSettings(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        // Hooked to the model rather than to the Settings screen, so any
        // write clears it, whichever code did the writing.
        static::saved(fn () => static::forgetSettings());
        static::deleted(fn () => static::forgetSettings());
    }
}
