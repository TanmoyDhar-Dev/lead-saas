<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignUuid('lead_id')->constrained('leads')->cascadeOnDelete();
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
    }
};
