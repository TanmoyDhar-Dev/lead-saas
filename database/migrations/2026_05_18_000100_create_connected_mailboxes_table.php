<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connected_mailboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email_address')->nullable();
            $table->enum('provider', ['google-mail', 'outlook']);
            $table->string('maton_connection_id')->unique();
            $table->enum('status', ['active', 'disconnected'])->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connected_mailboxes');
    }
};
