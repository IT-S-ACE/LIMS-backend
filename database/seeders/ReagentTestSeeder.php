<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReagentTestSeeder extends Seeder
{
    public function run(): void
    {
        $reagents = DB::table('reagents')
            ->get()
            ->keyBy('code');

        $tests = DB::table('tests')
            ->get()
            ->keyBy('name');

        DB::table('reagent_test')->insert([

            [
                'id' => Str::uuid(),
                'test_id' => $tests['Complete Blood Count (CBC)']->id,
                'reagent_id' => $reagents['RG-CBC']->id,
                'quantity_used' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'test_id' => $tests['Blood Glucose']->id,
                'reagent_id' => $reagents['RG-GLU']->id,
                'quantity_used' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'test_id' => $tests['Hemoglobin A1c']->id,
                'reagent_id' => $reagents['RG-HBA']->id,
                'quantity_used' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'test_id' => $tests['COVID-19 PCR']->id,
                'reagent_id' => $reagents['RG-COV']->id,
                'quantity_used' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'test_id' => $tests['Urinalysis']->id,
                'reagent_id' => $reagents['RG-UA']->id,
                'quantity_used' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}