<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $reagents = DB::table('reagents')
            ->get()
            ->keyBy('code');

        $lots = DB::table('reagent_lots')
            ->get()
            ->keyBy('reagent_id');

        DB::table('stock_transactions')->insert([

            // CBC: 250 in - 10 out = 240
            [
                'id' => Str::uuid(),
                'reagent_id' => $reagents['RG-CBC']->id,
                'reagent_lot_id' => $lots[$reagents['RG-CBC']->id]->id,
                'reason' => 'Initial seeded stock',
                'reference' => 'SEED-RG-CBC',
                'quantity' => 250,
                'type' => 'in',
                'date' => now()->subDays(20),
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],

            [
                'id' => Str::uuid(),
                'reagent_id' => $reagents['RG-CBC']->id,
                'reagent_lot_id' => $lots[$reagents['RG-CBC']->id]->id,
                'reason' => 'Seeded historical consumption',
                'reference' => null,
                'quantity' => 10,
                'type' => 'out',
                'date' => now()->subDays(5),
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],

            // GLU: 40 in - 11 out = 29
            [
                'id' => Str::uuid(),
                'reagent_id' => $reagents['RG-GLU']->id,
                'reagent_lot_id' => $lots[$reagents['RG-GLU']->id]->id,
                'reason' => 'Initial seeded stock',
                'reference' => 'SEED-RG-GLU',
                'quantity' => 40,
                'type' => 'in',
                'date' => now()->subDays(15),
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],

            [
                'id' => Str::uuid(),
                'reagent_id' => $reagents['RG-GLU']->id,
                'reagent_lot_id' => $lots[$reagents['RG-GLU']->id]->id,
                'reason' => 'Seeded historical consumption',
                'reference' => null,
                'quantity' => 11,
                'type' => 'out',
                'date' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],

            // HBA: 100 in - 10 out = 90
            [
                'id' => Str::uuid(),
                'reagent_id' => $reagents['RG-HBA']->id,
                'reagent_lot_id' => $lots[$reagents['RG-HBA']->id]->id,
                'reason' => 'Initial seeded stock',
                'reference' => 'SEED-RG-HBA',
                'quantity' => 100,
                'type' => 'in',
                'date' => now()->subDays(20),
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],

            [
                'id' => Str::uuid(),
                'reagent_id' => $reagents['RG-HBA']->id,
                'reagent_lot_id' => $lots[$reagents['RG-HBA']->id]->id,
                'reason' => 'Seeded historical consumption',
                'reference' => null,
                'quantity' => 10,
                'type' => 'out',
                'date' => now()->subDays(4),
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],

            // COVID: 20 in - 6 out = 14
            [
                'id' => Str::uuid(),
                'reagent_id' => $reagents['RG-COV']->id,
                'reagent_lot_id' => $lots[$reagents['RG-COV']->id]->id,
                'reason' => 'Initial seeded stock',
                'reference' => 'SEED-RG-COV',
                'quantity' => 20,
                'type' => 'in',
                'date' => now()->subDays(10),
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],

            [
                'id' => Str::uuid(),
                'reagent_id' => $reagents['RG-COV']->id,
                'reagent_lot_id' => $lots[$reagents['RG-COV']->id]->id,
                'reason' => 'Seeded historical consumption',
                'reference' => null,
                'quantity' => 6,
                'type' => 'out',
                'date' => now()->subDays(3),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],

            // UA: 200 in - 20 out = 180
            [
                'id' => Str::uuid(),
                'reagent_id' => $reagents['RG-UA']->id,
                'reagent_lot_id' => $lots[$reagents['RG-UA']->id]->id,
                'reason' => 'Initial seeded stock',
                'reference' => 'SEED-RG-UA',
                'quantity' => 200,
                'type' => 'in',
                'date' => now()->subDays(25),
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subDays(25),
            ],

            [
                'id' => Str::uuid(),
                'reagent_id' => $reagents['RG-UA']->id,
                'reagent_lot_id' => $lots[$reagents['RG-UA']->id]->id,
                'reason' => 'Seeded historical consumption',
                'reference' => null,
                'quantity' => 20,
                'type' => 'out',
                'date' => now()->subDays(2),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],

        ]);
    }
}
