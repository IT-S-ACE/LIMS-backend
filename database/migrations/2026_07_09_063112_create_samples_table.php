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
        Schema::create('samples', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('sample_number')->unique();

            $table->string('barcode')->unique();

            $table->string('sample_type');

            $table->foreignUuid('test_request_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('qr_code')->unique();

            $table->enum('status', [
                'registered',
                'collected',
                'in_progress',
                'completed',
                'rejected',
                'cancelled'
            ]);

            $table->timestamp('received_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
