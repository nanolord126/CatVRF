<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronics_serial_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('electronics_products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->string('serial_number', 100)->index();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->boolean('is_fraudulent')->default(false)->index();
            $table->decimal('fraud_probability', 5, 4)->default(0);
            $table->string('risk_level', 20)->index();
            $table->json('risk_factors')->nullable();
            $table->json('ml_features')->nullable();
            $table->string('recommended_action')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'serial_number']);
            $table->index(['user_id', 'is_fraudulent']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronics_serial_validations');
    }
};
