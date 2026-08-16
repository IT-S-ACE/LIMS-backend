<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestRequestItemSeeder extends Seeder
{
    public function run(): void
    {
        $requests = DB::table('test_requests')
            ->get()
            ->keyBy('request_number');

        $tests = DB::table('tests')
            ->get()
            ->keyBy('name');

        DB::table('test_request_items')->insert([

            // REQ-2001
            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2001']->id,
                'test_id' => $tests['Complete Blood Count (CBC)']->id,
                'quantity' => 1,
                'price' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2001']->id,
                'test_id' => $tests['Blood Glucose']->id,
                'quantity' => 1,
                'price' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2001']->id,
                'test_id' => $tests['Lipid Profile']->id,
                'quantity' => 1,
                'price' => 35,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // REQ-2002
            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2002']->id,
                'test_id' => $tests['Hemoglobin A1c']->id,
                'quantity' => 1,
                'price' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2002']->id,
                'test_id' => $tests['Thyroid Stimulating Hormone (TSH)']->id,
                'quantity' => 1,
                'price' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // REQ-2003
            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2003']->id,
                'test_id' => $tests['COVID-19 PCR']->id,
                'quantity' => 1,
                'price' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // REQ-2004
            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2004']->id,
                'test_id' => $tests['Complete Blood Count (CBC)']->id,
                'quantity' => 1,
                'price' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}