<?php

namespace App\Services;

use App\Models\Reagent;
use App\Models\ReagentConsumption;
use App\Models\ReagentLot;
use App\Models\ReagentTest;
use App\Models\Sample;
use App\Models\StockTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected NotificationService $notificationService
    ) {
    }

    public function getAll(
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->inventoryQuery()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('lots', fn($query) => $query
                            ->where('lot_number', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(min(max($perPage, 1), 100));
    }

    public function show(Reagent $reagent): Reagent
    {
        return $this->inventoryQuery()
            ->with(['stockTransactions' => fn($query) => $query
                ->with(['lot', 'sample'])
                ->latest('date')
                ->limit(50)])
            ->findOrFail($reagent->id);
    }

    private function inventoryQuery()
    {
        return Reagent::query()
            ->with([
                'tests',
                'lots' => fn($query) => $query
                    ->orderBy('expiry_date')
                    ->orderBy('received_at'),
            ])
            ->withSum([
                'lots as available_stock_qty' => fn($query) => $query
                    ->whereDate('expiry_date', '>=', today()),
            ], 'remaining_quantity');
    }

    public function store(array $data): Reagent
    {
        return DB::transaction(function () use ($data) {
            $tests = $data['tests'] ?? [];
            $initialQuantity = (float) $data['initial_quantity'];

            $reagent = Reagent::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'category' => $data['category'] ?? null,
                'stock_qty' => $initialQuantity,
                'min_stock' => $data['min_stock'],
                'expiry_date' => $data['expiry_date'],
                'unit_price' => $data['unit_price'],
            ]);

            if ($initialQuantity > 0) {
                $lot = $this->createLot($reagent, [
                    'lot_number' => $data['lot_number'],
                    'quantity' => $initialQuantity,
                    'expiry_date' => $data['expiry_date'],
                    'received_at' => $data['received_at'],
                    'unit_price' => $data['unit_price'],
                ]);

                $this->recordStockTransaction(
                    $reagent,
                    $lot,
                    $initialQuantity,
                    'in',
                    'Initial stock',
                    $lot->lot_number
                );
                $this->notificationService->notifyLotExpiry($lot);
            }

            $this->syncTestUsages($reagent, $tests);

            $this->auditLogService->record(
                'Reagent',
                $reagent->id,
                'created',
                null,
                $reagent->only(['code', 'name', 'category', 'stock_qty', 'min_stock'])
            );

            return $this->show($reagent);
        });
    }

    public function update(Reagent $reagent, array $data): Reagent
    {
        return DB::transaction(function () use ($reagent, $data) {
            $tests = $data['tests'] ?? null;
            unset($data['tests']);

            $allowed = collect($data)->only([
                'name',
                'code',
                'category',
                'min_stock',
                'unit_price',
            ])->all();
            $old = $reagent->only(array_keys($allowed));
            $reagent->update($allowed);

            if (is_array($tests)) {
                $this->syncTestUsages($reagent, $tests);
            }

            $this->auditLogService->record(
                'Reagent',
                $reagent->id,
                'updated',
                $old,
                $reagent->fresh()->only(array_keys($allowed)),
                $data['reason'] ?? 'Reagent details updated'
            );

            return $this->show($reagent);
        });
    }

    public function updateStock(Reagent $reagent, array $data): Reagent
    {
        return DB::transaction(function () use ($reagent, $data) {
            $reagent = Reagent::query()->lockForUpdate()->findOrFail($reagent->id);
            $quantity = (float) $data['quantity'];

            if ($data['type'] === 'add') {
                $lot = $this->createLot($reagent, $data);
                $this->recordStockTransaction(
                    $reagent,
                    $lot,
                    $quantity,
                    'in',
                    $data['reason'],
                    $lot->lot_number
                );
                $this->notificationService->notifyLotExpiry($lot);
            } else {
                $this->consumeAvailableLots(
                    $reagent,
                    $quantity,
                    $data['reason'],
                    $data['reference'] ?? null
                );
            }

            $this->syncStockCache($reagent);
            $this->checkLowStock($reagent->fresh());

            $this->auditLogService->record(
                'Reagent',
                $reagent->id,
                'stock_' . $data['type'],
                null,
                ['quantity' => $quantity],
                $data['reason']
            );

            return $this->show($reagent);
        });
    }

    private function createLot(Reagent $reagent, array $data): ReagentLot
    {
        return $reagent->lots()->create([
            'lot_number' => $data['lot_number'],
            'initial_quantity' => $data['quantity'],
            'remaining_quantity' => $data['quantity'],
            'expiry_date' => $data['expiry_date'],
            'received_at' => $data['received_at'],
            'unit_price' => $data['unit_price'] ?? $reagent->unit_price,
        ]);
    }

    private function syncTestUsages(Reagent $reagent, array $tests): void
    {
        $reagent->testUsages()->delete();

        foreach ($tests as $usage) {
            ReagentTest::create([
                'reagent_id' => $reagent->id,
                'test_id' => $usage['test_id'],
                'quantity_used' => $usage['quantity_used'],
            ]);
        }
    }

    private function consumeAvailableLots(
        Reagent $reagent,
        float $quantity,
        string $reason,
        ?string $reference = null,
        ?Sample $sample = null,
        ?string $testRequestItemId = null
    ): void {
        $lots = ReagentLot::query()
            ->where('reagent_id', $reagent->id)
            ->where('remaining_quantity', '>', 0)
            ->whereDate('expiry_date', '>=', today())
            ->orderBy('expiry_date')
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $available = (float) $lots->sum('remaining_quantity');

        if ($available + 0.0005 < $quantity) {
            throw ValidationException::withMessages([
                'inventory' => [
                    "Insufficient stock for {$reagent->name}. Required: {$quantity}; available: {$available}.",
                ],
            ]);
        }

        $remaining = $quantity;

        foreach ($lots as $lot) {
            if ($remaining <= 0.0005) {
                break;
            }

            $allocated = min($remaining, (float) $lot->remaining_quantity);
            $lot->decrement('remaining_quantity', $allocated);

            $this->recordStockTransaction(
                $reagent,
                $lot,
                $allocated,
                'out',
                $reason,
                $reference,
                $sample
            );

            if ($sample && $testRequestItemId) {
                ReagentConsumption::create([
                    'sample_id' => $sample->id,
                    'test_request_item_id' => $testRequestItemId,
                    'reagent_id' => $reagent->id,
                    'reagent_lot_id' => $lot->id,
                    'quantity' => $allocated,
                    'created_by' => Auth::id(),
                ]);
            }

            $remaining = round($remaining - $allocated, 3);
        }
    }

    private function recordStockTransaction(
        Reagent $reagent,
        ReagentLot $lot,
        float $quantity,
        string $type,
        string $reason,
        ?string $reference = null,
        ?Sample $sample = null
    ): void {
        StockTransaction::create([
            'reagent_id' => $reagent->id,
            'reagent_lot_id' => $lot->id,
            'sample_id' => $sample?->id,
            'created_by' => Auth::id(),
            'quantity' => $quantity,
            'type' => $type,
            'reason' => $reason,
            'reference' => $reference,
            'date' => now(),
        ]);
    }

    public function consumeForSample(Sample $sample): void
    {
        if ($sample->reagents_consumed_at) {
            return;
        }

        $sample->loadMissing('testRequest.items.test.reagents');
        $items = $sample->testRequest?->items ?? collect();

        foreach ($items as $item) {
            if (!$item->test || $item->test->reagents->isEmpty()) {
                $testName = $item->test?->name ?? 'Unknown test';
                throw ValidationException::withMessages([
                    'inventory' => ["No reagent consumption rule is configured for {$testName}."],
                ]);
            }
        }

        $requirements = $this->buildRequirements($items);
        $reagentIds = $requirements->pluck('reagent_id')->unique()->sort()->values();

        $reagents = Reagent::query()
            ->whereIn('id', $reagentIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($requirements as $requirement) {
            $reagent = $reagents->get($requirement['reagent_id']);

            $this->consumeAvailableLots(
                $reagent,
                $requirement['quantity'],
                "Automatic consumption for sample {$sample->sample_number}",
                $sample->sample_number,
                $sample,
                $requirement['test_request_item_id']
            );
        }

        foreach ($reagents as $reagent) {
            $this->syncStockCache($reagent);
            $this->checkLowStock($reagent->fresh());
        }

        $sample->update(['reagents_consumed_at' => now()]);

        $this->auditLogService->record(
            'Sample',
            $sample->id,
            'reagents_consumed',
            null,
            ['allocations' => $requirements->count()],
            'Automatic FEFO consumption when analysis started'
        );
    }

    private function buildRequirements(Collection $items): Collection
    {
        return $items->flatMap(function ($item) {
            return $item->test->reagents->map(function ($reagent) use ($item) {
                return [
                    'test_request_item_id' => $item->id,
                    'reagent_id' => $reagent->id,
                    'quantity' => round(
                        (float) $reagent->pivot->quantity_used * (int) $item->quantity,
                        3
                    ),
                ];
            });
        })->values();
    }

    private function syncStockCache(Reagent $reagent): void
    {
        $available = (float) $reagent->lots()
            ->whereDate('expiry_date', '>=', today())
            ->sum('remaining_quantity');
        $nearestExpiry = $reagent->lots()
            ->where('remaining_quantity', '>', 0)
            ->whereDate('expiry_date', '>=', today())
            ->orderBy('expiry_date')
            ->value('expiry_date');

        $reagent->update([
            'stock_qty' => $available,
            'expiry_date' => $nearestExpiry ?? today(),
        ]);
    }

    public function checkLowStock(Reagent $reagent): void
    {
        if ((float) $reagent->stock_qty <= (float) $reagent->min_stock) {
            $this->notificationService->notifyLowStock($reagent);
        }
    }

    public function destroy(Reagent $reagent): void
    {
        DB::transaction(function () use ($reagent) {
            if (
                $reagent->lots()->exists()
                || $reagent->stockTransactions()->exists()
                || $reagent->tests()->exists()
                || $reagent->consumptions()->exists()
            ) {
                throw ValidationException::withMessages([
                    'reagent' => [
                        'A reagent with lots, movements, test rules, or consumption history cannot be deleted.',
                    ],
                ]);
            }

            $old = $reagent->only(['code', 'name']);
            $reagent->delete();
            $this->auditLogService->record('Reagent', $reagent->id, 'deleted', $old);
        });
    }

    public function expiredReagents()
    {
        return $this->inventoryQuery()
            ->whereHas('lots', fn($query) => $query
                ->where('remaining_quantity', '>', 0)
                ->whereDate('expiry_date', '<', today()))
            ->get();
    }

    public function lowStockReagents()
    {
        return $this->inventoryQuery()
            ->whereRaw(
                '(SELECT COALESCE(SUM(remaining_quantity), 0) FROM reagent_lots WHERE reagent_lots.reagent_id = reagents.id AND reagent_lots.expiry_date >= ?) <= reagents.min_stock',
                [today()->toDateString()]
            )
            ->get();
    }

    public function expiringSoonReagents()
    {
        return $this->inventoryQuery()
            ->whereHas('lots', fn($query) => $query
                ->where('remaining_quantity', '>', 0)
                ->whereBetween('expiry_date', [today(), today()->addDays(30)]))
            ->get();
    }
}
