<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('inn', 12)->nullable()->index();
            $table->string('kpp', 9)->nullable();
            $table->string('legal_address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('b2b_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('b2b_partners')->onDelete('cascade');
            $table->string('contract_number')->index();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('payment_terms_days')->default(0);
            $table->string('status')->default('active'); // active, expired, terminated
            $table->uuid('correlation_id')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('b2b_partners');
            $table->foreignId('contract_id')->nullable()->constrained('b2b_contracts');
            $table->morphs('origin'); // e.g., Hotel Booking, Beauty Appointment
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending'); // pending, invoiced, paid, cancelled
            $table->uuid('correlation_id')->nullable();
            $table->timestamps();
        });

        Schema::create('b2b_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('b2b_orders');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('status')->default('unpaid'); // unpaid, paid, overdue, cancelled
            $table->string('payment_link')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_invoices');
        Schema::dropIfExists('b2b_orders');
        Schema::dropIfExists('b2b_contracts');
        Schema::dropIfExists('b2b_partners');
    }
};
