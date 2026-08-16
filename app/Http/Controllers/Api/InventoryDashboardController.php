<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InventoryDashboardService;
use App\Traits\ApiResponseTrait;


class InventoryDashboardController extends Controller
{

    use ApiResponseTrait;


    public function __construct(
        protected InventoryDashboardService $service
    ) {

    }



    public function index()
    {

        return $this->successResponse(

            $this->service->dashboard(),

            "Inventory dashboard retrieved successfully."

        );

    }

}