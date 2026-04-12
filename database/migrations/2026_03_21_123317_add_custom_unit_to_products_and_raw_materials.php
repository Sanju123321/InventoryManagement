<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('custom_unit')->nullable()->after('unit');
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->string('custom_unit')->nullable()->after('unit');
        });

        // Default existing NULL units to 'PCS'
        DB::table('products')->whereNull('unit')->update(['unit' => 'PCS']);
        DB::table('raw_materials')->whereNull('unit')->update(['unit' => 'PCS']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('custom_unit');
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropColumn('custom_unit');
        });
    }
};
