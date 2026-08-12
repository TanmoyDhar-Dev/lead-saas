<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graph_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subscription_id');
            $table->string('resource');
            $table->string('client_state');
            $table->timestamp('expiration_date');
            $table->timestamps();

            $table->index('user_id');
            $table->index('subscription_id');
            $table->index('expiration_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graph_subscriptions');
    }
};
