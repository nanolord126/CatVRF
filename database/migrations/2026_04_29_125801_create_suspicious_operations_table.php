<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suspicious_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('aml_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('operation_type');
            $table->decimal('amount', 18, 2);
            $table->integer('risk_score');
            $table->json('risk_factors');
            $table->text('suspicion_reason');
            $table->string('reporting_status')->default('pending'); // pending / reported / exempt
            $table->string('rosfinmonitoring_reference')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->json('report_payload')->nullable(); // XML/JSON sent to Rosfinmonitoring
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index('reporting_status');
            $table->index('reported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspicious_operations');
    }
};
