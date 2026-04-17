<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fashion_wishlists')) {
            Schema::create('fashion_wishlists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->text('note')->nullable();
                $table->decimal('price_added', 10, 2);
                $table->boolean('is_available')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id', 'product_id']);
                $table->index(['user_id', 'tenant_id']);
                $table->comment('User wishlists');
            });
        }

        if (!Schema::hasTable('fashion_shopping_lists')) {
            Schema::create('fashion_shopping_lists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('occasion')->nullable();
                $table->decimal('budget', 10, 2)->nullable();
                $table->enum('status', ['active', 'completed'])->default('active');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'tenant_id', 'status']);
                $table->comment('User shopping lists');
            });
        }

        if (!Schema::hasTable('fashion_shopping_list_items')) {
            Schema::create('fashion_shopping_list_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('list_id')->constrained('fashion_shopping_lists')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->boolean('is_purchased')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['list_id', 'product_id']);
                $table->comment('Shopping list items');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_shopping_list_items');
        Schema::dropIfExists('fashion_shopping_lists');
        Schema::dropIfExists('fashion_wishlists');
    }
};
