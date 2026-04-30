<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_seller_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('type', [
                'revenue_growth',
                'top_product',
                'pricing_recommendation',
                'return_problem',
                'category_performance',
                'general',
            ])->default('general');
            $table->string('title');
            $table->text('description');
            $table->string('value')->nullable();
            $table->enum('impact', ['high', 'medium', 'low'])->default('medium');
            $table->boolean('actionable')->default(false);
            $table->timestamp('generated_at');
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index(['tenant_id', 'expires_at']);
            $table->index(['tenant_id', 'impact']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_seller_insights');
    }
};
