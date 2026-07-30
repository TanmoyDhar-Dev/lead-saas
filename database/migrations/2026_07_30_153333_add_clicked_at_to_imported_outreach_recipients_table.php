<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imported_outreach_recipients', function (Blueprint $table) {
            $table->timestamp('clicked_at')->nullable()->after('open_count');
        });
    }

    public function down(): void
    {
        Schema::table('imported_outreach_recipients', function (Blueprint $table) {
            $table->dropColumn('clicked_at');
        });
    }
};
