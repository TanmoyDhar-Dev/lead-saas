<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('campaign_recipients', 'open_count')) {
            return;
        }

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->unsignedInteger('open_count')->default(0)->after('opened_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('campaign_recipients', 'open_count')) {
            return;
        }

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropColumn('open_count');
        });
    }
};
