<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'customer_id', 'firm_id', 'subtotal', 'gst_rate', 'gst_amount', 'discount_amount',
        'total_amount', 'paid_amount', 'pending_amount', 'status', 'created_by', 'approved_by',
        'notes', 'driver_name', 'driver_whatsapp', 'driver_vehicle', 'delivery_date', 'invoice_path',
        'receiving_ok', 'receiving_ok_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'pending_amount' => 'decimal:2',
        'receiving_ok' => 'boolean',
        'receiving_ok_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isFullyPaid(): bool
    {
        return (float) $this->pending_amount <= 0;
    }

    public function fulfillmentStatus(): string
    {
        return $this->status === 'paid' ? 'approved' : $this->status;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'dispatched' => 'Dispatched',
            default => ucfirst($this->status),
        };
    }

    public function canMarkDispatched(): bool
    {
        return in_array($this->status, ['approved', 'paid'], true);
    }

    public function canAssignDriver(): bool
    {
        return in_array($this->status, ['approved', 'dispatched', 'paid'], true);
    }

    public function isDispatched(): bool
    {
        return $this->status === 'dispatched';
    }

    public function canMarkReceivingOk(): bool
    {
        return $this->isDispatched() && $this->invoice_path && ! $this->receiving_ok;
    }
}
