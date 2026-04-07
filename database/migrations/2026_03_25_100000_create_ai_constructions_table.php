<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_constructions')) {
            return;
        }

        Schema::create('ai_constructions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('correlation_id')->index();

            $table->string('constructor_type')->comment('interior, beauty_look, outfit, cake, menu');
            $table->json('input_parameters')->comment('Явные параметры, которые задал пользователь');
            $table->json('used_taste_profile')->comment('Какие части профиля вкусов были использованы');
            $table->json('result')->comment('Сгенерированный результат конструктора');
            $table->float('confidence_score');

            $table->timestamps();

            $table->comment('История созданных пользователями AI-конструкций');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_constructions');
    }
};


