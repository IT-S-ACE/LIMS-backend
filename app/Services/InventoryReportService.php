<?php

namespace App\Services;


use App\Models\Reagent;
use App\Models\StockTransaction;
use Carbon\Carbon;


class InventoryReportService
{


    public function report(array $filters = [])
    {


        return [


            'summary' => [

                'total_items'
                =>
                    Reagent::count(),


                'low_stock'
                =>
                    Reagent::whereColumn(
                        'stock_qty',
                        '<',
                        'min_stock'
                    )
                        ->count(),



                'expiring_soon'
                =>
                    Reagent::whereBetween(
                        'expiry_date',
                        [
                            now(),
                            now()->addDays(30)
                        ]
                    )
                        ->count(),



                'inventory_value'
                =>
                    Reagent::sum(
                        \DB::raw(
                            'stock_qty * unit_price'
                        )
                    ),

            ],



            'current_inventory'
            =>
                $this->currentInventory(),



            'stock_movements'
            =>
                $this->stockMovements($filters),


        ];


    }





    private function currentInventory()
    {


        return Reagent::query()

            ->latest()

            ->get()

            ->map(function ($reagent) {


                return [

                    'id'
                    =>
                        $reagent->id,


                    'code'
                    =>
                        $reagent->code,


                    'name'
                    =>
                        $reagent->name,


                    'category'
                    =>
                        $reagent->category,



                    'stock'
                    =>
                        $reagent->stock_qty,


                    'min'
                    =>
                        $reagent->min_stock,


                    'value'
                    =>
                        $reagent->stock_qty
                        *
                        $reagent->unit_price,



                    'status'
                    =>
                        $reagent->stock_qty
                        <=
                        $reagent->min_stock

                        ?
                        'low stock'

                        :
                        'in stock'


                ];


            });


    }





    private function stockMovements(array $filters)
    {


        $query =
            StockTransaction::query()
                ->with('reagent');



        if (
            isset($filters['from'])
        ) {

            $query->whereDate(
                'created_at',
                '>=',
                $filters['from']
            );

        }



        if (
            isset($filters['to'])
        ) {

            $query->whereDate(
                'created_at',
                '<=',
                $filters['to']
            );

        }



        return $query
            ->latest()
            ->get()
            ->map(function ($movement) {


                return [

                    'date'
                    =>
                        $movement->created_at,


                    'reagent'
                    =>
                        $movement
                            ->reagent
                            ->name,


                    'type'
                    =>
                        $movement->type,


                    'quantity'
                    =>
                        $movement->quantity

                ];


            });


    }


}