<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Таблица рейтингов и отзывов на HR-бирже
        Schema::create('hr_exchange_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_exchange_task_id')->constrained('hr_exchange_tasks')->onDelete('cascade');
            $table->foreignId('response_id')->constrained('hr_exchange_responses')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users'); // Кто оценивает (Заказчик)
            $table->foreignId('employee_id')->constrained('users'); // Кого оценивают (Исполнитель)
            $table->integer('rating'); // 1-5 звезд
            $table->text('comment')->nullable();
            $table->json('ai_tags')->nullable(); // Теги от AI (дисциплина, вежливость, скорость)
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });

        // 2. Расширение профиля пользователя для хранения Trust Score
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('hr_trust_score', 4, 2)->default(5.00); // Динамический рейтинг 0-5
            $table->integer('completed_tasks_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['hr_trust_score', 'completed_tasks_count']);
        });
        Schema::dropIfExists('hr_exchange_reviews');
    }
};

