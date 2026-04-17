<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports_stream_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stream_id')->constrained('sports_live_streams')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('participant_token')->unique();
            $table->dateTime('joined_at');
            $table->dateTime('left_at')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->unique(['stream_id', 'user_id']);
            $table->index(['stream_id', 'joined_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_stream_participants');
    }
};
