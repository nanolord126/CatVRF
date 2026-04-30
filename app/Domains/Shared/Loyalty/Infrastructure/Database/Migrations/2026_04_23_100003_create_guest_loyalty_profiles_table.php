<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_loyalty_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('loyalty_program_id')->index();
            $table->unsignedBigInteger('guest_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            
            // Current tier
            $table->unsignedBigInteger('current_tier_id')->nullable()->index();
            $table->timestamp('tier_updated_at')->nullable();
            
            // Points balance
            $table->decimal('available_points', 10, 2)->default(0)->comment('Available points balance');
            $table->decimal('earned_points', 10, 2)->default(0)->comment('Total points earned (lifetime)');
            $table->decimal('redeemed_points', 10, 2)->default(0)->comment('Total points redeemed (lifetime)');
            
            // Spend tracking
            $table->decimal('total_spend', 10, 2)->default(0)->comment('Total spend in program');
            $table->integer('total_visits')->default(0)->comment('Total visits/transactions');
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            
            // Birthday for birthday bonuses
            $table->date('birthday')->nullable();
            $table->boolean('birthday_bonus_received')->default(false);
            $table->year('birthday_bonus_year')->nullable();
            
            // Referral tracking
            $table->unsignedBigInteger('referred_by')->nullable()->index();
            
            $table->json('preferences')->nullable()->comment('Guest preferences for personalization');
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('loyalty_program_id')->references('id')->on('loyalty_programs')->onDelete('cascade');
            $table->foreign('current_tier_id')->references('id')->on('loyalty_tiers')->onDelete('set null');
            $table->foreign('referred_by')->references('id')->on('guest_loyalty_profiles')->onDelete('set null');

            $table->unique(['loyalty_program_id', 'guest_id'], 'unique_guest_in_program');
            $table->index(['loyalty_program_id', 'available_points']);
            $table->index(['guest_id', 'loyalty_program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_loyalty_profiles');
    }
};
