<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('layout_id')->nullable()->constrained('restaurant_table_layouts')->onDelete('set null');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('table_number');
            $table->string('table_name')->nullable();
            $table->integer('capacity')->default(2);
            $table->string('shape')->default('rectangular');
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->integer('width')->default(100);
            $table->integer('height')->default(100);
            $table->integer('rotation')->default(0);
            $table->string('zone')->default('main_hall');
            $table->json('features')->nullable();
            $table->boolean('is_accessible')->default(true);
            $table->boolean('is_smoking')->default(false);
            $table->string('qr_code')->unique();
            $table->string('qr_code_url');
            $table->enum('status', ['available', 'occupied', 'reserved', 'cleaning', 'maintenance'])->default('available');
            $table->timestamps();
            
            $table->index(['tenant_id', 'restaurant_id', 'status']);
            $table->index(['restaurant_id', 'table_number']);
            $table->index('qr_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
