<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_tracking_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('campaign_recipient_id')->constrained('campaign_recipients')->cascadeOnDelete();
            $table->string('event_type');
            $table->text('clicked_url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['campaign_recipient_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_tracking_logs');
    }
};
