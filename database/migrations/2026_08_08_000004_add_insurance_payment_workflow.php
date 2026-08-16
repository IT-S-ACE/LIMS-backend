<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->unique()->after('id');
            $table->decimal('insurance_amount', 10, 2)->default(0)->after('total');
            $table->decimal('patient_due', 10, 2)->default(0)->after('insurance_amount');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('price');
            $table->decimal('line_total', 10, 2)->default(0)->after('quantity');
            $table->decimal('coverage_percent', 5, 2)->default(0)->after('line_total');
            $table->decimal('insurance_amount', 10, 2)->default(0)->after('coverage_percent');
            $table->decimal('patient_amount', 10, 2)->default(0)->after('insurance_amount');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('recorded_by')
                ->nullable()
                ->after('invoice_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('notes', 500)->nullable()->after('method');
        });

        Schema::table('coverage_rules', function (Blueprint $table) {
            $table->foreignUuid('test_id')
                ->nullable()
                ->after('insurance_company_id')
                ->constrained('tests')
                ->cascadeOnDelete();
            $table->unique(
                ['insurance_company_id', 'test_id'],
                'coverage_rules_company_test_unique'
            );
        });

        $invoices = DB::table('invoices')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($invoices as $index => $invoice) {
            $patientDue = max(0, (float) $invoice->total);

            DB::table('invoices')
                ->where('id', $invoice->id)
                ->update([
                    'invoice_number' => 'INV-' . str_pad(
                        (string) ($index + 1001),
                        6,
                        '0',
                        STR_PAD_LEFT
                    ),
                    'insurance_amount' => 0,
                    'patient_due' => $patientDue,
                    'remaining' => max(0, $patientDue - (float) $invoice->paid),
                    'status' => (float) $invoice->paid >= $patientDue
                        ? 'paid'
                        : ((float) $invoice->paid > 0 ? 'partial' : 'pending'),
                ]);

            $requestItems = DB::table('test_request_items')
                ->where('test_request_id', $invoice->test_request_id)
                ->get();

            foreach ($requestItems as $requestItem) {
                $lineTotal = round(
                    (float) $requestItem->price * (int) $requestItem->quantity,
                    2
                );

                $itemKey = [
                    'invoice_id' => $invoice->id,
                    'test_request_item_id' => $requestItem->id,
                ];
                $itemValues = [
                    'price' => $requestItem->price,
                    'quantity' => $requestItem->quantity,
                    'line_total' => $lineTotal,
                    'coverage_percent' => 0,
                    'insurance_amount' => 0,
                    'patient_amount' => $lineTotal,
                    'updated_at' => now(),
                ];

                if (DB::table('invoice_items')->where($itemKey)->exists()) {
                    DB::table('invoice_items')->where($itemKey)->update($itemValues);
                } else {
                    DB::table('invoice_items')->insert(array_merge(
                        $itemKey,
                        $itemValues,
                        [
                            'id' => (string) Illuminate\Support\Str::uuid(),
                            'created_at' => now(),
                        ]
                    ));
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('coverage_rules', function (Blueprint $table) {
            $table->dropUnique('coverage_rules_company_test_unique');
            $table->dropConstrainedForeignId('test_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn('notes');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'quantity',
                'line_total',
                'coverage_percent',
                'insurance_amount',
                'patient_amount',
            ]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn([
                'invoice_number',
                'insurance_amount',
                'patient_due',
            ]);
        });
    }
};
