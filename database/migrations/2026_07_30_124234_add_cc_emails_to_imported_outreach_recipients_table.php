<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imported_outreach_recipients', function (Blueprint $table) {
            $table->json('cc_emails')->nullable()->after('to_email');
        });
    }

    public function down(): void
    {
        Schema::table('imported_outreach_recipients', function (Blueprint $table) {
            $table->dropColumn('cc_emails');
        });
    }
};
