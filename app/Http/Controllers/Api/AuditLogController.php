<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\AuditLogIndexRequest;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected AuditLogService $service)
    {
    }

    public function index(AuditLogIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $logs = $this->service->paginate($filters);

        return $this->successResponse([
            'items' => $logs->getCollection()
                ->map(fn($log) => (new AuditLogResource($log))->resolve($request))
                ->values()
                ->all(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'summary' => $this->service->summary($filters),
            'filter_options' => $this->service->filterOptions(),
        ], 'Audit logs retrieved successfully.');
    }

    public function entity(string $type, string $id): JsonResponse
    {
        return $this->successResponse(
            AuditLogResource::collection($this->service->getByEntity($type, $id)),
            'Entity audit logs retrieved successfully.'
        );
    }

    public function export(AuditLogIndexRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        unset($filters['page'], $filters['per_page']);
        $logs = $this->service->exportRows($filters);

        $this->service->record(
            'AuditLog',
            null,
            'EXPORT',
            null,
            null,
            'Audit trail exported',
            null,
            'SUCCESS',
            ['filters' => $filters, 'exported_rows' => $logs->count()]
        );

        return response()->streamDownload(function () use ($logs): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Event ID',
                'Occurred At',
                'Actor',
                'Role',
                'Action',
                'Result',
                'Entity Type',
                'Entity ID',
                'Reason',
                'Before',
                'After',
                'IP Address',
                'Request Method',
                'Request Path',
                'Request ID',
                'Integrity',
            ]);

            foreach ($logs as $log) {
                fputcsv($stream, [
                    $log->id,
                    $log->timestamp?->toISOString(),
                    $log->actor_name ?? $log->user?->username,
                    $log->actor_role ?? $log->user?->role,
                    $log->action,
                    $log->result,
                    $log->entity_type,
                    $log->entity_id,
                    $log->reason,
                    json_encode($log->old_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    json_encode($log->new_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    $log->ip_address,
                    $log->request_method,
                    $log->request_path,
                    $log->request_id,
                    $log->integrityStatus(),
                ]);
            }

            fclose($stream);
        }, 'audit-trail-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
