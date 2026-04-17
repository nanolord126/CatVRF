<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('menu_item_id')->nullable()->constrained('restaurant_menu_items')->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained('restaurant_orders')->onDelete('set null');
            $table->decimal('rating', 3, 2);
            $table->text('comment')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->index(['tenant_id', 'restaurant_id', 'status']);
            $table->index(['user_id', 'restaurant_id']);
            $table->index(['menu_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_reviews');
    }
};
