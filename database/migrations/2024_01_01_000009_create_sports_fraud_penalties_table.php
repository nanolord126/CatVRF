<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports_fraud_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('fraud_type');
            $table->decimal('risk_score', 5, 2);
            $table->string('penalty_type');
            $table->json('penalty_details');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->index(['user_id', 'fraud_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_fraud_penalties');
    }
};
