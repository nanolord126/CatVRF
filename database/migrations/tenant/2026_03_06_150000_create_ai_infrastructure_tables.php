<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('ai_infrastructure')) {
            Schema::create('ai_infrastructure', function (Blueprint $table) {
                $table->comment('AI инфраструктура.');
                $table->id();
                $table->string('service')->comment('Сервис (OpenAI, Claude, и т.д.)');
                $table->jsonb('config')->comment('Конфигурация');
                $table->timestamps();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('ai_infrastructure'); }
};
