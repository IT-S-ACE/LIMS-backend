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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('patient_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->text('message');

            $table->enum('status', [
                'pending',
                'sent',
                'failed'
                
            ]);

            $table->string('type');

            $table->string('channel')->default('in-app');

            $table->timestamp('read_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
