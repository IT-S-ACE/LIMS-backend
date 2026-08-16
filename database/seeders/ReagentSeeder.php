<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReagentSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('reagents')->insert([

            [
                'id' => Str::uuid(),
                'code' => 'RG-CBC',
                'name' => 'CBC Reagent Kit',
                'category' => 'Hematology',
                'stock_qty' => 240,
                'min_stock' => 50,
                'expiry_date' => '2026-11-19',
                'unit_price' => 1.20,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'code' => 'RG-GLU',
                'name' => 'Glucose Reagent',
                'category' => 'Chemistry',
                'stock_qty' => 29,
                'min_stock' => 40,
                'expiry_date' => '2026-08-11',
                'unit_price' => 0.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'code' => 'RG-HBA',
                'name' => 'HbA1c Cartridge',
                'category' => 'Endocrinology',
                'stock_qty' => 90,
                'min_stock' => 30,
                'expiry_date' => '2027-02-07',
                'unit_price' => 2.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'code' => 'RG-COV',
                'name' => 'COVID-19 PCR Kit',
                'category' => 'Microbiology',
                'stock_qty' => 14,
                'min_stock' => 25,
                'expiry_date' => '2026-07-30',
                'unit_price' => 6.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'code' => 'RG-UA',
                'name' => 'Urinalysis Strips',
                'category' => 'Urinalysis',
                'stock_qty' => 180,
                'min_stock' => 60,
                'expiry_date' => '2027-05-18',
                'unit_price' => 0.30,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}