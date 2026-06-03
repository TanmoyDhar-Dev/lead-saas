<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sender_identities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('sender_name');
            $table->string('sender_role')->nullable();
            $table->string('sender_company')->nullable();
            $table->string('sender_region')->nullable();
            $table->string('sender_industry')->nullable();
            $table->text('sender_linkedin')->nullable();
            $table->text('sender_website')->nullable();
            $table->string('sender_eo_chapter')->nullable();
            $table->text('email_signature')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('sender_identity_id')->nullable()->constrained('sender_identities')->nullOnDelete();
            $table->string('name');
            $table->string('delivery_mode')->default('draft');
            $table->string('search_window')->default('qdr:m3');
            $table->text('email_main_body');
            $table->text('email_signature')->nullable();
            $table->integer('daily_limit')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status')->default('draft');
            $table->json('n8n_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_to_n8n_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('delivery_mode');
            $table->index('created_at');
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignUuid('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->uuid('tracking_id')->nullable()->unique();
            $table->string('status')->default('pending');
            $table->string('subject')->nullable();
            $table->text('hyper_personalized_line')->nullable();
            $table->text('final_email_body')->nullable();
            $table->string('email_topic')->nullable();
            $table->string('topic_source')->nullable();
            $table->text('website_summary')->nullable();
            $table->text('news_summary')->nullable();
            $table->text('product_summary')->nullable();
            $table->text('growth_summary')->nullable();
            $table->text('linkedin_summary')->nullable();
            $table->timestamp('drafted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('sender_identities');
    }
};
