<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            
            //Foreign keys 
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignUuid('lead_search_id')->nullable()->index()->constrained('lead_searches')->nullOnDelete();
            
            // Personal Info
            $table->string('full_name')->nullable();
            $table->text('job_title')->nullable(); 
            $table->text('position')->nullable();  
            $table->text('address')->nullable();
            $table->text('bio')->nullable();
            $table->text('linkedin_url')->nullable()->unique();
            $table->string('personal_email')->nullable()->index(); 
            $table->string('company_email')->nullable()->index(); 
            
            // Company Info
            $table->string('industry')->nullable();
            $table->text('company_name')->nullable()->index(); 
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
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};