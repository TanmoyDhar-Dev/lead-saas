<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update any existing null timestamps to prevent issues
        DB::statement('UPDATE leads SET created_at = NOW() WHERE created_at IS NULL');
        DB::statement('UPDATE leads SET updated_at = NOW() WHERE updated_at IS NULL');

        // Set defaults for PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE leads ALTER COLUMN created_at SET DEFAULT NOW()');
            DB::statement('ALTER TABLE leads ALTER COLUMN updated_at SET DEFAULT NOW()');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE leads ALTER COLUMN created_at DROP DEFAULT');
            DB::statement('ALTER TABLE leads ALTER COLUMN updated_at DROP DEFAULT');
        }
    }
};
