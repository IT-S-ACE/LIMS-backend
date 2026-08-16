<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->timestamp('collected_at')->nullable()->after('received_at');
            $table->text('rejected_reason')->nullable()->after('collected_at');
            $table->text('cancelled_reason')->nullable()->after('rejected_reason');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE samples MODIFY status ENUM('registered','received','collected','in_progress','completed','rejected','cancelled') NOT NULL"
            );

            DB::table('samples')
                ->where('status', 'received')
                ->update([
                    'status' => 'collected',
                    'collected_at' => DB::raw('received_at'),
                ]);

            DB::statement(
                "ALTER TABLE samples MODIFY status ENUM('registered','collected','in_progress','completed','rejected','cancelled') NOT NULL"
            );
        }

        Schema::create('sample_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sample_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['sample_id', 'created_at']);
        });

        DB::table('samples')
            ->orderBy('created_at')
            ->get()
            ->each(function ($sample) {
                DB::table('sample_status_histories')->insert([
                    'id' => (string) Str::uuid(),
                    'sample_id' => $sample->id,
                    'from_status' => null,
                    'to_status' => $sample->status,
                    'reason' => 'Lifecycle history initialized for an existing sample.',
                    'changed_by' => null,
                    'created_at' => $sample->updated_at ?? $sample->created_at ?? now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_status_histories');

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE samples MODIFY status ENUM('registered','received','collected','in_progress','completed','rejected','cancelled') NOT NULL"
            );

            DB::table('samples')
                ->where('status', 'collected')
                ->update(['status' => 'received']);

            DB::statement(
                "ALTER TABLE samples MODIFY status ENUM('registered','received','in_progress','completed','rejected','cancelled') NOT NULL"
            );
        }

        Schema::table('samples', function (Blueprint $table) {
            $table->dropColumn([
                'collected_at',
                'rejected_reason',
                'cancelled_reason',
            ]);
        });
    }
};
