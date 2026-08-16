<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use LogicException;

class AuditLog extends Model
{
    use HasFactory;

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

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'actor_name',
        'actor_role',
        'entity_type',
        'entity_id',
        'action',
        'result',
        'old_values',
        'new_values',
        'reason',
        'ip_address',
        'request_method',
        'request_path',
        'request_id',
        'user_agent',
        'metadata',
        'event_hash',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log): void {
            $log->id ??= (string) Str::uuid();
            $log->action = Str::upper($log->action);
            $log->result = Str::upper($log->result ?: 'SUCCESS');
            $log->timestamp ??= now();
            $log->old_values = self::redact($log->old_values);
            $log->new_values = self::redact($log->new_values);
            $log->metadata = self::redact($log->metadata);

            $actor = Auth::user();
            if (!$actor && $log->user_id) {
                $actor = User::query()->find($log->user_id);
            }

            $log->actor_name ??= $actor?->username ?? 'System';
            $log->actor_role ??= $actor?->role ?? 'system';

            if (app()->bound('request')) {
                $request = request();
                $requestId = $request->attributes->get('audit_request_id');
                if (!$requestId) {
                    $requestId = (string) Str::uuid();
                    $request->attributes->set('audit_request_id', $requestId);
                }

                $log->ip_address ??= $request->ip();
                $log->request_method ??= $request->method();
                $log->request_path ??= '/' . ltrim($request->path(), '/');
                $log->request_id ??= $requestId;
                $log->user_agent ??= Str::limit((string) $request->userAgent(), 1000, '');
            }

            $log->event_hash = $log->calculateEventHash();
        });

        static::updating(function (): never {
            throw new LogicException('Audit logs are immutable and cannot be updated.');
        });

        static::deleting(function (): never {
            throw new LogicException('Audit logs are immutable and cannot be deleted.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function integrityStatus(): string
    {
        if (!$this->event_hash) {
            return 'LEGACY';
        }

        return hash_equals($this->event_hash, $this->calculateEventHash())
            ? 'VERIFIED'
            : 'FAILED';
    }

    public function calculateEventHash(): string
    {
        $payload = [
            'id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'actor_name' => (string) $this->actor_name,
            'actor_role' => (string) $this->actor_role,
            'entity_type' => (string) $this->entity_type,
            'entity_id' => (string) $this->entity_id,
            'action' => (string) $this->action,
            'result' => (string) $this->result,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'reason' => $this->reason,
            'ip_address' => $this->ip_address,
            'request_method' => $this->request_method,
            'request_path' => $this->request_path,
            'request_id' => $this->request_id,
            'metadata' => $this->metadata,
            'user_agent' => $this->user_agent,
            'timestamp' => optional($this->timestamp)->format('Y-m-d H:i:s'),
        ];

        return hash_hmac(
            'sha256',
            json_encode(
                $this->sortRecursively($payload),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            (string) config('app.key')
        );
    }

    private function sortRecursively(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->sortRecursively($item);
        }

        if (!array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }

    private static function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => $value) {
            $normalizedKey = Str::lower((string) $key);
            $sensitive = false;

            foreach (self::SENSITIVE_KEY_PATTERNS as $pattern) {
                if (Str::contains($normalizedKey, $pattern)) {
                    $sensitive = true;
                    break;
                }
            }

            $values[$key] = $sensitive
                ? '[REDACTED]'
                : (is_array($value) ? self::redact($value) : $value);
        }

        return $values;
    }
}
