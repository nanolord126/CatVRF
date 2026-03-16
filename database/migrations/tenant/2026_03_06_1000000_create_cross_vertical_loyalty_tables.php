<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: cross-vertical loyalty tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0); // Current V-Coins
            $table->float('multiplier')->default(1.0); // Loyalty Tier Multiplier (Gold, Platinum)
            $table->timestamp('tier_expires_at')->nullable();
            $table->timestamps();
        });

        // 2. Transaction Log for Loyalty Points
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2); // can be negative for redemptions
            $table->string('vertical'); // taxi, food, education, sports
            $table->string('type'); // earn, redeem, bonus, referral
            $table->string('reason'); // "Completed Python Course", "Ride Discount"
            $table->uuid('correlation_id')->index();
            $table->json('metadata')->nullable(); // { "order_id": 123, "course_id": 45 }
            $table->timestamps();
        });

        // 3. Dynamic Loyalty Rules (Earning rates per Vertical)
        Schema::create('loyalty_rules', function (Blueprint $table) {
            $table->id();
            $table->string('vertical'); // taxi, food, clinic, education, etc.
            $table->float('earn_rate'); // e.g., 0.1 means 10% of price back in V-Coins
            $table->float('redeem_limit'); // max % of price covered by V-Coins
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_rules');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('ecosystem_loyalty_wallets');
    }
};
