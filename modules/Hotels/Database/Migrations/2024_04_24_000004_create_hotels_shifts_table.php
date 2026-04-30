<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('venue_id')->constrained('hotels_venues')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('uuid')->unique();
            $table->string('role'); // receptionist, housekeeper, manager, etc.
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->enum('status', ['scheduled', 'active', 'completed', 'cancelled'])->default('scheduled');
            $table->string('location')->nullable();
            $table->json('assigned_tasks')->nullable();
            $table->json('breaks')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'venue_id', 'status']);
            $table->index(['tenant_id', 'user_id', 'start_time']);
            $table->index(['tenant_id', 'venue_id', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels_shifts');
    }
};
