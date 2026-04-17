<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_bill_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('table_id')->constrained('restaurant_tables')->onDelete('cascade');
            $table->string('table_number');
            $table->foreignId('order_id')->constrained('restaurant_orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('split_payment')->default(false);
            $table->enum('status', ['pending', 'preparing', 'ready'])->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('ready_at')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            
            $table->index(['tenant_id', 'restaurant_id', 'status']);
            $table->index(['table_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_bill_requests');
    }
};
