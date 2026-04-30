<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aml_checks', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->bigInteger('amount_kopecks');
            $table->string('currency', 3)->default('RUB');
            $table->decimal('risk_score', 5, 4); // 0.0000 to 1.0000
            $table->string('risk_level', 20); // low, medium, high, critical
            $table->string('kyc_level', 20); // simplified, full, enhanced
            $table->boolean('requires_full_kyc')->default(false);
            $table->boolean('is_reported_to_rosfinmonitoring')->default(false);
            $table->timestamp('reported_at')->nullable();
            $table->json('check_factors'); // velocity, geo, profile mismatch, etc.
            $table->text('reason')->nullable();
            $table->string('status', 20); // passed, review, blocked
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['user_id', 'checked_at']);
            $table->index(['tenant_id', 'checked_at']);
            $table->index('order_id');
            $table->index(['risk_level', 'status']);
            $table->index('is_reported_to_rosfinmonitoring');
            
            // 5-year retention for ФЗ-115 compliance
            $table->index('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aml_checks');
    }
};
