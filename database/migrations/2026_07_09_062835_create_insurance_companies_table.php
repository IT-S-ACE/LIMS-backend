<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insurance_companies', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->string('code')
                ->unique();

            $table->string('name');

            $table->string('email')
                ->nullable();

            $table->string('phone')
                ->nullable();


            $table->decimal(
                'default_coverage',
                5,
                2
            )
                ->default(0);


            $table->enum('status', [
                'approved',
                'inactive'
            ])
                ->default('approved');


            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_companies');
    }
};
