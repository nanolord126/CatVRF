<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: HR exchange platform tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade'); // Кто ищет
            $table->string('title'); // Например: "Нужен официант на банкет 07.03"
            $table->text('description');
            $table->enum('category', ['RESTAURANT', 'CLINIC', 'TAXI', 'DELIVERY', 'GENERAL'])->default('GENERAL');
            $table->decimal('reward_amount', 15, 2); // Оплата за смену
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->integer('slots_available')->default(1);
            $table->enum('status', ['OPEN', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])->default('OPEN');
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // 2. Отклики сотрудников на задачи (Responses)
        Schema::create('hr_exchange_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_exchange_task_id')->constrained('hr_exchange_tasks')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('users'); // Кто откликнулся (User as Employee)
            $table->string('current_tenant_id'); // Из какого тенанта "арендуем"
            $table->enum('status', ['PENDING', 'ACCEPTED', 'REJECTED', 'FINISHED'])->default('PENDING');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_exchange_responses');
        Schema::dropIfExists('hr_exchange_tasks');
    }
};
