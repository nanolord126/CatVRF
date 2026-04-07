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
            $table->boolean("is_corporate_event")->default(false)->index()->after("party_theme");
            $table->string("corporate_client_id")->nullable()->index()->after("is_corporate_event");
            $table->string("company_name")->nullable()->after("corporate_client_id");
            $table->string("event_type")->nullable()->after("company_name");
            $table->integer("participants_count")->nullable()->after("event_type");
            $table->string("contract_number")->nullable()->after("participants_count");
            
            $table->comment("Поля для корпоративных мероприятий в вертикали Beauty 2026");
        });
    }

    public function down(): void
    {
        Schema::table("appointments", function (Blueprint $table) {
            $table->dropColumn(["is_corporate_event", "corporate_client_id", "company_name", "event_type", "participants_count", "contract_number"]);
        });
    }
};


