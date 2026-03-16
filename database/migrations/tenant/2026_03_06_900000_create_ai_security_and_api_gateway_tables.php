<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: AI security and API gateway tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('entity_type'); // Review, Ride, Transaction
            $table->unsignedBigInteger('entity_id');
            $table->float('probability')->default(0); // 0.0 to 1.0 (Potential Fraud)
            $table->string('flag_type'); // 'fake_review', 'gps_spoofing', 'payment_wash'
            $table->json('evidence')->nullable(); // AI reasoning markers
            $table->string('status')->default('pending'); // pending, confirmed, dismissed
            $table->uuid('correlation_id')->index();
            $table->timestamps();
        });

        // 2. API Gateway: Partner Access Management
        Schema::create('partner_api_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('partner_name');
            $table->string('api_key')->unique();
            $table->string('api_secret');
            $table->json('allowed_scopes')->nullable(); // ['taxi.rides.read', 'food.orders.write']
            $table->json('rate_limits')->nullable(); // { 'requests_per_minute': 100 }
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // 3. API Usage Logs for Billing & Security
        Schema::create('api_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partner_api_gateways');
            $table->string('endpoint');
            $table->string('method');
            $table->integer('response_code');
            $table->float('latency_ms');
            $table->string('ip_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_gateway_logs');
        Schema::dropIfExists('partner_api_gateways');
        Schema::dropIfExists('ai_fraud_detections');
    }
};
