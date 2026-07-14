<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = ['name', 'url', 'login', 'password', 'is_active', 'posts_available', 'homepage_available'];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active'          => 'boolean',
        'posts_available'    => 'boolean',
        'homepage_available' => 'boolean',
    ];

    protected function url(): Attribute
    {
        return Attribute::make(
            set: fn(string $value) => rtrim($value, '/'),
        );
    }
}
