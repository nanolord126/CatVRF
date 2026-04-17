<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fashion_virtual_wardrobe')) {
            Schema::create('fashion_virtual_wardrobe', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->json('custom_tags')->nullable();
                $table->date('purchase_date')->nullable();
                $table->decimal('purchase_price', 10, 2)->nullable();
                $table->integer('times_worn')->default(0);
                $table->timestamp('last_worn_at')->nullable();
                $table->boolean('is_favorite')->default(false);
                $table->enum('status', ['active', 'archived'])->default('active');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'tenant_id', 'status']);
                $table->comment('User virtual wardrobe');
            });
        }

        if (!Schema::hasTable('fashion_wear_history')) {
            Schema::create('fashion_wear_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wardrobe_item_id')->constrained('fashion_virtual_wardrobe')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamp('worn_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'worn_at']);
                $table->comment('Wear history tracking');
            });
        }

        if (!Schema::hasTable('fashion_outfits')) {
            Schema::create('fashion_outfits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('occasion')->nullable();
                $table->string('season')->nullable();
                $table->boolean('is_favorite')->default(false);
                $table->integer('times_worn')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'tenant_id']);
                $table->comment('User outfits');
            });
        }

        if (!Schema::hasTable('fashion_outfit_items')) {
            Schema::create('fashion_outfit_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outfit_id')->constrained('fashion_outfits')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('wardrobe_item_id')->constrained('fashion_virtual_wardrobe')->onDelete('cascade');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['outfit_id', 'wardrobe_item_id']);
                $table->comment('Outfit items');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_outfit_items');
        Schema::dropIfExists('fashion_outfits');
        Schema::dropIfExists('fashion_wear_history');
        Schema::dropIfExists('fashion_virtual_wardrobe');
    }
};
