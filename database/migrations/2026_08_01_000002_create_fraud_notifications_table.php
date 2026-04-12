<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_notifications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('fraud_attempt_id')->constrained('fraud_attempts')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('severity', ['info', 'warning', 'high', 'critical'])->index();
            $table->string('title');
            $table->text('message');
            $table->json('channels')->nullable()->comment('Array of channels: email, push, telegram, sms, slack, in_app');
            $table->json('recipients')->nullable()->comment('User IDs or roles that received this notification');
            $table->enum('status', ['pending', 'sent', 'partially_sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->string('correlation_id')->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'severity', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_notifications');
    }
};
