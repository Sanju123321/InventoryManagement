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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            // 'all', 'plan:free', 'plan:basic', 'plan:pro', or 'company:{id}'
            $table->string('target')->default('all');
            // comma-separated channels: 'in_app', 'email'
            $table->string('channels')->default('in_app');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();

            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
