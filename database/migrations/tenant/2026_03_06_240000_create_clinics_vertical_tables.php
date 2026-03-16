<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: clinics vertical tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->string('name');
            $table->enum('type', ['human', 'vet'])->default('human')->index();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('geo_lat', 10, 8)->nullable();
            $table->decimal('geo_lng', 11, 8)->nullable();
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // 2. Doctor Profiles (Linked to users)
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('clinic_id')->constrained('clinics')->onDelete('cascade');
            $table->string('specialization');
            $table->text('biography')->nullable();
            $table->string('license_number')->nullable();
            $table->decimal('consultation_fee', 15, 2)->default(0);
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'clinic_id']);
        });

        // 3. Animals (Pets)
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('species')->index(); // cat, dog, bird, etc.
            $table->string('breed')->nullable()->index();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->decimal('weight', 8, 2)->nullable(); // kg
            $table->string('chip_number')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // 4. Medical Cards (Universal for HUMAN and ANIMAL patients)
        Schema::create('medical_cards', function (Blueprint $table) {
            $table->id();
            $table->enum('patient_type', ['HUMAN', 'ANIMAL'])->index();
            $table->unsignedBigInteger('patient_id')->index(); // ID of user or animal
            $table->foreignId('doctor_id')->constrained('users'); // Doctor's user ID
            $table->foreignId('appointment_id')->nullable(); // Reference to medical_appointments
            $table->text('symptoms')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('prescription')->nullable();
            $table->string('status')->default('closed');
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // 5. Medical Consumables (Linking surgery/appointment to inventory)
        Schema::create('medical_consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_card_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('inventory_item_id'); // From inventory module
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // 6. Vaccinations tracking (Specific for VetClinics)
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('users');
            $table->string('vaccine_name');
            $table->dateTime('administered_at');
            $table->dateTime('due_at')->nullable()->index();
            $table->string('lot_number')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccinations');
        Schema::dropIfExists('medical_consumables');
        Schema::dropIfExists('medical_cards');
        Schema::dropIfExists('animals');
        Schema::dropIfExists('doctor_profiles');
        Schema::dropIfExists('clinics');
    }
};
