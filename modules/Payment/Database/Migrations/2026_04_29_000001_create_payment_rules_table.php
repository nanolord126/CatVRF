<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_rules', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('code', 100)->unique();
            $table->string('name');
            $table->text('description');
            $table->string('category', 50); // transaction_limits, fraud_detection, settlement, reporting
            $table->json('rule_data'); // JSON with actual rule parameters
            $table->string('version', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->string('created_by', 100);
            $table->timestamps();
            
            $table->uuid('previous_version_uuid')->nullable();
            $table->foreign('previous_version_uuid')
                  ->references('uuid')
                  ->on('payment_rules')
                  ->nullOnDelete();
            
            $table->index(['code', 'is_active']);
            $table->index(['category', 'is_active']);
            $table->index('effective_from');
            $table->index('effective_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_rules');
    }
};
