<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = ['created_by', 'title', 'body', 'target', 'channels', 'sent_at'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetLabel(): string
    {
        return match (true) {
            $this->target === 'all'           => 'All Companies',
            str_starts_with($this->target, 'plan:') => 'Plan: ' . ucfirst(substr($this->target, 5)),
            str_starts_with($this->target, 'company:') => 'Company #' . substr($this->target, 8),
            default => $this->target,
        };
    }
}
