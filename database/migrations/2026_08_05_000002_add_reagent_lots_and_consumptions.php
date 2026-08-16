<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reagent_lots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reagent_id')->constrained()->cascadeOnDelete();
            $table->string('lot_number')->unique();
            $table->decimal('initial_quantity', 12, 3);
            $table->decimal('remaining_quantity', 12, 3);
            $table->date('expiry_date');
            $table->date('received_at');
            $table->decimal('unit_price', 12, 3)->default(0);
            $table->timestamps();

            $table->index(['reagent_id', 'expiry_date', 'remaining_quantity']);
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->timestamp('reagents_consumed_at')->nullable()->after('cancelled_reason');
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->foreignUuid('reagent_lot_id')->nullable()->after('reagent_id')
                ->constrained('reagent_lots')->nullOnDelete();
            $table->foreignUuid('sample_id')->nullable()->after('reagent_lot_id')
                ->constrained()->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->after('sample_id')
                ->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable()->after('type');
            $table->string('reference')->nullable()->after('reason');
        });

        Schema::create('reagent_consumptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sample_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('test_request_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('reagent_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('reagent_lot_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['sample_id', 'test_request_item_id', 'reagent_id', 'reagent_lot_id'],
                'reagent_consumption_allocation_unique'
            );
            $table->index(['sample_id', 'created_at']);
        });

        DB::table('reagent_test')
            ->select('test_id', 'reagent_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('test_id', 'reagent_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function ($duplicate) {
                DB::table('reagent_test')
                    ->where('test_id', $duplicate->test_id)
                    ->where('reagent_id', $duplicate->reagent_id)
                    ->where('id', '!=', $duplicate->keep_id)
                    ->delete();
            });

        Schema::table('reagent_test', function (Blueprint $table) {
            $table->unique(['test_id', 'reagent_id']);
        });

        DB::table('reagents')
            ->orderBy('created_at')
            ->get()
            ->each(function ($reagent) {
                $lotId = (string) Str::uuid();
                $createdAt = $reagent->created_at ?? now();

                DB::table('reagent_lots')->insert([
                    'id' => $lotId,
                    'reagent_id' => $reagent->id,
                    'lot_number' => 'LEGACY-' . $reagent->code,
                    'initial_quantity' => $reagent->stock_qty,
                    'remaining_quantity' => $reagent->stock_qty,
                    'expiry_date' => $reagent->expiry_date,
                    'received_at' => date('Y-m-d', strtotime((string) $createdAt)),
                    'unit_price' => $reagent->unit_price,
                    'created_at' => $createdAt,
                    'updated_at' => now(),
                ]);

                DB::table('stock_transactions')
                    ->where('reagent_id', $reagent->id)
                    ->update(['reagent_lot_id' => $lotId]);
            });
    }

    public function down(): void
    {
        Schema::table('reagent_test', function (Blueprint $table) {
            $table->dropUnique(['test_id', 'reagent_id']);
        });

        Schema::dropIfExists('reagent_consumptions');

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reagent_lot_id');
            $table->dropConstrainedForeignId('sample_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['reason', 'reference']);
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->dropColumn('reagents_consumed_at');
        });

        Schema::dropIfExists('reagent_lots');
    }
};
