<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels_rooms', function (Blueprint $table) {
            $table->string('external_code')->nullable()->after('room_number');
            $table->index('external_code');
        });
    }

    public function down(): void
    {
        Schema::table('hotels_rooms', function (Blueprint $table) {
            $table->dropIndex(['external_code']);
            $table->dropColumn('external_code');
        });
    }
};
