<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create table to track seller-specific CLV models.
     * 
     * Stores metadata about per-seller fine-tuned models.
     */
    public function up(): void
    {
        Schema::create('seller_clv_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id')->unique();
            $table->unsignedBigInteger('tenant_id')->index();
            
            $table->string('model_path')->comment('Storage path to seller-specific model');
            $table->string('model_version', 50)->comment('Model version identifier');
            $table->timestamp('trained_at')->comment('When the model was trained');
            
            $table->json('training_metrics')->nullable()->comment('MAE, RMSE, R2 from training');
            $table->unsignedInteger('training_samples')->default(0)->comment('Number of samples used for training');
            
            $table->boolean('is_active')->default(true)->comment('Whether this model is currently in use');
            $table->timestamps();
            
            $table->index(['seller_id', 'tenant_id']);
            $table->index('trained_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_clv_models');
    }
};
