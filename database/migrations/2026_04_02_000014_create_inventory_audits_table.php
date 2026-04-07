<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_audits', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'discrepancy'])->default('planned');
            $table->unsignedInteger('total_positions')->default(0);
            $table->unsignedInteger('checked_positions')->default(0);
            $table->unsignedInteger('discrepancy_count')->default(0);
            $table->json('discrepancies')->nullable();   // [{product_id, expected, actual, diff}]
            $table->text('comments')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_audits');
    }
};
