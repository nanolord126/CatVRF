<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('correlation_id')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'date']);
        });

        Schema::create('staff_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('TODO'); // TODO, IN_PROGRESS, DONE
            $table->string('priority')->default('medium');
            $table->morphs('taskable'); // For linking with Room or Booking
            $table->string('correlation_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_tasks');
        Schema::dropIfExists('staff_schedules');
    }
};
