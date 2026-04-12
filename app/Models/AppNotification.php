<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'company_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
    ];

    protected $casts = [
        'data'    => 'array',
        'is_read' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Icon class based on notification type. */
    public function iconClass(): string
    {
        return match ($this->type) {
            'low_stock_material'  => 'fas fa-exclamation-triangle text-warning',
            'low_stock_product'   => 'fas fa-box-open text-danger',
            'order_low_stock'     => 'fas fa-shopping-cart text-danger',
            'announcement'        => 'fas fa-bullhorn text-primary',
            'plan_expired'        => 'fas fa-ban text-danger',
            'plan_expiring_soon'  => 'fas fa-clock text-warning',
            default               => 'fas fa-bell text-info',
        };
    }

    /** Badge colour based on notification type. */
    public function badgeClass(): string
    {
        return match ($this->type) {
            'low_stock_material' => 'bg-warning text-dark',
            'low_stock_product'  => 'bg-danger',
            'order_low_stock'    => 'bg-danger',
            default              => 'bg-info',
        };
    }
}
