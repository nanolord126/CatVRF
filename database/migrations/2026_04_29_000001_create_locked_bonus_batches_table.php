<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locked_bonus_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('original_amount', 18, 2)->unsigned();
            $table->decimal('remaining_locked', 18, 2)->unsigned();
            $table->decimal('daily_unlock_rate', 18, 4)->unsigned()->default(0.0667);
            $table->date('vested_until');
            $table->json('acceleration_history')->nullable();
            $table->string('source'); // purchase, referral, quest, streak_bonus, etc.
            $table->string('correlation_id')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'vested_until']);
            $table->index(['tenant_id', 'user_id']);
            $table->index('source');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locked_bonus_batches');
    }
};
