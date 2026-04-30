<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fitness_trainers', function (Blueprint $table) {
            $table->enum('qualification_level', ['junior', 'certified', 'senior', 'master', 'specialist'])
                ->default('junior')
                ->after('rating');
            $table->boolean('is_on_hold')->default(false)->after('qualification_level');
            $table->timestamp('on_hold_since')->nullable()->after('is_on_hold');
            $table->string('on_hold_reason')->nullable()->after('on_hold_since');
        });
    }

    public function down(): void
    {
        Schema::table('fitness_trainers', function (Blueprint $table) {
            $table->dropColumn(['qualification_level', 'is_on_hold', 'on_hold_since', 'on_hold_reason']);
        });
    }
};
