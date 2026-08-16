<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestRequestSeeder extends Seeder
{
    public function run(): void
    {
        $patients = DB::table('patients')
            ->get()
            ->keyBy('patient_number');

        $insurance = DB::table('insurance_companies')
            ->get()
            ->keyBy('code');

        DB::table('test_requests')->insert([

            [
                'id' => Str::uuid(),
                'request_number' => 'REQ-2001',
                'patient_id' => $patients['PAT-1001']->id,
                'insurance_company_id' => $insurance['TAW']->id,
                'status' => 'completed',
                'total_price' => 70,
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(5),
            ],

            [
                'id' => Str::uuid(),
                'request_number' => 'REQ-2002',
                'patient_id' => $patients['PAT-1002']->id,
                'insurance_company_id' => $insurance['BUP']->id,
                'status' => 'completed',
                'total_price' => 50,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(4),
            ],

            [
                'id' => Str::uuid(),
                'request_number' => 'REQ-2003',
                'patient_id' => $patients['PAT-1003']->id,
                'insurance_company_id' => null,
                'status' => 'completed',
                'total_price' => 40,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(3),
            ],

            [
                'id' => Str::uuid(),
                'request_number' => 'REQ-2004',
                'patient_id' => $patients['PAT-1004']->id,
                'insurance_company_id' => $insurance['BUP']->id,
                'status' => 'processing',
                'total_price' => 20,
                'created_at' => now()->subDay(),
                'updated_at' => now(),
            ],

        ]);
    }
}