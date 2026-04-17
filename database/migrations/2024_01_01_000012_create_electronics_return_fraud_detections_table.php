<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronics_return_fraud_detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('electronics_products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('serial_number', 100)->index();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->string('return_reason')->nullable();
            $table->string('condition')->nullable();
            $table->boolean('is_fraudulent')->default(false)->index();
            $table->decimal('fraud_probability', 5, 4)->default(0);
            $table->string('risk_level', 20)->index();
            $table->json('risk_factors')->nullable();
            $table->json('ml_features')->nullable();
            $table->string('recommended_action')->nullable();
            $table->integer('hold_duration_minutes')->nullable();
            $table->timestamp('investigated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'serial_number']);
            $table->index(['user_id', 'is_fraudulent']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['is_fraudulent', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronics_return_fraud_detections');
    }
};
