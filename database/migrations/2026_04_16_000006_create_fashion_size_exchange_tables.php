<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fashion_size_exchanges')) {
            Schema::create('fashion_size_exchanges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('order_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->string('current_size');
                $table->string('requested_size');
                $table->text('reason')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
                $table->timestamp('requested_at');
                $table->timestamp('processed_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'tenant_id', 'status']);
                $table->comment('Size exchange requests');
            });
        }

        if (!Schema::hasTable('fashion_rentals')) {
            Schema::create('fashion_rentals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->integer('rental_days');
                $table->decimal('rental_price', 10, 2);
                $table->decimal('deposit', 10, 2);
                $table->timestamp('pickup_date');
                $table->timestamp('return_date');
                $table->enum('status', ['active', 'returned', 'overdue'])->default('active');
                $table->text('condition')->nullable();
                $table->json('damage_photos')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'tenant_id', 'status']);
                $table->comment('Product rentals');
            });
        }

        if (!Schema::hasTable('fashion_rental_subscriptions')) {
            Schema::create('fashion_rental_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->integer('items_per_month');
                $table->integer('duration_months');
                $table->enum('plan_type', ['basic', 'standard', 'premium'])->default('standard');
                $table->decimal('monthly_price', 10, 2);
                $table->decimal('total_price', 10, 2);
                $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
                $table->timestamp('started_at');
                $table->timestamp('expires_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'tenant_id', 'status']);
                $table->comment('Rental subscriptions');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_rental_subscriptions');
        Schema::dropIfExists('fashion_rentals');
        Schema::dropIfExists('fashion_size_exchanges');
    }
};
