<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create retention campaigns table.
     * 
     * Tracks automated retention campaigns for high churn risk buyers.
     */
    public function up(): void
    {
        Schema::create('retention_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('buyer_id')->index();
            $table->unsignedBigInteger('tenant_id')->index();
            
            $table->string('campaign_type', 50)->comment('vip_personal, high_value_personal, medium_value_nurture, low_value_reengage');
            $table->string('offer_type', 50)->comment('discount_coupon, free_shipping, bonus_points');
            $table->unsignedInteger('offer_value')->comment('Discount percentage or bonus amount');
            
            $table->decimal('predicted_clv', 14, 2)->comment('Predicted CLV at campaign creation');
            $table->decimal('churn_probability', 5, 4)->comment('Churn probability at campaign creation');
            
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending')->index();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('accepted_at')->nullable()->comment('When buyer accepted the offer');
            $table->timestamp('redeemed_at')->nullable()->comment('When offer was redeemed');
            
            $table->decimal('revenue_impact', 14, 2)->default(0)->comment('Revenue generated from campaign');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['seller_id', 'buyer_id']);
            $table->index(['seller_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_campaigns');
    }
};
