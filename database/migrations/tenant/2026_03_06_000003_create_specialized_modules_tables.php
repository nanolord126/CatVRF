<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('specialized_modules_config')) {
            Schema::create('specialized_modules_config', function (Blueprint $table) {
                $table->comment('Конфигурация специализированных модулей.');
                $table->id();
                $table->string('module')->comment('Название модуля');
                $table->jsonb('config')->comment('Конфиг модуля');
                $table->timestamps();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('specialized_modules_config');
    }
};
