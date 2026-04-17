<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports_live_streams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('trainer_id')->nullable()->constrained('sports_trainers')->onDelete('set null');
            $table->string('session_title');
            $table->text('session_description')->nullable();
            $table->dateTime('scheduled_start');
            $table->dateTime('scheduled_end');
            $table->string('stream_type')->default('group');
            $table->integer('max_participants')->default(50);
            $table->integer('current_participants')->default(0);
            $table->enum('status', ['scheduled', 'live', 'ended', 'cancelled'])->default('scheduled');
            $table->string('webrtc_room')->nullable();
            $table->string('stream_token')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->json('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->index(['status', 'scheduled_start']);
            $table->index('trainer_id');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_live_streams');
    }
};
