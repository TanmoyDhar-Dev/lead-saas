<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
