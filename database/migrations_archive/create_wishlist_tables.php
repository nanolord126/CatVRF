<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create wishlist_items table
        if (!Schema::connection('central')->hasTable('wishlist_items')) {
            Schema::connection('central')->create('wishlist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('item_type')->comment('Type: product, service, event, etc');
                $table->unsignedBigInteger('item_id')->comment('ID of the item in respective table');
                $table->json('metadata')->nullable()->comment('Custom metadata about item (price, vertical, etc)');
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'item_type', 'item_id']);
                $table->index('item_type');
                $table->index('user_id');
                $table->comment('User wishlist items (saved for later)');
            });
        }

        // Create wishlist_shares table
        if (!Schema::connection('central')->hasTable('wishlist_shares')) {
            Schema::connection('central')->create('wishlist_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('item_type')->nullable()->comment('Filter by type if set');
                $table->string('share_token')->unique()->comment('Public share token');
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->comment('Shared wishlist links');
            });
        }

        // Create wishlist_shared_payments table (for group purchasing)
        if (!Schema::connection('central')->hasTable('wishlist_shared_payments')) {
            Schema::connection('central')->create('wishlist_shared_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wishlist_share_id')->constrained('wishlist_shares')->cascadeOnDelete();
                $table->foreignId('payer_id')->constrained('users')->cascadeOnDelete();
                $table->string('item_type');
                $table->unsignedBigInteger('item_id');
                $table->unsignedBigInteger('amount')->comment('Amount in kopeks');
                $table->string('status')->default('pending')->comment('pending, collected, completed, refunded');
                $table->unsignedBigInteger('target_amount')->comment('Total to collect');
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->index('wishlist_share_id');
                $table->index('payer_id');
                $table->index('status');
                $table->comment('Group purchase collection (pooled payments)');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('wishlist_shared_payments');
        Schema::connection('central')->dropIfExists('wishlist_shares');
        Schema::connection('central')->dropIfExists('wishlist_items');
    }
};
