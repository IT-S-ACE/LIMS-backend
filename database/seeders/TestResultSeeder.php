<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestResultSeeder extends Seeder
{
    public function run(): void
    {
        $technician = DB::table('users')
            ->where('username', 'technician')
            ->first();

        $requests = DB::table('test_request_items')
            ->join(
                'test_requests',
                'test_request_items.test_request_id',
                '=',
                'test_requests.id'
            )
            ->join(
                'tests',
                'test_request_items.test_id',
                '=',
                'tests.id'
            )
            ->select(
                'test_request_items.*',
                'test_requests.request_number',
                'tests.name as test_name',
                'tests.unit',
                'tests.reference_range'
            )
            ->get();

        $samples = DB::table('samples')
            ->get()
            ->keyBy('sample_number');

        $rows = [];

        foreach ($requests as $item) {

            if ($item->request_number === 'REQ-2004') {
                continue;
            }

            $sampleNumber = match ($item->test_name) {
                'Complete Blood Count (CBC)' =>
                $item->request_number === 'REQ-2001'
                ? 'SMP-3001'
                : 'SMP-3006',

                'Blood Glucose' => 'SMP-3002',

                'Lipid Profile' => 'SMP-3003',

                'Hemoglobin A1c' => 'SMP-3004',

                'Thyroid Stimulating Hormone (TSH)' => 'SMP-3004',

                'COVID-19 PCR' => 'SMP-3005',

                default => null,
            };

            if (!$sampleNumber || !isset($samples[$sampleNumber])) {
                continue;
            }

            $value = match ($item->test_name) {
                'Complete Blood Count (CBC)' => '5.2',
                'Blood Glucose' => '95',
                'Lipid Profile' => 'Normal',
                'Hemoglobin A1c' => '5.4',
                'Thyroid Stimulating Hormone (TSH)' => '2.1',
                'COVID-19 PCR' => 'Negative',
                default => 'Normal',
            };

            $rows[] = [
                'id' => Str::uuid(),
                'sample_id' => $samples[$sampleNumber]->id,
                'test_request_item_id' => $item->id,
                'value' => $value,
                'result_number' => 'RES-' . (4001 + count($rows)),
                'value_unit' => $item->unit,
                'reference_range' => $item->reference_range,
                'flag' => 'normal',
                'status' => 'completed',
                'approved' => true,
                'approved_by' => $technician?->id,
                'approved_at' => now()->subDays(2),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(2),
            ];
        }

        DB::table('test_results')->insert($rows);
    }
}