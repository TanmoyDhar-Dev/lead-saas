<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index('user_id');
        });

        Schema::create('category_imported_lead', function (Blueprint $table) {
            $table->foreignUuid('lead_category_id')->constrained('lead_categories')->cascadeOnDelete();
            $table->foreignUuid('imported_lead_id')->constrained('imported_leads')->cascadeOnDelete();

            $table->primary(['lead_category_id', 'imported_lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_imported_lead');
        Schema::dropIfExists('lead_categories');
    }
};
