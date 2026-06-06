<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['company_id', 'created_by', 'name', 'phone', 'email', 'address', 'google_location', 'authorized_person', 'contact_details', 'gst_number', 'md_details'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function customerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public static function normalizeGoogleLocation(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^https?:\/\//i', $value)) {
            $value = 'https://' . $value;
        }

        return $value;
    }

    /**
     * Best link for driver navigation — saved Maps URL, or search from address.
     */
    public function mapsNavigationUrl(): ?string
    {
        if ($this->google_location) {
            return self::normalizeGoogleLocation($this->google_location);
        }

        if ($this->address) {
            return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($this->address);
        }

        return null;
    }
}
