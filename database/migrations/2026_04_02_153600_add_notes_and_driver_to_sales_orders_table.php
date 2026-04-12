<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('approved_by');
            $table->string('driver_name')->nullable()->after('notes');
            $table->string('driver_whatsapp', 15)->nullable()->after('driver_name');
            $table->string('driver_vehicle')->nullable()->after('driver_whatsapp');
            $table->date('delivery_date')->nullable()->after('driver_vehicle');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['notes', 'driver_name', 'driver_whatsapp', 'driver_vehicle', 'delivery_date']);
        });
    }
};
