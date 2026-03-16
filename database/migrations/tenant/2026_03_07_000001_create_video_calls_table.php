<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('video_calls', function (Blueprint $table) {
            $table->id();
            $table->string('room_id')->unique();
            $table->foreignId('caller_id')->constrained('users');
            $table->foreignId('receiver_id')->nullable()->constrained('users');
            $table->string('status')->default('initiated'); // initiated, active, ended
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('recording_path')->nullable();
            $table->string('correlation_id')->index();
            $table->timestamps(); $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('video_calls'); }
};
