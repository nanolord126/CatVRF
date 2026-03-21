<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id(); $table->string('payment_id')->unique();
            $table->decimal('amount', 12, 2); $table->json('splits');
            $table->string('fiscal_number')->nullable();
            $table->string('fiscal_sign')->nullable();
            $table->string('receipt_url')->nullable();
            $table->string('qr_code')->nullable();
            $table->string('status')->index(); 
            $table->string('correlation_id')->index(); $table->timestamps();
        });

        Schema::create('wallet_ledger', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained();
            $table->decimal('amount', 12, 2); $table->string('type'); // credit|debit
            $table->string('reason'); $table->string('reference')->nullable();
            $table->string('correlation_id')->index(); $table->timestamps();
        });
        
        Schema::table('users', fn($t) => $t->decimal('wallet_balance', 12, 2)->default(0));
    }
};
