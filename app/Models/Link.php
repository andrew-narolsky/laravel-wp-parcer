<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Link extends Model
{
    protected $fillable = [
        'site_id', 'project_id', 'title', 'url', 'wp_url', 'anchor', 'text', 'image', 'type',
        'status', 'failed_reason', 'check_status', 'check_error', 'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
