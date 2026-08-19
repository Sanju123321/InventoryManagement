<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    public const TYPES = ['casual', 'sick', 'unpaid', 'half_day', 'short', 'other'];

    protected $fillable = [
        'company_id',
        'user_id',
        'from_date',
        'to_date',
        'leave_type',
        'session',
        'start_time',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPartialDay(): bool
    {
        return in_array($this->leave_type, ['half_day', 'short'], true);
    }

    public function daysCount(): float
    {
        if ($this->leave_type === 'short') {
            return 0.25;
        }
        if ($this->leave_type === 'half_day') {
            return 0.5;
        }

        return (int) $this->from_date->diffInDays($this->to_date) + 1;
    }

    public function durationLabel(): string
    {
        return match ($this->leave_type) {
            'short' => '2 hrs',
            'half_day' => '0.5 day',
            default => (string) $this->daysCount(),
        };
    }

    public function timeDetail(): string
    {
        if ($this->leave_type === 'half_day') {
            return $this->session === 'afternoon' ? 'Afternoon' : 'Morning';
        }
        if ($this->leave_type === 'short' && $this->start_time) {
            $start = substr((string) $this->start_time, 0, 5);
            $end = Carbon::parse($start)->addHours(2)->format('H:i');

            return $start . '–' . $end;
        }

        return '';
    }

    /**
     * @return array<int, string> Y-m-d dates inclusive
     */
    public function dateRange(): array
    {
        return collect(CarbonPeriod::create($this->from_date, $this->to_date))
            ->map(fn (Carbon $date) => $date->toDateString())
            ->all();
    }

    public function employeeCanModify(): bool
    {
        return $this->to_date->gte(today());
    }

    public function typeLabel(): string
    {
        return match ($this->leave_type) {
            'casual' => 'Casual Leave',
            'sick' => 'Sick Leave',
            'unpaid' => 'Unpaid Leave',
            'half_day' => 'Half Day',
            'short' => 'Short Leave (2 hrs)',
            default => 'Other',
        };
    }

    public function attendanceStatus(): string
    {
        return match ($this->leave_type) {
            'half_day' => 'half_day',
            'short' => 'short_leave',
            default => 'leave',
        };
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'bg-warning text-dark',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
