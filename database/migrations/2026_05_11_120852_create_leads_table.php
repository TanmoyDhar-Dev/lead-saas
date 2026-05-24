<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('lead_search_id')->nullable()->constrained('lead_searches')->nullOnDelete();
            
            // Personal Info
            $table->string('full_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('position')->nullable();
            $table->text('address')->nullable();
            $table->text('bio')->nullable();
            $table->text('linkedin_url')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('company_email')->nullable();
            
            // Company Info
            $table->string('industry')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_website')->nullable();
            $table->text('company_linkedin')->nullable();
            $table->string('company_city')->nullable();
            $table->string('company_country')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_state')->nullable();
            $table->string('company_domain')->nullable();
            $table->text('company_description')->nullable();
            $table->string('company_annual_revenue')->nullable();
            $table->string('company_total_funding')->nullable();
            $table->text('company_technology')->nullable();
            
            $table->timestamps();

            $table->index('user_id');
            $table->index('lead_search_id');
            $table->index('personal_email');
            $table->index('company_email');
            $table->index('company_name');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
