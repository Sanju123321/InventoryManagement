<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->default(0)->after('customer_id');
            $table->unsignedTinyInteger('gst_rate')->default(18)->after('subtotal');
            $table->decimal('gst_amount', 12, 2)->default(0)->after('gst_rate');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('gst_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'gst_rate', 'gst_amount', 'discount_amount']);
        });
    }
};
