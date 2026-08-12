<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imported_outreaches', function (Blueprint $table) {
            $table->foreignUuid('parent_outreach_id')
                ->nullable()
                ->after('id')
                ->constrained('imported_outreaches')
                ->nullOnDelete();
        });

        Schema::table('imported_outreach_recipients', function (Blueprint $table) {
            $table->string('graph_message_id')
                ->nullable()
                ->after('tracking_id');

            $table->index('graph_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('imported_outreach_recipients', function (Blueprint $table) {
            $table->dropIndex(['graph_message_id']);
            $table->dropColumn('graph_message_id');
        });

        Schema::table('imported_outreaches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_outreach_id');
        });
    }
};
