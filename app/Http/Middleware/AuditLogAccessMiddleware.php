<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogAccessMiddleware
{
    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->role !== 'admin') {
            $isExport = $request->routeIs('audit-logs.export');
            $this->auditLogs->record(
                'AuditLog',
                null,
                $isExport ? 'EXPORT' : 'VIEW',
                null,
                null,
                $isExport
                    ? 'Unauthorized audit export attempt denied'
                    : 'Unauthorized audit access attempt denied',
                null,
                'DENIED',
                ['route' => $request->route()?->getName()]
            );

            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
