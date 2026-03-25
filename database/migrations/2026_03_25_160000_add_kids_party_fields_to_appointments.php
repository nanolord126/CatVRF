<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable("appointments")) {
            return;
        }

        Schema::table("appointments", function (Blueprint $table) {
            $table->boolean("is_kids_party")->default(false)->index()->after("bride_name");
            $table->integer("kids_count")->nullable()->after("is_kids_party");
            $table->string("age_range")->nullable()->after("kids_count");
            $table->boolean("has_allergies")->default(false)->after("age_range");
            $table->text("allergies_info")->nullable()->after("has_allergies");
            $table->string("party_theme")->nullable()->after("allergies_info");
            
            $table->comment("Поля для детских праздников в вертикали Beauty 2026");
        });
    }

    public function down(): void
    {
        Schema::table("appointments", function (Blueprint $table) {
            $table->dropColumn(["is_kids_party", "kids_count", "age_range", "has_allergies", "allergies_info", "party_theme"]);
        });
    }
};
