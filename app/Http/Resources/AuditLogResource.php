<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'actor' => [
                'id' => $this->user_id,
                'name' => $this->actor_name ?? $this->user?->username ?? 'System',
                'role' => $this->actor_role ?? $this->user?->role ?? 'system',
            ],
            'entity' => [
                'type' => $this->entity_type,
                'id' => $this->entity_id,
            ],
            'action' => $this->action,
            'result' => $this->result,
            'reason' => $this->reason,
            'changes' => [
                'before' => $this->old_values,
                'after' => $this->new_values,
            ],
            'request' => [
                'id' => $this->request_id,
                'method' => $this->request_method,
                'path' => $this->request_path,
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent,
            ],
            'metadata' => $this->metadata,
            'integrity' => [
                'status' => $this->integrityStatus(),
                'hash' => $this->event_hash,
            ],
            'occurred_at' => $this->timestamp?->toISOString(),
        ];
    }
}
