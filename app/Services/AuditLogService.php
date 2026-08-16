<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuditLogService
{
    private const EMPTY_ENTITY_ID = '00000000-0000-0000-0000-000000000000';

    private const SENSITIVE_KEY_PATTERNS = [
        'password',
        'authorization',
        'token',
        'secret',
        'otp',
        'cookie',
        'card_number',
        'cvv',
        'client_secret',
    ];

    public function record(
        string $entityType,
        ?string $entityId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        ?string $ipAddress = null,
        string $result = 'SUCCESS',
        ?array $metadata = null,
        ?User $actor = null
    ): ?AuditLog {
        $actor ??= Auth::user();

        if (!$actor) {
            return null;
        }

        return AuditLog::create([
            'user_id' => $actor->id,
            'actor_name' => $actor->username,
            'actor_role' => $actor->role,
            'entity_type' => $entityType,
            'entity_id' => $entityId ?: self::EMPTY_ENTITY_ID,
            'action' => Str::upper($action),
            'result' => Str::upper($result),
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'metadata' => $this->sanitize($metadata),
            'timestamp' => now(),
        ]);
    }

    public function recordModelChange(
        Model $model,
        string $action,
        ?array $oldValues,
        ?array $newValues,
        ?string $reason = null
    ): ?AuditLog {
        return $this->record(
            class_basename($model),
            (string) $model->getKey(),
            $action,
            $oldValues,
            $newValues,
            $reason
        );
    }

    public function snapshot(array $values): array
    {
        unset($values['updated_at']);

        return $this->sanitize($values) ?? [];
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with('user:id,username,role')
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function getByEntity(string $type, string $id): Collection
    {
        return AuditLog::query()
            ->with('user:id,username,role')
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderByDesc('timestamp')
            ->get();
    }

    public function exportRows(array $filters, int $limit = 10000): Collection
    {
        return $this->filteredQuery($filters)
            ->with('user:id,username,role')
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function summary(array $filters): array
    {
        $query = $this->filteredQuery($filters);

        return [
            'total' => (clone $query)->count(),
            'success' => (clone $query)->where('result', 'SUCCESS')->count(),
            'denied' => (clone $query)->where('result', 'DENIED')->count(),
            'today' => (clone $query)->whereDate('timestamp', today())->count(),
        ];
    }

    public function filterOptions(): array
    {
        return [
            'actions' => AuditLog::query()
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->values(),
            'entity_types' => AuditLog::query()
                ->distinct()
                ->orderBy('entity_type')
                ->pluck('entity_type')
                ->values(),
            'actor_roles' => AuditLog::query()
                ->whereNotNull('actor_role')
                ->distinct()
                ->orderBy('actor_role')
                ->pluck('actor_role')
                ->values(),
            'results' => ['SUCCESS', 'DENIED'],
        ];
    }

    private function filteredQuery(array $filters): Builder
    {
        $query = AuditLog::query();

        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $escaped = addcslashes($search, '%_\\');
                $term = "%{$escaped}%";
                $query->where(function (Builder $query) use ($term): void {
                    $query
                        ->where('actor_name', 'like', $term)
                        ->orWhere('actor_role', 'like', $term)
                        ->orWhere('action', 'like', $term)
                        ->orWhere('entity_type', 'like', $term)
                        ->orWhere('entity_id', 'like', $term)
                        ->orWhere('reason', 'like', $term)
                        ->orWhere('request_id', 'like', $term);
                });
            })
            ->when($filters['user_id'] ?? null, fn(Builder $query, string $value) =>
                $query->where('user_id', $value)
            )
            ->when($filters['actor_role'] ?? null, fn(Builder $query, string $value) =>
                $query->where('actor_role', $value)
            )
            ->when($filters['action'] ?? null, fn(Builder $query, string $value) =>
                $query->where('action', Str::upper($value))
            )
            ->when($filters['entity_type'] ?? null, fn(Builder $query, string $value) =>
                $query->where('entity_type', $value)
            )
            ->when($filters['entity_id'] ?? null, fn(Builder $query, string $value) =>
                $query->where('entity_id', $value)
            )
            ->when($filters['result'] ?? null, fn(Builder $query, string $value) =>
                $query->where('result', Str::upper($value))
            )
            ->when($filters['from'] ?? null, fn(Builder $query, string $value) =>
                $query->where('timestamp', '>=', Carbon::createFromFormat('Y-m-d', $value)->startOfDay())
            )
            ->when($filters['to'] ?? null, fn(Builder $query, string $value) =>
                $query->where('timestamp', '<=', Carbon::createFromFormat('Y-m-d', $value)->endOfDay())
            );
    }

    private function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $sanitized = [];

        foreach ($values as $key => $value) {
            $normalizedKey = Str::lower((string) $key);
            $sensitive = collect(self::SENSITIVE_KEY_PATTERNS)
                ->contains(fn(string $pattern) => Str::contains($normalizedKey, $pattern));

            if ($sensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
