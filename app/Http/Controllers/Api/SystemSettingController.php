<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSystemSettingRequest;
use App\Http\Resources\SystemSettingResource;
use App\Services\SystemSettingService;
use App\Traits\ApiResponseTrait;



class SystemSettingController extends Controller
{

    use ApiResponseTrait;



    public function __construct(
        protected SystemSettingService $service
    ) {
    }





    public function show()
    {


        return $this->successResponse(

            new SystemSettingResource(
                $this->service->get()
            ),

            "System settings retrieved successfully."

        );

    }





    public function update(
        UpdateSystemSettingRequest $request
    ) {


        $setting =
            $this->service->update(
                $request->validated()
            );



        return $this->successResponse(

            new SystemSettingResource($setting),

            "System settings updated successfully."

        );

    }


}