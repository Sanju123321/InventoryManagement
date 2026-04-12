<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['company_name', 'business_type', 'phone', 'status', 'plan', 'plan_expires_at'];

    protected $casts = [
        'plan_expires_at' => 'date',
    ];

    // ── Relations ─────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function rawMaterials(): HasMany
    {
        return $this->hasMany(RawMaterial::class);
    }

    // ── Plan helpers ───────────────────────────────────────────

    public function planLabel(): string
    {
        return match ($this->plan ?? 'free') {
            'basic' => 'Basic',
            'pro'   => 'Pro',
            default => 'Free',
        };
    }

    public function planColor(): string
    {
        return match ($this->plan ?? 'free') {
            'basic' => 'primary',
            'pro'   => 'success',
            default => 'secondary',
        };
    }

    public function isPlanExpired(): bool
    {
        if (! $this->plan_expires_at) {
            return false;
        }

        return $this->plan_expires_at->isPast();
    }

    public function planConfig(): array
    {
        // No restrictions — unlimited for all plans
        return [
            'max_users'     => -1,
            'max_products'  => -1,
            'max_materials' => -1,
        ];
    }
}
