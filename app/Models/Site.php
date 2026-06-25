<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = ['name', 'url', 'login', 'password', 'is_active'];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected function url(): Attribute
    {
        return Attribute::make(
            set: fn(string $value) => rtrim($value, '/'),
        );
    }
}
