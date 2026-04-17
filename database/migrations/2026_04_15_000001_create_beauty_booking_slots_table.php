<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beauty_booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id', 64)->nullable()->index();
            
            $table->foreignId('salon_id')->nullable()->constrained('beauty_salons')->onDelete('set null');
            $table->foreignId('master_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            
            $table->date('slot_date')->index();
            $table->time('slot_time')->index();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            
            $table->enum('status', ['available', 'held', 'booked', 'cancelled', 'expired'])->default('available')->index();
            
            $table->timestamp('held_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('released_at')->nullable();
            
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'slot_date', 'status']);
            $table->index(['tenant_id', 'master_id', 'slot_date', 'slot_time']);
            $table->index(['tenant_id', 'status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_booking_slots');
    }
};
