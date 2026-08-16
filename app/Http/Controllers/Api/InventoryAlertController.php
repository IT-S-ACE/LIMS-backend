<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Services\InventoryService;
use App\Http\Resources\ReagentResource;


class InventoryAlertController extends Controller
{

    use ApiResponseTrait;


    public function __construct(
        protected InventoryService $service
    ) {

    }



    public function expired()
    {

        return $this->successResponse(

            ReagentResource::collection(
                $this->service->expiredReagents()
            ),

            "Expired reagents retrieved"

        );

    }



    public function lowStock()
    {

        return $this->successResponse(

            ReagentResource::collection(
                $this->service->lowStockReagents()
            ),

            "Low stock reagents retrieved"

        );

    }

    public function expiringSoon()
    {
        return $this->successResponse(
            ReagentResource::collection($this->service->expiringSoonReagents()),
            'Reagent lots expiring within 30 days retrieved'
        );
    }


}
