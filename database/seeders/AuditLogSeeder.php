<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = DB::table('users')
            ->where('username', 'admin')
            ->first();

        $technician = DB::table('users')
            ->where('username', 'technician')
            ->first();

        $sample = DB::table('samples')
            ->where('sample_number', 'SMP-3004')
            ->first();

        $testRequest = DB::table('test_requests')
            ->where('request_number', 'REQ-2001')
            ->first();

        $payment = DB::table('payments')->first();

        $reagent = DB::table('reagents')
            ->where('code', 'RG-GLU')
            ->first();

        DB::table('audit_logs')->insert([

            [
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'entity_type' => 'TestRequest',
                'entity_id' => $testRequest->id,
                'action' => 'create',
                'old_values' => null,
                'new_values' => json_encode([
                    'request_number' => 'REQ-2001',
                    'status' => 'pending',
                ]),
                'reason' => 'Created test request with 3 tests',
                'ip_address' => '127.0.0.1',
                'timestamp' => now()->subDays(7),
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(7),
            ],

            [
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'entity_type' => 'Sample',
                'entity_id' => $sample->id,
                'action' => 'create',
                'old_values' => null,
                'new_values' => json_encode([
                    'status' => 'registered',
                ]),
                'reason' => 'Registered sample',
                'ip_address' => '127.0.0.1',
                'timestamp' => now()->subDays(4),
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],

            [
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'entity_type' => 'Payment',
                'entity_id' => $payment->id,
                'action' => 'payment',
                'old_values' => null,
                'new_values' => json_encode([
                    'amount' => 70,
                    'method' => 'card',
                ]),
                'reason' => 'Full payment recorded',
                'ip_address' => '127.0.0.1',
                'timestamp' => now()->subDays(7),
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(7),
            ],

            [
                'id' => Str::uuid(),
                'user_id' => $technician->id,
                'entity_type' => 'TestResult',
                'entity_id' => DB::table('test_results')->first()->id,
                'action' => 'update',
                'old_values' => json_encode([
                    'approved' => false,
                ]),
                'new_values' => json_encode([
                    'approved' => true,
                ]),
                'reason' => 'Approved test result',
                'ip_address' => '127.0.0.1',
                'timestamp' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],

            [
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'entity_type' => 'Reagent',
                'entity_id' => $reagent->id,
                'action' => 'update',
                'old_values' => json_encode([
                    'stock_qty' => 30,
                ]),
                'new_values' => json_encode([
                    'stock_qty' => 29,
                ]),
                'reason' => 'Updated reagent stock',
                'ip_address' => '127.0.0.1',
                'timestamp' => now()->subHours(2),
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],

        ]);
    }
}