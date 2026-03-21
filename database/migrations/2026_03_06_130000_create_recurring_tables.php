<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('wallet_cards', function (Blueprint $t) {
            $this->common($t);
            $t->foreignId('user_id')->constrained();
            $t->string('token')->unique();
            $t->string('brand')->nullable();
            $t->string('last4', 4);
            $t->boolean('is_active')->default(1);
        });

        Schema::create('subscriptions', function (Blueprint $t) {
            $this->common($t);
            $t->foreignId('user_id')->constrained();
            $t->foreignId('wallet_card_id')->constrained();
            $t->string('plan_name');
            $t->decimal('amount', 12, 2);
            $t->string('period'); // monthly, weekly
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamp('last_payment_at')->nullable();
            $t->string('status')->index()->default('active'); // active, paused, cancelled
        });
    }

    private function common(Blueprint $t) {
        $t->id(); $t->string('correlation_id')->index(); $t->timestamps(); return $t;
    }
};
