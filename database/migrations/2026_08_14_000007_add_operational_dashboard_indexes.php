<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('test_requests', function (Blueprint $table) {
            $table->index('created_at', 'test_requests_created_at_idx');
            $table->index(['status', 'created_at'], 'test_requests_status_created_idx');
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->index(['status', 'updated_at'], 'samples_status_updated_idx');
            $table->index(['status', 'received_at'], 'samples_status_received_idx');
        });

        Schema::table('test_results', function (Blueprint $table) {
            $table->index(['approved', 'approved_at'], 'results_approved_date_idx');
            $table->index(
                ['flag', 'approved', 'created_at'],
                'results_flag_approved_created_idx'
            );
            $table->index('workflow_status', 'results_workflow_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('test_results', function (Blueprint $table) {
            $table->dropIndex('results_workflow_status_idx');
            $table->dropIndex('results_flag_approved_created_idx');
            $table->dropIndex('results_approved_date_idx');
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->dropIndex('samples_status_received_idx');
            $table->dropIndex('samples_status_updated_idx');
        });

        Schema::table('test_requests', function (Blueprint $table) {
            $table->dropIndex('test_requests_status_created_idx');
            $table->dropIndex('test_requests_created_at_idx');
        });
    }
};
