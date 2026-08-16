<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceItemSeeder extends Seeder
{
    public function run(): void
    {
        $invoices = DB::table('invoices')
            ->get()
            ->keyBy('test_request_id');

        $items = DB::table('test_request_items')->get();

        $rows = [];

        foreach ($items as $item) {

            if (!isset($invoices[$item->test_request_id])) {
                continue;
            }

            $rows[] = [
                'id' => Str::uuid(),
                'invoice_id' => $invoices[$item->test_request_id]->id,
                'test_request_item_id' => $item->id,
                'price' => $item->price,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('invoice_items')->insert($rows);
    }
}