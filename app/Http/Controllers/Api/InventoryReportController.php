<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InventoryReportService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;


class InventoryReportController extends Controller
{

    use ApiResponseTrait;


    protected InventoryReportService $service;


    public function __construct(
        InventoryReportService $service
    ) {
        $this->service = $service;
    }



    public function index(Request $request)
    {

        $report =
            $this->service->report(
                $request->all()
            );


        return $this->successResponse(
            $report,
            "Inventory report retrieved successfully."
        );

    }

}