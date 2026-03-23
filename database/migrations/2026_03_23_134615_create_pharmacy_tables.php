<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('pharmacies')) {
            Schema::create('pharmacies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->unique()->index();
                $table->string('name')->comment('Название аптеки');
                $table->string('license_number')->unique()->comment('Лицензия на фарм. деятельность');
                $table->string('address');
                $table->point('geo_point')->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Аптечные сети и отдельные точки');
            });
        }

        if (!Schema::hasTable('medications')) {
            Schema::create('medications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->unique()->index();
                $table->string('name')->comment('Торговое название');
                $table->string('inn')->comment('МНН (действующее вещество)');
                $table->string('sku')->unique();
                $table->bigInteger('price')->comment('Цена в копейках');
                $table->boolean('requires_prescription')->default(false)->index();
                $table->integer('stock_quantity')->default(0);
                $table->jsonb('instructions')->nullable()->comment('Инструкция, дозировка');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Лекарственные средства');
            });
        }

        if (!Schema::hasTable('prescriptions')) {
            Schema::create('prescriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('doctor_id')->nullable();
                $table->uuid('uuid')->unique()->index();
                $table->string('prescription_number')->unique();
                $table->date('expires_at')->index();
                $table->enum('status', ['pending', 'verified', 'used', 'expired'])->default('pending')->index();
                $table->string('ocr_data')->nullable()->comment('Данные после OCR проверки');
                $table->string('scan_path')->nullable()->comment('Путь к скану рецепта');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Медицинские рецепты');
            });
        }

        if (!Schema::hasTable('pharmacy_orders')) {
            Schema::create('pharmacy_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->foreignId('pharmacy_id')->constrained('pharmacies')->onDelete('cascade');
                $table->uuid('uuid')->unique()->index();
                $table->bigInteger('total_amount')->comment('Сумма в копейках');
                $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending')->index();
                $table->string('idempotency_key')->unique();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Заказы в аптеках');
            });
        }

        if (!Schema::hasTable('pharmacy_order_items')) {
            Schema::create('pharmacy_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('pharmacy_orders')->onDelete('cascade');
                $table->foreignId('medication_id')->constrained('medications')->onDelete('cascade');
                $table->integer('quantity')->default(1);
                $table->bigInteger('price_at_order')->comment('Цена на момент заказа');
                $table->string('correlation_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_order_items');
        Schema::dropIfExists('pharmacy_orders');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medications');
        Schema::dropIfExists('pharmacies');
    }
};
