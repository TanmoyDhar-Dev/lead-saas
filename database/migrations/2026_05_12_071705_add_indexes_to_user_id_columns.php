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
        Schema::table('leads', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('source');
            $table->index('created_at');
        });
        Schema::table('lead_searches', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
            $table->index('started_at');
        });
        Schema::table('campaigns', function (Blueprint $table) {
            $table->index('user_id');
        });
        Schema::table('email_body_templates', function (Blueprint $table) {
            $table->index('user_id');
        });
        Schema::table('email_signature_templates', function (Blueprint $table) {
            $table->index('user_id');
        });
        Schema::table('sender_identities', function (Blueprint $table) {
            $table->index('user_id');
        });
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->index('user_id');
        });
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->index('campaign_id');
            $table->index('lead_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['source']);
            $table->dropIndex(['created_at']);
        });
        Schema::table('lead_searches', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['started_at']);
        });
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
        Schema::table('email_body_templates', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
        Schema::table('email_signature_templates', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
        Schema::table('sender_identities', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
        Schema::table('csv_imports', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropIndex(['campaign_id']);
            $table->dropIndex(['lead_id']);
        });
    }
};
