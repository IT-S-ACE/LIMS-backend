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
        Schema::create('reagent_test', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('test_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('reagent_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('quantity_used', 10, 2)->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reagent_test');
    }
};
