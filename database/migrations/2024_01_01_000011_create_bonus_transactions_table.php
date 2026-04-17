<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // loyalty, referral, turnover, promo, migration
            $table->integer('amount'); // in kopeks
            $table->enum('status', ['pending', 'credited', 'expired'])->default('pending');
            $table->string('source_type')->nullable();
            $table->integer('source_id')->nullable();
            $table->timestamp('hold_until')->nullable();
            $table->timestamp('credited_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('hold_until');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_transactions');
    }
};
