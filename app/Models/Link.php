<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Link extends Model
{
    protected $fillable = ['site_id', 'title', 'url', 'wp_url', 'anchor', 'text', 'image', 'type', 'status', 'failed_reason'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
