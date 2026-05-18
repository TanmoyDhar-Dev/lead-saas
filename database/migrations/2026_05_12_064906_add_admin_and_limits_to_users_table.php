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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->index();
            $table->string('status')->default('active')->index();
            
            $table->integer('lead_search_limit')->nullable();
            $table->integer('lead_export_limit')->nullable();
            $table->integer('lead_storage_limit')->nullable();
            $table->integer('campaign_limit')->nullable();
            $table->integer('email_send_limit')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->foreignId('created_by')->nullable()->index()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'role',
                'status',
                'lead_search_limit',
                'lead_export_limit',
                'lead_storage_limit',
                'campaign_limit',
                'email_send_limit',
                'notes',
                'created_by'
            ]);
        });
    }
};
