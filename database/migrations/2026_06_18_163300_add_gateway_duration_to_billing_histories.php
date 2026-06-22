<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_histories', function (Blueprint $table) {
            $table->string('gateway')->default('Bank Transfer')->after('currency');
            $table->string('duration_note')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('billing_histories', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'duration_note']);
        });
    }
};
