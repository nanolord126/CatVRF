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
        Schema::create('aml_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('order_id')->nullable()->constrained();
            $table->foreignId('payment_transaction_id')->nullable()->constrained();
            $table->string('operation_type');
            $table->decimal('amount', 18, 2);
            $table->json('risk_factors')->nullable();
            $table->integer('risk_score')->default(0);
            $table->string('kyc_level')->default('simplified');
            $table->boolean('passed')->default(true);
            $table->text('block_reason')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['tenant_id', 'user_id', 'checked_at']);
            $table->index(['risk_score', 'passed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aml_checks');
    }
};
