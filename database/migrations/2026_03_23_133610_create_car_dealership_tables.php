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
        if (!Schema::hasTable('car_brands')) {
            Schema::create('car_brands', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->string('name')->comment('Название бренда');
                $table->string('slug')->unique();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Бренды автомобилей');
            });
        }

        if (!Schema::hasTable('car_models')) {
            Schema::create('car_models', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brand_id')->constrained('car_brands')->onDelete('cascade');
                $table->uuid('uuid')->unique()->index();
                $table->string('name')->comment('Название модели');
                $table->string('slug')->unique();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Модели автомобилей');
            });
        }

        if (!Schema::hasTable('car_dealers')) {
            Schema::create('car_dealers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->uuid('uuid')->unique()->index();
                $table->string('name')->comment('Название дилерского центра');
                $table->string('address')->comment('Адрес автосалона');
                $table->point('geo_point')->nullable()->comment('Геопозиция');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Дилерские центры / Автосалоны');
            });
        }

        if (!Schema::hasTable('cars')) {
            Schema::create('cars', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('dealer_id')->constrained('car_dealers')->onDelete('cascade');
                $table->foreignId('model_id')->constrained('car_models')->onDelete('cascade');
                $table->uuid('uuid')->unique()->index();
                $table->bigInteger('price')->comment('Цена в копейках');
                $table->integer('year')->comment('Год выпуска');
                $table->string('vin')->unique()->comment('VIN номер');
                $table->enum('status', ['available', 'reserved', 'sold', 'archived'])->default('available')->index();
                $table->jsonb('specifications')->nullable()->comment('Технические характеристики');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Автомобили в наличии');
            });
        }

        if (!Schema::hasTable('car_orders')) {
            Schema::create('car_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('car_id')->constrained('cars')->onDelete('cascade');
                $table->unsignedBigInteger('client_id')->index();
                $table->uuid('uuid')->unique()->index();
                $table->bigInteger('amount')->comment('Сумма заказа в копейках');
                $table->enum('status', ['pending', 'paid', 'delivered', 'cancelled'])->default('pending')->index();
                $table->string('idempotency_key')->unique()->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Заказы на покупку/бронирование авто');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_orders');
        Schema::dropIfExists('cars');
        Schema::dropIfExists('car_dealers');
        Schema::dropIfExists('car_models');
        Schema::dropIfExists('car_brands');
    }
};
