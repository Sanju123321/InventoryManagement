<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCost extends Model
{
    protected $fillable = ['company_id', 'product_id', 'production_cost', 'selling_price', 'profit'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
