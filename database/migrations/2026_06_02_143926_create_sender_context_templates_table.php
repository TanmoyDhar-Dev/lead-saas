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
        Schema::create('sender_context_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_role')->nullable();
            $table->string('sender_company')->nullable();
            $table->string('sender_region')->nullable();
            $table->string('sender_industry')->nullable();
            $table->string('sender_linkedin')->nullable();
            $table->string('sender_website')->nullable();
            $table->string('sender_eo_chapter')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sender_context_templates');
    }
};
