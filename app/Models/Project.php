<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['name'];

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public static function domainFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: parse_url("https://{$url}", PHP_URL_HOST);

        if (!$host || !str_contains($host, '.') || str_contains($host, ' ')) {
            return null;
        }

        return strtolower(preg_replace('/^www\./i', '', $host));
    }

    public static function resolveForUrl(string $url): ?self
    {
        $domain = self::domainFromUrl($url);

        return $domain ? self::firstOrCreate(['name' => $domain]) : null;
    }
}
