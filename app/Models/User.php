<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone_number',
        'password',
        'role',
        'status',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── RBAC helpers ──────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCompanyUser(): bool
    {
        return in_array($this->role, ['admin', 'sales_admin', 'inventory_admin']);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return in_array($this->role, $roles);
    }

    public function hasPermission(string $permission): bool
    {
        $role = Role::with('permissions')->where('slug', $this->role)->first();
        if (! $role) {
            return false;
        }
        return $role->permissions->contains('slug', $permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }
        return false;
    }

    public function rolePermissions(): Collection
    {
        $role = Role::with('permissions')->where('slug', $this->role)->first();
        return $role ? $role->permissions : collect();
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            'superadmin'      => 'Super Admin',
            'admin'           => 'Company Admin',
            'sales_admin'     => 'Sales Admin',
            'inventory_admin' => 'Inventory Admin',
            default           => ucfirst($this->role),
        };
    }

    public function roleBadgeClass(): string
    {
        return match ($this->role) {
            'superadmin'      => 'bg-danger',
            'admin'           => 'bg-primary',
            'sales_admin'     => 'bg-success',
            'inventory_admin' => 'bg-info text-dark',
            default           => 'bg-secondary',
        };
    }
}
