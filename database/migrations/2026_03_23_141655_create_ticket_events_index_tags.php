<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tickets_events', function (Blueprint $table) {
            $table->index(['tenant_id', 'status']);
            $table->index(['type', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets_events', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropIndex(['type', 'start_at']);
        });
    }
};
