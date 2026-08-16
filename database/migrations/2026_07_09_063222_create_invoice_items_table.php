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
        Schema::create('invoice_items', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('test_request_item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('price', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
