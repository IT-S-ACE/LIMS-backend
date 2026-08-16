<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Traits\ApiResponseTrait;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

    use ApiResponseTrait;

    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }


    public function profile(Request $request)
    {

        return $this->successResponse(

            new ProfileResource(
                $request->user()
            ),

            'Profile data retrieved successfully.'

        );

    }



    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        if ($request->user()) {
            $this->auditLogs->record(
                'User',
                $request->user()->id,
                'LOGOUT',
                null,
                null,
                'User logged out successfully'
            );
        }


        return $this->respondWithSuccess(
            'Logout successfully.'
        );

    }

}
