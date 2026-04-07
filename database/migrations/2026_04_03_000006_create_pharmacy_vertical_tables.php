<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pharmacy vertical tables — CatVRF 2026
 * Models: Pharmacy, Medication, Medicine, PharmacyOrder, PharmacyOrderItem,
 *         PharmacyB2BOrder, PharmacyB2BStorefront, PharmacySubscription,
 *         PharmacyConsumable, PharmacyReview, Prescription
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('license_number')->nullable()->index();
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->json('working_hours')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_24h')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('pharmacy_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('pharmacy_id')->constrained('pharmacies')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('international_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('barcode')->nullable()->index();
            $table->decimal('price', 10, 2);
            $table->decimal('price_b2b', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->boolean('requires_prescription')->default(false);
            $table->boolean('is_controlled')->default(false);
            $table->string('dosage_form')->nullable(); // tablet, capsule, injection, syrup
            $table->string('dosage')->nullable();
            $table->json('contraindications')->nullable();
            $table->json('interactions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'requires_prescription', 'is_active']);
        });

        Schema::create('pharmacy_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pharmacy_id')->constrained('pharmacies')->onDelete('cascade');
            $table->foreignId('prescription_id')->nullable()->constrained('pharmacy_prescriptions')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('idempotency_key')->unique()->nullable();
            $table->enum('status', ['pending', 'prescription_check', 'confirmed', 'assembling', 'ready', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('total_price', 14, 2);
            $table->boolean('requires_prescription')->default(false);
            $table->boolean('is_delivery')->default(false);
            $table->json('delivery_address')->nullable();
            $table->boolean('is_b2b')->default(false);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'user_id']);
        });

        Schema::create('pharmacy_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('pharmacy_orders')->onDelete('cascade');
            $table->foreignId('medication_id')->constrained('pharmacy_medications')->onDelete('restrict');
            $table->string('correlation_id')->nullable()->index();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });

        Schema::create('pharmacy_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('doctor_name')->nullable();
            $table->string('doctor_license')->nullable();
            $table->string('document_number')->nullable()->index();
            $table->date('issued_at');
            $table->date('expires_at');
            $table->json('medications')->nullable();
            $table->enum('status', ['pending_verification', 'verified', 'rejected', 'used'])->default('pending_verification');
            $table->string('image_path')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'user_id', 'status']);
        });

        Schema::create('pharmacy_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pharmacy_id')->constrained('pharmacies')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->json('medications')->nullable(); // recurring meds list
            $table->enum('period', ['weekly', 'monthly', 'bimonthly', 'quarterly']);
            $table->decimal('price', 10, 2);
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->timestamp('next_delivery_at')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('pharmacy_b2b_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('pharmacy_id')->constrained('pharmacies')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('idempotency_key')->unique()->nullable();
            $table->string('inn')->index();
            $table->decimal('total_price', 14, 2);
            $table->integer('payment_term_days')->default(14);
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'inn']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_b2b_orders');
        Schema::dropIfExists('pharmacy_subscriptions');
        Schema::dropIfExists('pharmacy_prescriptions');
        Schema::dropIfExists('pharmacy_order_items');
        Schema::dropIfExists('pharmacy_orders');
        Schema::dropIfExists('pharmacy_medications');
        Schema::dropIfExists('pharmacies');
    }
};
