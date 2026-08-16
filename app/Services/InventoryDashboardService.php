<?php

namespace App\Services;


use App\Models\Reagent;
use App\Http\Resources\ReagentResource;


class InventoryDashboardService
{


    public function dashboard()
    {

        return [

            "summary" => [

                "total_items" =>
                    Reagent::count(),


                "low_stock" =>
                    Reagent::whereColumn(
                        'stock_qty',
                        '<=',
                        'min_stock'
                    )->count(),


                "expired" =>
                    Reagent::whereDate(
                        'expiry_date',
                        '<=',
                        now()
                    )->count(),


                "expiring_soon" =>
                    Reagent::whereBetween(
                        'expiry_date',
                        [
                            now(),
                            now()->addDays(30)
                        ]
                    )->count(),

            ],


            "inventory" =>
                ReagentResource::collection(
                    Reagent::with('tests')
                        ->latest()
                        ->get()
                ),



            "low_stock" =>
                ReagentResource::collection(

                    Reagent::whereColumn(
                        'stock_qty',
                        '<=',
                        'min_stock'
                    )->get()

                ),



            "expired" =>
                ReagentResource::collection(

                    Reagent::whereDate(
                        'expiry_date',
                        '<=',
                        now()
                    )->get()

                ),



            "expiring_soon" =>
                ReagentResource::collection(

                    Reagent::whereBetween(
                        'expiry_date',
                        [
                            now(),
                            now()->addDays(30)
                        ]
                    )->get()

                )

        ];

    }

}