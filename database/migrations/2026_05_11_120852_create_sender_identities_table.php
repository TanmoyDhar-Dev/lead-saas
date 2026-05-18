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
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sender_identities');
    }
};
