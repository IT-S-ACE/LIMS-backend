<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InsuranceCompanySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('insurance_companies')->insert([

            [
                'id' => Str::uuid(),
                'code' => 'TAW',
                'name' => 'Tawuniya',
                'email' => 'claims@tawuniya.test',
                'phone' => '+966112000000',
                'default_coverage' => 80,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'code' => 'BUP',
                'name' => 'Bupa Arabia',
                'email' => 'claims@bupa.test',
                'phone' => '+966113000000',
                'default_coverage' => 70,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'code' => 'MED',
                'name' => 'MedGulf',
                'email' => 'claims@medgulf.test',
                'phone' => '+966114000000',
                'default_coverage' => 60,
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}