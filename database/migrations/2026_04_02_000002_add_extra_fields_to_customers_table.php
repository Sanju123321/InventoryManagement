<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('authorized_person')->nullable()->after('address');
            $table->string('contact_details')->after('authorized_person');
            $table->string('gst_number', 20)->after('contact_details');
            $table->text('md_details')->nullable()->after('gst_number');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['authorized_person', 'contact_details', 'gst_number', 'md_details']);
        });
    }
};
