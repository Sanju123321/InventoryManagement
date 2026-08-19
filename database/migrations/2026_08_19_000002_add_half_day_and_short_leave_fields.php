<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('session', 20)->nullable()->after('leave_type'); // morning, afternoon (half day)
            $table->time('start_time')->nullable()->after('session'); // short leave start
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['session', 'start_time']);
        });
    }
};
