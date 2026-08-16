<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }

    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return $this->error(
                'Unauthenticated.',
                ResponseCode::UN_AUTHENTICATED,
                401
            );
        }

        if (!in_array($user->role, $roles, true)) {
            $this->auditLogs->record(
                'Authorization',
                $user->id,
                'ACCESS',
                null,
                null,
                'Role-based access denied',
                null,
                'DENIED',
                [
                    'route' => $request->route()?->getName(),
                    'path' => '/' . ltrim($request->path(), '/'),
                    'method' => $request->method(),
                    'required_roles' => $roles,
                ]
            );

            return $this->error(
                'You do not have permission to perform this action.',
                ResponseCode::UN_AUTHORIZED,
                403
            );
        }

        return $next($request);
    }

    private function error(string $message, string $code, int $status): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'server_time' => now()->toDateTimeString(),
            'payload' => [],
        ], $status);
    }
}
