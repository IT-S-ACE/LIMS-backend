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
        Schema::create('reagents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');

            $table->decimal('stock_qty', 10, 2);

            $table->date('expiry_date');

            $table->string('code')->unique();

            $table->string('category')->nullable();

            $table->decimal('min_stock',10,2)->default(0);

            $table->decimal('unit_price',10,2)->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reagents');
    }
};
