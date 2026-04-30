<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_device_id')->nullable()->constrained('iot_devices')->onDelete('set null');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Event classification
            $table->enum('event_type', [
                'authentication_success',
                'authentication_failure',
                'signature_verification_failed',
                'certificate_expired',
                'certificate_revoked',
                'token_expired',
                'token_revoked',
                'rate_limit_exceeded',
                'anomaly_detected',
                'quarantine_triggered',
                'quarantine_lifted',
                'mitm_attempt',
                'replay_attack',
                'device_spoofing',
                'unauthorized_access',
                'policy_violation',
            ])->comment('Type of security event');
            
            $table->enum('severity', ['info', 'warning', 'critical', 'emergency'])->default('warning');
            
            // Event details
            $table->text('description')->comment('Human-readable description');
            $table->json('event_data')->nullable()->comment('Structured event data');
            $table->string('source_ip')->nullable()->comment('Source IP address');
            $table->string('user_agent')->nullable()->comment('User agent string');
            $table->string('fingerprint')->nullable()->comment('Device or certificate fingerprint');
            
            // Response and resolution
            $table->enum('action_taken', [
                'none',
                'logged_only',
                'device_quarantined',
                'device_disabled',
                'connection_blocked',
                'alert_sent',
                'certificate_revoked',
                'token_revoked',
            ])->default('logged_only')->comment('Action taken in response');
            $table->text('action_details')->nullable()->comment('Details of action taken');
            $table->timestamp('resolved_at')->nullable()->comment('When the event was resolved');
            $table->text('resolution_notes')->nullable()->comment('Resolution notes');
            
            // Correlation
            $table->string('correlation_id')->nullable()->comment('Correlation ID for related events');
            $table->string('parent_event_id')->nullable()->comment('Parent event ID if this is a follow-up');
            
            $table->timestamps();
            $table->index(['tenant_id', 'event_type']);
            $table->index(['iot_device_id', 'event_type']);
            $table->index(['tenant_id', 'severity']);
            $table->index('created_at');
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_security_events');
    }
};
