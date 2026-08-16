<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('patient_number')
                ->unique();

            $table->string('name');

            $table->enum('gender', [
                'male',
                'female'
            ]);

            $table->string('phone');

            $table->string('email')->nullable();

            $table->date('dob');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};