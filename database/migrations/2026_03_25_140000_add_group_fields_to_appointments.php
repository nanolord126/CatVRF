<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('is_group')->default(false)->index()->after('client_id');
            $table->integer('group_size')->nullable()->after('is_group');
            $table->foreignId('group_leader_id')->nullable()->constrained('users')->nullOnDelete()->after('group_size');
            $table->string('group_name')->nullable()->after('group_leader_id');
            $table->comment('Добавление полей для группового бронирования в вертикали Beauty');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['is_group', 'group_size', 'group_leader_id', 'group_name']);
        });
    }
};


