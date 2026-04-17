<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fashion_sustainability_scores')) {
            Schema::create('fashion_sustainability_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->decimal('score', 3, 2)->default(0);
                $table->json('breakdown')->nullable();
                $table->timestamp('calculated_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique('product_id');
                $table->comment('Sustainability scores');
            });
        }

        if (!Schema::hasTable('fashion_churn_predictions')) {
            Schema::create('fashion_churn_predictions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->decimal('risk_score', 3, 2)->default(0);
                $table->timestamp('predicted_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id']);
                $table->comment('Churn predictions');
            });
        }

        if (!Schema::hasTable('fashion_cross_sells')) {
            Schema::create('fashion_cross_sells', function (Blueprint $table) {
                $table->id();
                $table->foreignId('source_product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('target_product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->timestamp('occurred_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['source_product_id', 'target_product_id']);
                $table->comment('Cross-sell tracking');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_cross_sells');
        Schema::dropIfExists('fashion_churn_predictions');
        Schema::dropIfExists('fashion_sustainability_scores');
    }
};
