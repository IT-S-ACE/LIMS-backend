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
        Schema::create('test_results', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('sample_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('test_request_item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->text('value');

            $table->string('result_number')->unique();

            $table->string('value_unit')->nullable();

            $table->string('reference_range')->nullable();

            $table->enum('flag', [
                'normal',
                'low',
                'high',
                'critical'
            ])->default('normal');

            $table->enum('status', [
                'draft',
                'completed'
            ]);

            $table->boolean('approved')->default(false);

            $table->foreignUuid('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};
