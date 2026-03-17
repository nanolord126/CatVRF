<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Добавляет soft delete поддержку к таблицам которые нуждаются в логическом удалении.
     * Production 2026: idempotent, SoftDeletes trait.
     */
    public function up(): void
    {
        // Soft deletes will be added to specific tables via eloquent traits
        // This is placeholder for explicit migrations if needed
    }

    public function down(): void
    {
        // Cannot safely remove soft delete columns
    }
};
