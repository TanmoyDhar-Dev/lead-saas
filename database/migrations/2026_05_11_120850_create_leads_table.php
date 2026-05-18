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
            $table->text('main_search_query')->nullable();
            $table->string('country_by_search_param')->nullable();
            $table->string('city_by_search_param')->nullable();
            $table->string('person_name')->nullable();
            $table->text('personal__linkdin_url')->nullable();
            $table->text('personal_linkdin_bio')->nullable();
            $table->text('personal_profile_about')->nullable();
            $table->text('personal_address_with_country')->nullable();
            $table->string('position_by_search_param')->nullable();
            $table->string('position_by_apifiapi')->nullable();
            $table->string('personal_email_address')->nullable();
            $table->string('industry_by_search_param')->nullable();
            $table->string('industry_by_apifyapi')->nullable();
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->text('company_website')->nullable();
            $table->text('company_linkdin_url')->nullable();
            $table->string('email_sent')->default('no operation yet');
            $table->string('source')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('personal_email_address');
            $table->index('company_name');
            $table->index('email_sent');
            $table->index('country_by_search_param');
            $table->index('city_by_search_param');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
