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
        Schema::create('coverage_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('insurance_company_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('test_code')
                ->nullable();

            $table->decimal('coverage_percent', 5, 2);


            $table->decimal('max_amount', 10, 2)
                ->nullable(); 
                
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coverage_rules');
    }
};
