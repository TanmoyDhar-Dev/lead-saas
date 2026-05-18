<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_automation_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lead_id')->nullable();
            $table->foreignUuid('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignUuid('campaign_recipient_id')->nullable()->constrained('campaign_recipients')->nullOnDelete();
            $table->string('email_sent')->nullable();
            $table->string('email_topic')->nullable();
            $table->text('email_body')->nullable();
            $table->text('email_attachments')->nullable();
            $table->string('search_window')->nullable();
            $table->text('website_summary')->nullable();
            $table->text('news_summary')->nullable();
            $table->text('product_summary')->nullable();
            $table->text('growth_summary')->nullable();
            $table->text('linkedin_summary')->nullable();
            $table->text('topic_source')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_role')->nullable();
            $table->string('sender_company')->nullable();
            $table->string('sender_region')->nullable();
            $table->string('sender_industry')->nullable();
            $table->text('sender_linkedin')->nullable();
            $table->text('sender_website')->nullable();
            $table->string('sender_eo_chapter')->nullable();
            $table->timestamp('search_last_run_at')->nullable();
            $table->timestamps();

            $table->index('lead_id');
            $table->index('campaign_id');
            $table->index('email_sent');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_automation_details');
    }
};
