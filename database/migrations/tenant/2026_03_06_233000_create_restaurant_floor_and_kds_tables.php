<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: restaurant floor and KDS handling in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
                $table->id();
                $table->string('number'); // Номер стола
                $table->integer('capacity')->default(2); // Вместимость
                $table->enum('status', ['available', 'occupied', 'reserved', 'cleaning'])->default('available');
                $table->string('qr_code')->nullable(); // Код для заказа гостем
                $table->text('position')->nullable(); // Координаты на схеме зала [x, y]
                $table->timestamps();
            });
        }

        // 2. Связь заказа со столом и этапами кухни
        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->after('restaurant_id');
            $table->integer('guests_count')->default(1)->after('table_id');
            $table->enum('kitchen_status', ['pending', 'queued', 'cooking', 'ready', 'served'])->default('pending')->after('status');
            $table->json('order_sequences')->nullable(); // Очередность подачи (курсы: 1, 2, 3)
            $table->timestamp('fired_at')->nullable(); // Время отправки "встречки" на кухню
        });

        // 3. Кухонные позиции (KDS Items) - для детального контроля поваров
        Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('restaurant_orders')->onDelete('cascade');
            $table->foreignId('dish_id')->constrained('restaurant_menus');
            $table->integer('quantity')->default(1);
            $table->integer('course_number')->default(1); // Порядковый номер подачи
            $table->enum('status', ['waiting', 'cooking', 'ready'])->default('waiting');
            $table->text('comment')->nullable(); // "Без лука", "Прожарка Medium"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_order_items');
        Schema::table('restaurant_orders', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropColumn(['table_id', 'guests_count', 'kitchen_status', 'order_sequences', 'fired_at']);
        });
        Schema::dropIfExists('restaurant_tables');
    }
};
