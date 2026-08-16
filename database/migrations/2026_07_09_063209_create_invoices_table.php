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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('test_request_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('total', 10, 2);

            $table->decimal('paid', 10, 2)->default(0);

            $table->decimal('remaining', 10, 2);

            $table->enum('status', [
                'pending',
                'partial',
                'paid'
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
