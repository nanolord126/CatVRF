<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // We'll add fields to transactions table because bavix/laravel-wallet uses it
        // and we might want to store contract type there for payouts.
        // Assuming the common Laravel Wallet table 'transactions' exists in tenant schema.
        
        // However, the task mentions 'wallets' or 'payouts' tables. 
        // Let's create a dedicated payouts table in tenant schema to track business payouts.
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->string('contract_type')->default('standard'); // standard, gph
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->string('correlation_id')->nullable()->index();        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};

