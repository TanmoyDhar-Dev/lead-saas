<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campaign_recipients')) {
            return;
        }

        if (Schema::hasColumn('campaign_recipients', 'tracking_id')) {
            return;
        }

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->uuid('tracking_id')->nullable()->unique()->after('lead_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('campaign_recipients', 'tracking_id')) {
            Schema::table('campaign_recipients', function (Blueprint $table) {
                $table->dropColumn('tracking_id');
            });
        }
    }
};
