<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    protected NotificationService $service;

    public function __construct(
        NotificationService $service
    ) {
        $this->service = $service;
    }

    public function send(
        Notification $notification
    ) {

        $notification =
            $this->service
                ->send($notification);

        return $this->successResponse(
            new NotificationResource($notification),
            "Notification sent successfully."
        );
    }

    public function index(Request $request)
    {

        $notifications =
            $this->service->getForUser($request->user());


        return $this->successResponse(

            NotificationResource::collection(
                $notifications
            ),

            "Notifications retrieved successfully."

        );

    }

    public function read(Request $request, Notification $notification)
    {
        $this->service->markAsRead($notification, $request->user());


        return $this->successResponse(
            null,
            "Notification marked as read"
        );

    }
}
