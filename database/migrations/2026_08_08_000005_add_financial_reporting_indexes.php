<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('created_at', 'invoices_created_at_index');
            $table->index(['status', 'created_at'], 'invoices_status_created_at_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('date', 'payments_date_index');
            $table->index(['method', 'date'], 'payments_method_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_method_date_index');
            $table->dropIndex('payments_date_index');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_status_created_at_index');
            $table->dropIndex('invoices_created_at_index');
        });
    }
};
