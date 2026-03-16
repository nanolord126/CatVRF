<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: wallet migrations handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
    {
        return (new Wallet())->getTable();
    }

    private function transactionTable(): string
    {
        return (new Transaction())->getTable();
    }
};
