<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_sync_queue', function (Blueprint $table) {
            $table->id();
            $table->string('id', 64)->unique();
            $table->string('correlation_id', 64)->nullable();
            $table->string('event_type', 64);
            $table->integer('status_code');
            $table->text('error_message');
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at');
            $table->timestamps();

            $table->index(['next_retry_at', 'retry_count']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_sync_queue');
    }
};
