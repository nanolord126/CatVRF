<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Tourism Bookings Table
 * 
 * Migration for Tourism vertical bookings with killer features:
 * - AI-personalized tours
 * - Real-time availability hold with biometric verification
 * - Dynamic pricing + flash packages
 * - Virtual 360° tours + AR viewing
 * - Instant video-call with guides
 * - B2C quick booking + B2B corporate tours/MICE
 * - ML-fraud detection for cancellations
 * - Wallet split payment + instant cashback
 * - CRM integration at every status
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tourism_bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('tour_id')->constrained('tours')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('person_count');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_amount', 14, 2);
            $table->decimal('base_price', 14, 2);
            $table->decimal('dynamic_price', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('commission_rate', 5, 4);
            $table->decimal('commission_amount', 14, 2);
            $table->enum('status', ['held', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('held');
            $table->string('biometric_token', 255)->nullable();
            $table->boolean('biometric_verified')->default(false);
            $table->timestamp('hold_expires_at')->nullable();
            $table->boolean('virtual_tour_viewed')->default(false);
            $table->timestamp('virtual_tour_viewed_at')->nullable();
            $table->boolean('video_call_scheduled')->default(false);
            $table->timestamp('video_call_time')->nullable();
            $table->string('video_call_link', 500)->nullable();
            $table->string('video_call_meeting_id', 255)->nullable();
            $table->string('video_call_join_url', 500)->nullable();
            $table->string('payment_method', 50)->default('card');
            $table->boolean('split_payment_enabled')->default(false);
            $table->decimal('cashback_amount', 14, 2)->default(0);
            $table->string('cancellation_reason', 500)->nullable();
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->decimal('fraud_score', 5, 4)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('correlation_id', 100)->nullable()->index();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'tour_id']);
            $table->index(['status', 'hold_expires_at']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tourism_bookings');
    }
};
