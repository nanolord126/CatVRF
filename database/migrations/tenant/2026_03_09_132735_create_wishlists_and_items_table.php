<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: wishlists and items table handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('is_public')->default(false);
            $table->uuid('correlation_id')->index();
            $table->timestamps();
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('price_at_addition', 15, 2);
            $table->decimal('collected_amount', 15, 2)->default(0);
            $table->boolean('is_fully_paid')->default(false);            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists_and_items');
    }
};
