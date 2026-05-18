<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sender_identities', function (Blueprint $table) {
            $table->string('status')->default('active')->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('sender_identities', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
