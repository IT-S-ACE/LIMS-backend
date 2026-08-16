<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->string('result_type', 20)->default('text')->after('unit');
            $table->json('result_options')->nullable()->after('result_type');
            $table->decimal('critical_low', 15, 4)->nullable()->after('result_options');
            $table->decimal('critical_high', 15, 4)->nullable()->after('critical_low');
        });

        DB::table('tests')->orderBy('created_at')->get()->each(function ($test) {
            $reference = trim((string) $test->reference_range);
            $type = preg_match('/^(?:[<>]=?\s*)?-?\d+(?:\.\d+)?(?:\s*-\s*-?\d+(?:\.\d+)?)?$/', $reference)
                ? 'numeric'
                : 'text';
            $options = null;

            if (str_contains(strtolower((string) $test->name), 'covid')) {
                $type = 'choice';
                $options = json_encode(['Negative', 'Positive', 'Inconclusive']);
            }

            DB::table('tests')->where('id', $test->id)->update([
                'result_type' => $type,
                'result_options' => $options,
            ]);
        });

        Schema::table('test_results', function (Blueprint $table) {
            $table->string('workflow_status', 30)->default('draft')->after('status');
            $table->text('notes')->nullable()->after('workflow_status');
            $table->foreignUuid('entered_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable()->after('entered_by');
            $table->timestamp('submitted_at')->nullable()->after('entered_at');
            $table->foreignUuid('reviewed_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
            $table->text('correction_reason')->nullable()->after('review_notes');

            $table->index(['sample_id', 'workflow_status']);
            $table->index(['test_request_item_id', 'workflow_status'], 'test_result_item_workflow_idx');
        });

        DB::table('test_results')->orderBy('created_at')->get()->each(function ($result) {
            DB::table('test_results')->where('id', $result->id)->update([
                'workflow_status' => $result->approved ? 'approved' : 'draft',
                'entered_at' => $result->created_at,
            ]);
        });

        Schema::create('test_result_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('test_result_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable();
            $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at');

            $table->index(['test_result_id', 'created_at'], 'result_history_result_date_idx');
        });

        DB::table('test_results')->orderBy('created_at')->get()->each(function ($result) {
            DB::table('test_result_status_histories')->insert([
                'id' => (string) Str::uuid(),
                'test_result_id' => $result->id,
                'from_status' => null,
                'to_status' => $result->workflow_status,
                'reason' => 'Workflow history initialized for an existing result.',
                'changed_by' => null,
                'created_at' => $result->updated_at ?? $result->created_at ?? now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_result_status_histories');

        Schema::table('test_results', function (Blueprint $table) {
            $table->dropIndex(['sample_id', 'workflow_status']);
            $table->dropIndex('test_result_item_workflow_idx');
            $table->dropConstrainedForeignId('entered_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'workflow_status',
                'notes',
                'entered_at',
                'submitted_at',
                'reviewed_at',
                'review_notes',
                'correction_reason',
            ]);
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn([
                'result_type',
                'result_options',
                'critical_low',
                'critical_high',
            ]);
        });
    }
};
