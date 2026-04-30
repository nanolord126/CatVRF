<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Vertical type (restaurant, hotel, etc.)
            $table->string('vertical_type')->default('restaurant')->index()->comment('restaurant, hotel, beauty, etc.');
            
            // Program settings
            $table->boolean('is_active')->default(true)->index();
            
            // Point calculation settings
            $table->decimal('base_points_per_currency', 10, 4)->default(1.0000)
                ->comment('Points earned per currency unit (e.g., 1 point per 1 ruble)');
            $table->decimal('points_to_currency_rate', 10, 4)->default(0.0100)
                ->comment('Currency value per point (e.g., 1 point = 0.01 ruble)');
            
            // Bonus settings
            $table->decimal('signup_bonus_points', 10, 2)->default(0)->comment('Bonus points for registration');
            $table->decimal('birthday_bonus_points', 10, 2)->default(0)->comment('Bonus points on birthday');
            $table->decimal('referral_bonus_points', 10, 2)->default(0)->comment('Bonus points for referrals');
            
            // Tier settings
            $table->boolean('tier_system_enabled')->default(true);
            $table->json('tier_config')->nullable()->comment('Tier configuration in JSON');
            
            // Point expiration
            $table->boolean('points_expire')->default(false);
            $table->integer('points_expiration_days')->nullable()->comment('Days until points expire');
            
            // Schedule
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            $table->index(['tenant_id', 'vertical_type']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_programs');
    }
};
