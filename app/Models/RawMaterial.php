<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterial extends Model
{
    protected $fillable = ['company_id', 'name', 'unit', 'stock_qty', 'min_stock_alert', 'unit_cost', 'custom_unit'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function billOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class, 'material_id');
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'material_id');
    }
}
