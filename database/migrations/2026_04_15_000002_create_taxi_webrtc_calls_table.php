<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_webrtc_calls', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('ride_id')->constrained('taxi_rides')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('caller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('callee_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['initiated', 'ringing', 'in_progress', 'completed', 'failed', 'cancelled'])->default('initiated');
            $table->string('signaling_key', 255)->unique();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('end_reason', 255)->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 100)->nullable();
            $table->timestamps();

            $table->index(['ride_id', 'tenant_id']);
            $table->index(['caller_id', 'callee_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_webrtc_calls');
    }
};
