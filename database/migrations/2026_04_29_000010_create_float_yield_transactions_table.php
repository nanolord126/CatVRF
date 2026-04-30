<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('float_yield_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('locked_batch_id')->nullable()->constrained('locked_bonus_batches')->onDelete('set null');
            $table->decimal('total_float', 18, 2)->unsigned()->comment('Total float amount at calculation time');
            $table->decimal('platform_yield', 18, 4)->unsigned()->comment('Platform earnings from float');
            $table->decimal('user_yield', 18, 4)->unsigned()->comment('User earnings from float');
            $table->decimal('platform_rate', 18, 6)->unsigned()->comment('Platform daily rate');
            $table->decimal('user_rate', 18, 6)->unsigned()->comment('User daily rate');
            $table->decimal('yield_rate', 18, 6)->unsigned()->nullable()->comment('Legacy yield rate field');
            $table->date('yield_date')->comment('Date for which yield was calculated');
            $table->string('correlation_id')->unique()->comment('Unique correlation ID for idempotency');
            $table->json('metadata')->nullable()->comment('Additional metadata');
            $table->timestamps();

            $table->index(['yield_date', 'user_id']);
            $table->index(['tenant_id', 'yield_date']);
            $table->index('created_at');
            $table->index('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('float_yield_transactions');
    }
};
