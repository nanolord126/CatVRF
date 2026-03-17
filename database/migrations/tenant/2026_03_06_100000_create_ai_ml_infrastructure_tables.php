<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_ml_models')) {
            Schema::create('ai_ml_models', function (Blueprint $table) {
                $table->comment('AI/ML модели: embeddings, predictions.');
                $table->id();
                $table->string('name')->comment('Название модели');
                $table->string('type')->comment('Тип: embeddings, classifier, recommender');
                $table->jsonb('config')->comment('Конфигурация модели');
                $table->timestamps();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_ml_models');
    }
};
