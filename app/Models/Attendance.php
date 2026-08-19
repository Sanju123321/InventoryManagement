<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'work_date',
        'status',
        'check_in',
        'check_out',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'work_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'present' => 'Present',
            'absent' => 'Absent',
            'half_day' => 'Half Day',
            'leave' => 'On Leave',
            'short_leave' => 'Short Leave',
            default => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'present' => 'bg-success',
            'absent' => 'bg-danger',
            'half_day' => 'bg-warning text-dark',
            'leave' => 'bg-info text-dark',
            'short_leave' => 'bg-primary',
            default => 'bg-secondary',
        };
    }
}
