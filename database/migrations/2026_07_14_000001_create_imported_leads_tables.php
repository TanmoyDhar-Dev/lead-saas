<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('error_report')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });

        Schema::create('imported_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->string('organization_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->text('address')->nullable();
            $table->string('original_filename')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'organization_name']);
            $table->index('created_at');
        });

        Schema::create('imported_lead_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('imported_lead_id')->constrained('imported_leads')->cascadeOnDelete();
            $table->string('email');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['imported_lead_id', 'email']);
            $table->index('email');
        });

        Schema::create('imported_lead_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('imported_lead_id')->constrained('imported_leads')->cascadeOnDelete();
            $table->string('phone');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['imported_lead_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_lead_phones');
        Schema::dropIfExists('imported_lead_emails');
        Schema::dropIfExists('imported_leads');
        Schema::dropIfExists('import_batches');
    }
};
