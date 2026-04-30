<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_kitchen_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('kitchen_station_id')->constrained('kitchen_stations')->onDelete('cascade');
            $table->enum('status', [
                'pending',
                'in_progress',
                'ready',
                'served',
                'cancelled',
                'problem',
            ])->default('pending');
            $table->enum('priority', [
                'normal',
                'high',
                'urgent',
                'vip',
                'emergency',
            ])->default('normal');
            $table->unsignedInteger('estimated_preparation_minutes')->default(15);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('problem_comment')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_from_marketplace')->default(false);
            $table->boolean('is_vip')->default(false);
            $table->timestamps();

            $table->index(['kitchen_station_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index(['kitchen_station_id', 'priority', 'created_at']);
            $table->index(['status', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_kitchen_statuses');
    }
};
