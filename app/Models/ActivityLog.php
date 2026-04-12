<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'company_id',
        'company_name',
        'impersonated_by_id',
        'impersonated_by_name',
        'action',
        'description',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public static function actionBadgeClass(string $action): string
    {
        if (str_starts_with($action, 'auth.'))         return 'bg-primary';
        if (str_starts_with($action, 'impersonate.'))  return 'bg-dark';
        if (str_ends_with($action, '.created'))        return 'bg-success';
        if (str_ends_with($action, '.updated'))        return 'bg-warning text-dark';
        if (str_ends_with($action, '.deleted'))        return 'bg-danger';
        if (str_starts_with($action, 'sales.'))        return 'bg-info text-dark';
        if (str_starts_with($action, 'production.'))   return 'bg-secondary';
        if (str_starts_with($action, 'profile.'))      return 'bg-light text-dark border';
        if (str_ends_with($action, '.status_changed')) return 'bg-warning text-dark';
        return 'bg-secondary';
    }
}
