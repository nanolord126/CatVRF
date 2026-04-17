<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_dispatcher_queue', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('ride_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status')->default('pending');
            $table->integer('priority')->default(2);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('timeout_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'ride_id']);
            $table->index(['tenant_id', 'driver_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'priority']);
            $table->index('timeout_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_dispatcher_queue');
    }
};
