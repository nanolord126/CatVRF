<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type');
            $table->enum('channel', ['email', 'push', 'sms', 'telegram']);
            $table->string('subject_template');
            $table->text('body_template');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
