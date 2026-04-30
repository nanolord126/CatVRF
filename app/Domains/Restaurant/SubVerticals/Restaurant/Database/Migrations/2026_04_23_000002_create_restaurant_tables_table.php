<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('table_number')->unique();
            $table->string('name')->nullable();
            $table->string('zone')->nullable()->comment('Зал/зона (терраса, VIP, главный зал)');
            $table->integer('seats')->default(4)->comment('Количество мест');
            $table->enum('status', ['available', 'occupied', 'reserved', 'cleaning', 'maintenance'])
                  ->default('available')
                  ->index();
            $table->boolean('is_accessible')->default(true)->comment('Доступен для бронирования');
            $table->boolean('is_vip')->default(false);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');

            $table->index(['tenant_id', 'restaurant_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['restaurant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
