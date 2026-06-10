<?php

use App\Models\Lead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignUuid('lead_search_id')->nullable()->constrained('lead_searches')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'lead_id']);
            $table->index('lead_search_id');
        });

        Lead::query()
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(500, function ($leads) {
                $rows = $leads->map(fn (Lead $lead) => [
                    'user_id' => $lead->user_id,
                    'lead_id' => $lead->id,
                    'lead_search_id' => $lead->lead_search_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                if ($rows !== []) {
                    DB::table('lead_user')->insertOrIgnore($rows);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_user');
    }
};
