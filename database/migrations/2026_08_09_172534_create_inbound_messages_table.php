<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('imported_outreach_recipient_id')
                ->constrained('imported_outreach_recipients')
                ->cascadeOnDelete();
            $table->string('graph_message_id');
            $table->string('from_email');
            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->unique('graph_message_id');
            $table->index('imported_outreach_recipient_id');
            $table->index('from_email');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_messages');
    }
};
