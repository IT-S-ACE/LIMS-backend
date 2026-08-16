<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SampleSeeder extends Seeder
{
    public function run(): void
    {
        $requests = DB::table('test_requests')
            ->get()
            ->keyBy('request_number');

        DB::table('samples')->insert([

            [
                'id' => Str::uuid(),
                'sample_number' => 'SMP-3001',
                'barcode' => 'BC-3001',
                'sample_type' => 'blood',
                'test_request_id' => $requests['REQ-2001']->id,
                'qr_code' => 'QR-SMP-3001',
                'status' => 'completed',
                'received_at' => now()->subDays(6),
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(5),
            ],

            [
                'id' => Str::uuid(),
                'sample_number' => 'SMP-3002',
                'barcode' => 'BC-3002',
                'sample_type' => 'serum',
                'test_request_id' => $requests['REQ-2001']->id,
                'qr_code' => 'QR-SMP-3002',
                'status' => 'completed',
                'received_at' => now()->subDays(6),
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(5),
            ],

            [
                'id' => Str::uuid(),
                'sample_number' => 'SMP-3003',
                'barcode' => 'BC-3003',
                'sample_type' => 'serum',
                'test_request_id' => $requests['REQ-2001']->id,
                'qr_code' => 'QR-SMP-3003',
                'status' => 'completed',
                'received_at' => now()->subDays(6),
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(5),
            ],

            [
                'id' => Str::uuid(),
                'sample_number' => 'SMP-3004',
                'barcode' => 'BC-3004',
                'sample_type' => 'blood',
                'test_request_id' => $requests['REQ-2002']->id,
                'qr_code' => 'QR-SMP-3004',
                'status' => 'completed',
                'received_at' => now()->subDays(4),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(4),
            ],

            [
                'id' => Str::uuid(),
                'sample_number' => 'SMP-3005',
                'barcode' => 'BC-3005',
                'sample_type' => 'swab',
                'test_request_id' => $requests['REQ-2003']->id,
                'qr_code' => 'QR-SMP-3005',
                'status' => 'completed',
                'received_at' => now()->subDays(3),
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(3),
            ],

            [
                'id' => Str::uuid(),
                'sample_number' => 'SMP-3006',
                'barcode' => 'BC-3006',
                'sample_type' => 'blood',
                'test_request_id' => $requests['REQ-2004']->id,
                'qr_code' => 'QR-SMP-3006',
                'status' => 'in_progress',
                'received_at' => now()->subHours(3),
                'created_at' => now()->subDay(),
                'updated_at' => now(),
            ],

        ]);

        $seededSamples = DB::table('samples')
            ->whereIn('sample_number', [
                'SMP-3001',
                'SMP-3002',
                'SMP-3003',
                'SMP-3004',
                'SMP-3005',
                'SMP-3006',
            ])
            ->get();

        foreach ($seededSamples as $sample) {
            DB::table('samples')
                ->where('id', $sample->id)
                ->update(['collected_at' => $sample->received_at]);

            DB::table('sample_status_histories')->insert([
                'id' => Str::uuid(),
                'sample_id' => $sample->id,
                'from_status' => null,
                'to_status' => $sample->status,
                'reason' => 'Seeded sample lifecycle state.',
                'changed_by' => null,
                'created_at' => $sample->updated_at,
            ]);
        }
    }
}
