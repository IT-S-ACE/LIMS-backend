<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MedicalReport;
use App\Models\Patient;
use App\Models\ReagentLot;
use App\Models\StockTransaction;
use App\Models\SystemSetting;
use App\Models\Test;
use App\Models\TestRequest;
use App\Models\TestRequestItem;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class AuditTrailObserver
{
    private const EVENTS = [
        Patient::class => ['created', 'deleted'],
        Test::class => ['created', 'deleted'],
        TestRequest::class => ['created', 'deleted'],
        TestRequestItem::class => ['created', 'updated', 'deleted'],
        MedicalReport::class => ['created'],
        Invoice::class => ['created', 'updated'],
        InvoiceItem::class => ['created', 'updated', 'deleted'],
        ReagentLot::class => ['created', 'updated', 'deleted'],
        StockTransaction::class => ['created'],
        SystemSetting::class => ['created', 'updated'],
    ];

    public function __construct(private readonly AuditLogService $auditLogs)
    {
    }

    public function created(Model $model): void
    {
        if (!$this->records($model, 'created')) {
            return;
        }

        $this->auditLogs->recordModelChange(
            $model,
            'CREATE',
            null,
            $this->auditLogs->snapshot($model->getAttributes()),
            class_basename($model) . ' created'
        );
    }

    public function updated(Model $model): void
    {
        if (!$this->records($model, 'updated')) {
            return;
        }

        $changes = collect($model->getChanges())
            ->except(['updated_at'])
            ->all();

        if ($changes === []) {
            return;
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        $this->auditLogs->recordModelChange(
            $model,
            'UPDATE',
            $this->auditLogs->snapshot($old),
            $this->auditLogs->snapshot($changes),
            class_basename($model) . ' updated'
        );
    }

    public function deleted(Model $model): void
    {
        if (!$this->records($model, 'deleted')) {
            return;
        }

        $this->auditLogs->recordModelChange(
            $model,
            'DELETE',
            $this->auditLogs->snapshot($model->getAttributes()),
            null,
            class_basename($model) . ' deleted'
        );
    }

    private function records(Model $model, string $event): bool
    {
        return in_array($event, self::EVENTS[$model::class] ?? [], true);
    }
}
