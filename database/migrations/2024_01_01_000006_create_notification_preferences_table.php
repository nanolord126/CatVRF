<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('channel', ['email', 'push', 'sms', 'telegram']);
            $table->boolean('enabled')->default(true);
            $table->string('quiet_hours_start', 5)->nullable();
            $table->string('quiet_hours_end', 5)->nullable();
            $table->timestamps();
            
            $table->unique(['tenant_id', 'user_id', 'channel']);
            $table->index(['user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
