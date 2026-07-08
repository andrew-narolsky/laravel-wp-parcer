<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Link extends Model
{
    protected $fillable = ['site_id', 'title', 'url', 'wp_url', 'anchor', 'text', 'image', 'type', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
