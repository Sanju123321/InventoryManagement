<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('pending','approved','rejected','delivered','dispatched','paid') NOT NULL DEFAULT 'pending'");
            DB::table('sales_orders')->where('status', 'delivered')->update(['status' => 'dispatched']);
            DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('pending','approved','rejected','dispatched','paid') NOT NULL DEFAULT 'pending'");
        } else {
            DB::table('sales_orders')->where('status', 'delivered')->update(['status' => 'dispatched']);
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->boolean('receiving_ok')->default(false)->after('invoice_path');
            $table->timestamp('receiving_ok_at')->nullable()->after('receiving_ok');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['receiving_ok', 'receiving_ok_at']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('pending','approved','rejected','delivered','dispatched','paid') NOT NULL DEFAULT 'pending'");
            DB::table('sales_orders')->where('status', 'dispatched')->update(['status' => 'delivered']);
            DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('pending','approved','rejected','delivered','paid') NOT NULL DEFAULT 'pending'");
        } else {
            DB::table('sales_orders')->where('status', 'dispatched')->update(['status' => 'delivered']);
        }
    }
};
