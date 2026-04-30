<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Staff table — управление сотрудниками в Tenant Panel.
     * CatVRF 2026 — PRODUCTION MANDATORY.
     *
     * Включает: персональные данные, контакты, фото, роли,
     * статистика эффективности, KPI, оценки, архивация.
     */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Tenant scoping
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Персональные данные
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('full_name')->virtualAs("CONCAT(last_name, ' ', first_name, ' ', COALESCE(middle_name, ''))");
            
            // Фото
            $table->string('photo_url')->nullable();
            $table->string('photo_path')->nullable();
            
            // Контакты
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('telegram')->nullable();
            $table->string('whatsapp')->nullable();
            
            // Доступ и авторизация
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('role')->default('employee'); // owner, manager, employee, accountant
            $table->json('permissions')->nullable();
            
            // Должность и отдел
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('staff')->onDelete('set null');
            
            // Данные о найме
            $table->date('hired_at')->nullable();
            $table->date('probation_end_at')->nullable();
            $table->string('employment_type')->default('full_time'); // full_time, part_time, contract
            $table->string('schedule')->nullable(); // График работы
            
            // Зарплата
            $table->decimal('salary', 12, 2)->default(0); // В копейках
            $table->string('salary_type')->default('monthly'); // monthly, hourly, per_order
            $table->json('salary_details')->nullable();
            
            // Статус
            $table->string('status')->default('active'); // active, inactive, on_vacation, probation, archived
            $table->timestamp('archived_at')->nullable();
            $table->text('archive_reason')->nullable();
            
            // Статистика эффективности (KPI)
            $table->integer('total_orders_processed')->default(0);
            $table->decimal('total_revenue_generated', 15, 2)->default(0); // В копейках
            $table->decimal('average_order_value', 12, 2)->default(0);
            $table->integer('total_customers_served')->default(0);
            $table->decimal('customer_satisfaction_score', 5, 2)->default(0); // 0-5
            $table->integer('positive_reviews')->default(0);
            $table->integer('negative_reviews')->default(0);
            $table->integer('complaints')->default(0);
            $table->integer('compliments')->default(0);
            
            // Эффективность работы
            $table->integer('tasks_completed')->default(0);
            $table->integer('tasks_overdue')->default(0);
            $table->decimal('task_completion_rate', 5, 2)->default(0);
            $table->integer('attendance_days')->default(0);
            $table->integer('absence_days')->default(0);
            $table->decimal('attendance_rate', 5, 2)->default(0);
            
            // Продажи и конверсии
            $table->integer('sales_count')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('upsell_rate', 5, 2)->default(0);
            $table->decimal('cross_sell_rate', 5, 2)->default(0);
            
            // Качество работы
            $table->decimal('quality_score', 5, 2)->default(0); // 0-5
            $table->decimal('speed_score', 5, 2)->default(0); // 0-5
            $table->decimal('accuracy_score', 5, 2)->default(0); // 0-5
            
            // Дополнительные метрики
            $table->integer('referrals_hired')->default(0);
            $table->integer('training_completed')->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->integer('shifts_worked')->default(0);
            
            // Оценка товаров/услуг (средний рейтинг по сотруднику)
            $table->decimal('average_product_rating', 5, 2)->default(0);
            $table->integer('products_rated')->default(0);
            
            // Метрики за текущий месяц
            $table->json('monthly_stats')->nullable();
            
            // Заметки и комментарии
            $table->text('notes')->nullable();
            $table->text('performance_notes')->nullable();
            
            // Дополнительные поля (flexible)
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'role']);
            $table->index(['tenant_id', 'department']);
            $table->index('email');
            $table->index('phone');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
