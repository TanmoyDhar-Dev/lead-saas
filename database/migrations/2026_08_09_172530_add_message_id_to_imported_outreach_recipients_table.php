<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imported_outreach_recipients', function (Blueprint $table) {
            $table->string('message_id')->nullable()->after('tracking_id');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::table('imported_outreach_recipients', function (Blueprint $table) {
            $table->dropIndex(['message_id']);
            $table->dropColumn('message_id');
        });
    }
};
