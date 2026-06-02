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
        Schema::create('lead_automation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('lead_id')->constrained('leads')->onDelete('cascade');
            $table->string('email_sent')->default('no operation yet');
            $table->string('email_topic')->nullable();
            $table->text('email_body')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_role')->nullable();
            $table->string('sender_company')->nullable();
            $table->string('sender_region')->nullable();
            $table->string('sender_industry')->nullable();
            $table->string('sender_linkedin')->nullable();
            $table->string('sender_website')->nullable();
            $table->string('sender_eo_chapter')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_automation_details');
    }
};
