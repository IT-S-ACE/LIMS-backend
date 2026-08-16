<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $requests = DB::table('test_requests')
            ->get()
            ->keyBy('request_number');

        DB::table('invoices')->insert([

            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2001']->id,
                'total' => 70,
                'paid' => 70,
                'remaining' => 0,
                'status' => 'paid',
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(5),
            ],

            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2002']->id,
                'total' => 50,
                'paid' => 35,
                'remaining' => 15,
                'status' => 'partial',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(3),
            ],

            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2003']->id,
                'total' => 40,
                'paid' => 40,
                'remaining' => 0,
                'status' => 'paid',
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(3),
            ],

            [
                'id' => Str::uuid(),
                'test_request_id' => $requests['REQ-2004']->id,
                'total' => 20,
                'paid' => 0,
                'remaining' => 20,
                'status' => 'pending',
                'created_at' => now()->subDay(),
                'updated_at' => now(),
            ],

        ]);
    }
}