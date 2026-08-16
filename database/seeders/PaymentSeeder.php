<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $invoices = DB::table('invoices')
            ->get()
            ->keyBy('test_request_id');

        $requests = DB::table('test_requests')
            ->get()
            ->keyBy('request_number');

        DB::table('payments')->insert([

            [
                'id' => Str::uuid(),

                'payment_number' => 'PAY-5001',

                'invoice_id' =>
                    $invoices[
                        $requests['REQ-2001']->id
                    ]->id,

                'amount' => 70,

                'method' => 'card',

                'date' => now()->subDays(4),

                'created_at' => now()->subDays(4),

                'updated_at' => now()->subDays(4),
            ],

            [
                'id' => Str::uuid(),

                'payment_number' => 'PAY-5002',

                'invoice_id' =>
                    $invoices[
                        $requests['REQ-2002']->id
                    ]->id,

                'amount' => 35,

                'method' => 'cash',

                'date' => now()->subDays(2),

                'created_at' => now()->subDays(2),

                'updated_at' => now()->subDays(2),
            ],

            [
                'id' => Str::uuid(),

                'payment_number' => 'PAY-5003',

                'invoice_id' =>
                    $invoices[
                        $requests['REQ-2003']->id
                    ]->id,

                'amount' => 40,

                'method' => 'cash',

                'date' => now()->subDay(),

                'created_at' => now()->subDay(),

                'updated_at' => now()->subDay(),
            ],

        ]);
    }
}