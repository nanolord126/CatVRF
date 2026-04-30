<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_device_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iot_device_id')->constrained('iot_devices')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Certificate fields
            $table->string('certificate_fingerprint', 64)->unique()->comment('SHA-256 fingerprint of X.509 certificate');
            $table->text('public_key')->comment('Device public key (PEM format)');
            $table->text('certificate_pem')->nullable()->comment('Full X.509 certificate (PEM format)');
            $table->string('serial_number')->unique()->comment('Certificate serial number');
            $table->string('issuer')->comment('Certificate issuer (CA)');
            $table->timestamp('issued_at')->comment('Certificate issue date');
            $table->timestamp('expires_at')->comment('Certificate expiration date');
            $table->boolean('is_revoked')->default(false)->comment('Whether certificate is revoked');
            $table->timestamp('revoked_at')->nullable()->comment('Revocation timestamp');
            $table->string('revocation_reason')->nullable()->comment('Reason for revocation');
            
            // Security metadata
            $table->string('key_algorithm')->default('RSA')->comment('Key algorithm: RSA, ECDSA, Ed25519');
            $table->integer('key_bits')->nullable()->comment('Key size (e.g., 2048, 4096 for RSA)');
            $table->string('signature_algorithm')->comment('Signature algorithm');
            
            // Device attestation
            $table->string('device_mac_address')->nullable()->comment('MAC address for attestation');
            $table->string('device_serial')->nullable()->comment('Hardware serial number');
            $table->json('attestation_data')->nullable()->comment('Additional attestation data');
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes for fast lookups
            $table->index(['iot_device_id', 'is_revoked']);
            $table->index(['tenant_id', 'is_revoked']);
            $table->index('certificate_fingerprint');
            $table->index('serial_number');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_device_certificates');
    }
};
