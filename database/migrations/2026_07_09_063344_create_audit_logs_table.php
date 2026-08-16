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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('entity_type');

            $table->uuid('entity_id');

            $table->string('action');
            
            $table->json('old_values')->nullable();

            $table->json('new_values')->nullable();

            $table->text('reason')->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('timestamp');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
