<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Wallets already created in root migrations
        // This is a stub for tenancy package compatibility
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty
    }
};
