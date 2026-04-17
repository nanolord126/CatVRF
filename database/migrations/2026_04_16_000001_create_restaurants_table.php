<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->string('cuisine_type')->nullable();
            $table->string('price_range')->nullable();
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('review_count')->default(0);
            $table->boolean('is_delivery_available')->default(true);
            $table->boolean('is_pickup_available')->default(true);
            $table->boolean('is_dine_in_available')->default(true);
            $table->integer('average_preparation_time_minutes')->default(30);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'city']);
            $table->index(['lat', 'lon']);
            $table->index('cuisine_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
