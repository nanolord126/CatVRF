<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_staff_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id')->index();
            
            // Роль и статус
            $table->enum('role', ['owner', 'manager', 'waiter', 'cook', 'bartender', 'courier', 'cashier', 'host'])
                  ->index();
            $table->enum('status', ['scheduled', 'active', 'completed', 'cancelled', 'no_show'])
                  ->default('scheduled')
                  ->index();
            
            // Время смены
            $table->date('shift_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            
            // Статистика
            $table->integer('orders_handled')->default(0);
            $table->decimal('tips_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['tenant_id', 'restaurant_id']);
            $table->index(['restaurant_id', 'shift_date']);
            $table->index(['user_id', 'shift_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_staff_shifts');
    }
};
