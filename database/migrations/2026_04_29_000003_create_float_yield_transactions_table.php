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
            $table->decimal('total_float', 18, 2)->unsigned(); // total locked amount used for calculation
            $table->decimal('platform_yield', 18, 2)->unsigned(); // platform's share (~0.06%)
            $table->decimal('user_yield', 18, 2)->unsigned(); // user's share (~0.012%)
            $table->decimal('yield_rate', 10, 6)->unsigned(); // actual rate applied
            $table->date('yield_date');
            $table->string('correlation_id')->unique();
            $table->timestamps();

            $table->index(['user_id', 'yield_date']);
            $table->index(['tenant_id', 'user_id']);
            $table->index('yield_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('float_yield_transactions');
    }
};
