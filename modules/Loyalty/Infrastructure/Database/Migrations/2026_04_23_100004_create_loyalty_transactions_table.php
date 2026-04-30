<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('loyalty_program_id')->index();
            $table->unsignedBigInteger('guest_loyalty_profile_id')->index();
            $table->uuid('uuid')->unique();
            
            // Transaction type
            $table->enum('type', ['earned', 'redeemed', 'bonus', 'expired', 'adjusted', 'refunded'])
                ->index();
            
            // Source of transaction
            $table->string('source_type')->nullable()->comment('Order, Booking, Manual, etc.');
            $table->unsignedBigInteger('source_id')->nullable()->index();
            
            // Points
            $table->decimal('points_change', 10, 2)->comment('Points change (+/-)');
            $table->decimal('balance_before', 10, 2)->default(0)->comment('Balance before transaction');
            $table->decimal('balance_after', 10, 2)->default(0)->comment('Balance after transaction');
            
            // Context
            $table->decimal('order_amount', 10, 2)->nullable()->comment('Order amount for earned points');
            $table->decimal('point_multiplier', 5, 4)->default(1.0000)->comment('Multiplier applied');
            
            // Description
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            
            // Expiration
            $table->timestamp('expires_at')->nullable()->comment('When these points expire');
            $table->boolean('is_expired')->default(false);
            $table->timestamp('expired_at')->nullable();
            
            // Rule that triggered this transaction
            $table->unsignedBigInteger('loyalty_rule_id')->nullable()->index();
            
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('loyalty_program_id')->references('id')->on('loyalty_programs')->onDelete('cascade');
            $table->foreign('guest_loyalty_profile_id')->references('id')->on('guest_loyalty_profiles')->onDelete('cascade');

            $table->index(['guest_loyalty_profile_id', 'created_at']);
            $table->index(['loyalty_program_id', 'type']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
