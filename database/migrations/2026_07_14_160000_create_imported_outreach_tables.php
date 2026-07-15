<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imported_outreaches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('sender_identity_id')->nullable()->constrained('sender_identities')->nullOnDelete();
            $table->string('name');
            $table->string('delivery_mode')->default('draft');
            $table->string('subject_template');
            $table->text('body_template');
            $table->text('email_signature')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('delivery_mode');
        });

        Schema::create('imported_outreach_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('imported_outreach_id')->constrained('imported_outreaches')->cascadeOnDelete();
            $table->foreignUuid('imported_lead_id')->constrained('imported_leads')->cascadeOnDelete();
            $table->uuid('tracking_id')->nullable()->unique();
            $table->string('to_email');
            $table->string('subject')->nullable();
            $table->text('final_body')->nullable();
            $table->string('status')->default('pending');
            $table->text('failed_reason')->nullable();
            $table->timestamp('drafted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamps();

            $table->index('imported_outreach_id');
            $table->index('imported_lead_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_outreach_recipients');
        Schema::dropIfExists('imported_outreaches');
    }
};
