<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tests')->insert([

            [
                'id' => Str::uuid(),
                'name' => 'Complete Blood Count (CBC)',
                'price' => 20.00,
                'reference_range' => 'Normal',
                'unit' => 'cells/uL',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Blood Glucose',
                'price' => 15.00,
                'reference_range' => '70-110',
                'unit' => 'mg/dL',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Lipid Profile',
                'price' => 35.00,
                'reference_range' => 'Normal',
                'unit' => 'mg/dL',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Liver Function Test',
                'price' => 45.00,
                'reference_range' => 'Normal',
                'unit' => 'U/L',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Kidney Function Test',
                'price' => 40.00,
                'reference_range' => 'Normal',
                'unit' => 'mg/dL',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Basic Metabolic Panel (BMP)',
                'price' => 30.00,
                'reference_range' => 'Normal',
                'unit' => 'mmol/L',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Hemoglobin A1c',
                'price' => 25.00,
                'reference_range' => '4.0-5.6',
                'unit' => '%',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Thyroid Stimulating Hormone (TSH)',
                'price' => 25.00,
                'reference_range' => '0.4-4.0',
                'unit' => 'mIU/L',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Urinalysis',
                'price' => 15.00,
                'reference_range' => 'Normal',
                'unit' => 'mg/dL',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'C-Reactive Protein (CRP)',
                'price' => 20.00,
                'reference_range' => '<5',
                'unit' => 'mg/L',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'COVID-19 PCR',
                'price' => 40.00,
                'reference_range' => 'Negative',
                'unit' => 'Result',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Vitamin D',
                'price' => 30.00,
                'reference_range' => '30-100',
                'unit' => 'ng/mL',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'name' => 'Ferritin',
                'price' => 30.00,
                'reference_range' => '15-150',
                'unit' => 'ng/mL',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}