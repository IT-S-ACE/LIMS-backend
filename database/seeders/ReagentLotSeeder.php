<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReagentLotSeeder extends Seeder
{
    public function run(): void
    {
        $initialQuantities = [
            'RG-CBC' => 250,
            'RG-GLU' => 40,
            'RG-HBA' => 100,
            'RG-COV' => 20,
            'RG-UA' => 200,
        ];

        DB::table('reagents')->orderBy('code')->get()->each(function ($reagent) use ($initialQuantities) {
            DB::table('reagent_lots')->insert([
                'id' => Str::uuid(),
                'reagent_id' => $reagent->id,
                'lot_number' => 'SEED-' . $reagent->code,
                'initial_quantity' => $initialQuantities[$reagent->code] ?? $reagent->stock_qty,
                'remaining_quantity' => $reagent->stock_qty,
                'expiry_date' => $reagent->expiry_date,
                'received_at' => now()->subDays(30)->toDateString(),
                'unit_price' => $reagent->unit_price,
                'created_at' => now()->subDays(30),
                'updated_at' => now(),
            ]);
        });
    }
}
