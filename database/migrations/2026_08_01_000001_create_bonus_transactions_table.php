<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('bonus_transactions')) {
            return;
        }

        Schema::create('bonus_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bonus_id')->nullable()->constrained('bonuses')->nullOnDelete();
            $table->enum('type', ['referral', 'turnover', 'promo', 'loyalty', 'cashback', 'manual', 'debit', 'expiry']);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->string('description')->nullable();
            $table->string('source_type')->nullable()->comment('Model class that triggered the bonus');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('correlation_id')->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_transactions');
    }
};
