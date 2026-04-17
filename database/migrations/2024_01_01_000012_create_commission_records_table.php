<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('vertical');
            $table->integer('amount'); // in kopeks
            $table->integer('commission'); // in kopeks
            $table->float('rate'); // percentage
            $table->string('operation_type');
            $table->integer('operation_id');
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamp('payout_scheduled_for')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('context')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'vertical']);
            $table->index(['tenant_id', 'status']);
            $table->index(['operation_type', 'operation_id']);
            $table->index('payout_scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_records');
    }
};
