<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CoverageRuleSeeder extends Seeder
{
    public function run(): void
    {
        $companies = DB::table('insurance_companies')
            ->get()
            ->keyBy('code');

        DB::table('coverage_rules')->insert([

            [
                'id' => Str::uuid(),
                'insurance_company_id' => $companies['TAW']->id,
                'test_code' => null,
                'coverage_percent' => 80,
                'max_amount' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'insurance_company_id' => $companies['TAW']->id,
                'test_code' => 'COVID',
                'coverage_percent' => 100,
                'max_amount' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'insurance_company_id' => $companies['BUP']->id,
                'test_code' => null,
                'coverage_percent' => 70,
                'max_amount' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'insurance_company_id' => $companies['BUP']->id,
                'test_code' => 'HBA1C',
                'coverage_percent' => 80,
                'max_amount' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'insurance_company_id' => $companies['MED']->id,
                'test_code' => null,
                'coverage_percent' => 60,
                'max_amount' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}