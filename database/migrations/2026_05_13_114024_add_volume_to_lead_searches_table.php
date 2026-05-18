<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_searches', function (Blueprint $table) {
            $table->integer('volume')->nullable()->default(10)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('lead_searches', function (Blueprint $table) {
            $table->dropColumn('volume');
        });
    }
};
