<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vehicle_id')->nullable()->constrained('auto_vehicles')->onDelete('set null');
            $table->string('vin')->unique();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->index();
            $table->string('country_origin');
            $table->decimal('declared_value', 15, 2);
            $table->string('currency', 3);
            $table->string('engine_type');
            $table->decimal('engine_volume', 5, 2)->nullable();
            $table->integer('manufacture_year');
            $table->json('documents');
            $table->string('status')->default('pending_payment');
            $table->boolean('is_b2b')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
            $table->index('vin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_imports');
    }
};
