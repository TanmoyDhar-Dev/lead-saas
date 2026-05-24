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
        Schema::dropIfExists('lead_automation_details');
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('connected_mailboxes');
        Schema::dropIfExists('sender_identities');
        Schema::dropIfExists('email_body_templates');
        Schema::dropIfExists('email_signature_templates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse for cleanup
    }
};
