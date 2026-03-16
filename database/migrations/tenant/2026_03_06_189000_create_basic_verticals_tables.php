<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: basic verticals tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
                $table->id();
                $table->string('name');
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->text('location')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('restaurant_menus')) {
            Schema::create('restaurant_menus', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained();
                $table->string('name');
                $table->decimal('price', 12, 2);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('restaurant_orders')) {
            Schema::create('restaurant_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('restaurant_id')->constrained();
                $table->foreignId('customer_id')->constrained('users');
                $table->decimal('total_amount', 12, 2);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('flowers_shops')) {
            Schema::create('flowers_shops', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('address')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('restaurants');
        Schema::dropIfExists('flowers_shops');
    }
};
