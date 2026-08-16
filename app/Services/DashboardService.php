<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Payment;
use App\Models\Reagent;
use App\Models\ReagentLot;
use App\Models\Sample;
use App\Models\TestRequest;
use App\Models\TestRequestItem;
use App\Models\TestResult;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
    private const SAMPLE_STATUSES = [
        'registered',
        'collected',
        'in_progress',
        'completed',
        'rejected',
        'cancelled',
    ];

    private const REQUEST_STATUSES = [
        'pending',
        'processing',
        'completed',
        'cancelled',
    ];

    private const RESULT_STATUSES = [
        'draft',
        'pending_review',
        'reviewed',
        'correction_required',
        'approved',
    ];

    public function getDashboard(User $user, int $days = 7): array
    {
        $to = now()->endOfDay();
        $from = now()->subDays($days - 1)->startOfDay();
        $canViewFinancials = in_array($user->role, ['admin', 'receptionist'], true);

        return [
            'generated_at' => now()->toIso8601String(),
            'period' => [
                'days' => $days,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'permissions' => [
                'financial' => $canViewFinancials,
            ],
            'statistics' => $this->statistics($from, $to, $canViewFinancials),
            'activity_trend' => $this->activityTrend($from, $to),
            'request_status' => $this->statusCounts(
                TestRequest::query(),
                'status',
                self::REQUEST_STATUSES
            ),
            'sample_status' => $this->statusCounts(
                Sample::query(),
                'status',
                self::SAMPLE_STATUSES
            ),
            'result_status' => $this->statusCounts(
                TestResult::query(),
                'workflow_status',
                self::RESULT_STATUSES
            ),
            'attention' => [
                'overdue_samples' => $this->overdueSamples(),
                'critical_results' => $this->criticalResults(),
            ],
            'inventory' => $this->inventory(),
            'recent_requests' => $this->recentRequests(),
            'top_tests' => $this->topTests($from, $to),
        ];
    }

    public function search(?string $keyword): array
    {
        if (!$keyword) {
            return [];
        }

        $patients = Patient::query()
            ->where('name', 'like', "%{$keyword}%")
            ->limit(5)
            ->get()
            ->map(fn(Patient $patient) => [
                'type' => 'patient',
                'id' => $patient->id,
                'title' => $patient->name,
                'subtitle' => $patient->phone,
            ]);

        $requests = TestRequest::query()
            ->with('patient')
            ->where(function (Builder $query) use ($keyword) {
                $query->where('request_number', 'like', "%{$keyword}%")
                    ->orWhereHas('patient', function (Builder $patientQuery) use ($keyword) {
                        $patientQuery->where('name', 'like', "%{$keyword}%");
                    });
            })
            ->limit(5)
            ->get()
            ->map(fn(TestRequest $request) => [
                'type' => 'request',
                'id' => $request->id,
                'title' => $request->request_number,
                'subtitle' => $request->patient?->name,
            ]);

        return [
            'patients' => $patients,
            'requests' => $requests,
        ];
    }

    private function statistics(Carbon $from, Carbon $to, bool $canViewFinancials): array
    {
        $periodRequests = TestRequest::query()
            ->whereBetween('created_at', [$from, $to]);
        $completedRequests = (clone $periodRequests)
            ->where('status', 'completed')
            ->count();
        $eligibleRequests = (clone $periodRequests)
            ->where('status', '!=', 'cancelled')
            ->count();

        $completedSamples = Sample::query()
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$from, $to])
            ->whereNotNull('received_at')
            ->get(['received_at', 'updated_at']);

        return [
            'patients_total' => Patient::query()->count(),
            'requests_today' => TestRequest::query()
                ->whereDate('created_at', today())
                ->count(),
            'requests_in_period' => (clone $periodRequests)->count(),
            'samples_in_lab' => Sample::query()
                ->whereIn('status', ['collected', 'in_progress'])
                ->count(),
            'pending_results' => TestResult::query()
                ->where('approved', false)
                ->count(),
            'critical_results' => TestResult::query()
                ->where('flag', 'critical')
                ->where('approved', false)
                ->count(),
            'completed_today' => TestResult::query()
                ->where('approved', true)
                ->whereDate('approved_at', today())
                ->count(),
            'completion_rate' => $eligibleRequests > 0
                ? round(($completedRequests / $eligibleRequests) * 100, 1)
                : 0.0,
            'average_turnaround_hours' => $this->averageTurnaroundHours($completedSamples),
            'revenue_today' => $canViewFinancials
                ? round((float) Payment::query()->whereDate('date', today())->sum('amount'), 2)
                : null,
        ];
    }

    private function activityTrend(Carbon $from, Carbon $to): array
    {
        $requests = TestRequest::query()
            ->whereBetween('created_at', [$from, $to])
            ->get(['created_at'])
            ->countBy(fn(TestRequest $request) => $request->created_at->toDateString());

        $completedResults = TestResult::query()
            ->where('approved', true)
            ->whereBetween('approved_at', [$from, $to])
            ->get(['approved_at'])
            ->countBy(fn(TestResult $result) => $result->approved_at->toDateString());

        return collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(function (Carbon $date) use ($requests, $completedResults) {
                $key = $date->toDateString();

                return [
                    'date' => $key,
                    'label' => $date->format('M j'),
                    'requests' => (int) $requests->get($key, 0),
                    'completed_results' => (int) $completedResults->get($key, 0),
                ];
            })
            ->values()
            ->all();
    }

    private function statusCounts(Builder $query, string $column, array $statuses): array
    {
        $counts = $query
            ->selectRaw("{$column} as dashboard_status, COUNT(*) as dashboard_total")
            ->groupBy($column)
            ->pluck('dashboard_total', 'dashboard_status');

        return collect($statuses)
            ->mapWithKeys(fn(string $status) => [$status => (int) $counts->get($status, 0)])
            ->all();
    }

    private function overdueSamples(): array
    {
        $cutoff = now()->subHours(24);

        return Sample::query()
            ->with([
                'testRequest:id,request_number,patient_id',
                'testRequest.patient:id,name,patient_number',
            ])
            ->whereIn('status', ['collected', 'in_progress'])
            ->where(function (Builder $query) use ($cutoff) {
                $query->where('received_at', '<', $cutoff)
                    ->orWhere(function (Builder $receivedQuery) use ($cutoff) {
                        $receivedQuery->whereNull('received_at')
                            ->where('collected_at', '<', $cutoff);
                    })
                    ->orWhere(function (Builder $collectedQuery) use ($cutoff) {
                        $collectedQuery->whereNull('received_at')
                            ->whereNull('collected_at')
                            ->where('created_at', '<', $cutoff);
                    });
            })
            ->orderByRaw('COALESCE(received_at, collected_at, created_at) ASC')
            ->limit(8)
            ->get()
            ->map(function (Sample $sample) {
                $startedAt = $sample->received_at ?? $sample->collected_at ?? $sample->created_at;

                return [
                    'id' => $sample->id,
                    'sample_number' => $sample->sample_number,
                    'barcode' => $sample->barcode,
                    'status' => $sample->status,
                    'request_number' => $sample->testRequest?->request_number,
                    'patient' => $sample->testRequest?->patient?->name,
                    'waiting_hours' => round(Carbon::parse($startedAt)->diffInMinutes(now()) / 60, 1),
                ];
            })
            ->all();
    }

    private function criticalResults(): array
    {
        return TestResult::query()
            ->with([
                'sample:id,sample_number,test_request_id',
                'sample.testRequest:id,patient_id',
                'sample.testRequest.patient:id,name,patient_number',
                'testRequestItem:id,test_id',
                'testRequestItem.test:id,name',
            ])
            ->where('flag', 'critical')
            ->where('approved', false)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn(TestResult $result) => [
                'id' => $result->id,
                'result_number' => $result->result_number,
                'sample_number' => $result->sample?->sample_number,
                'patient' => $result->sample?->testRequest?->patient?->name,
                'test' => $result->testRequestItem?->test?->name,
                'value' => $result->value,
                'unit' => $result->value_unit,
                'workflow_status' => $result->workflow_status,
                'created_at' => $result->created_at?->toIso8601String(),
            ])
            ->all();
    }

    private function inventory(): array
    {
        $today = today();
        $soon = today()->addDays(30);

        $lowStock = Reagent::query()
            ->with(['lots' => function ($query) {
                $query->where('remaining_quantity', '>', 0)
                    ->orderBy('expiry_date');
            }])
            ->whereColumn('stock_qty', '<=', 'min_stock')
            ->orderBy('stock_qty')
            ->limit(8)
            ->get();

        return [
            'low_stock_items' => Reagent::query()
                ->whereColumn('stock_qty', '<=', 'min_stock')
                ->count(),
            'expired_lots' => ReagentLot::query()
                ->where('remaining_quantity', '>', 0)
                ->whereDate('expiry_date', '<', $today)
                ->count(),
            'expiring_soon_lots' => ReagentLot::query()
                ->where('remaining_quantity', '>', 0)
                ->whereBetween('expiry_date', [$today, $soon])
                ->count(),
            'items' => $lowStock
                ->map(fn(Reagent $reagent) => [
                    'id' => $reagent->id,
                    'code' => $reagent->code,
                    'name' => $reagent->name,
                    'stock' => (float) $reagent->stock_qty,
                    'minimum' => (float) $reagent->min_stock,
                    'nearest_expiry' => $reagent->lots->first()?->expiry_date?->toDateString(),
                ])
                ->all(),
        ];
    }

    private function recentRequests(): array
    {
        return TestRequest::query()
            ->with('patient:id,name,patient_number')
            ->withCount('items')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn(TestRequest $request) => [
                'id' => $request->id,
                'request_number' => $request->request_number,
                'patient' => $request->patient?->name,
                'patient_number' => $request->patient?->patient_number,
                'tests_count' => (int) $request->items_count,
                'status' => $request->status,
                'created_at' => $request->created_at?->toIso8601String(),
            ])
            ->all();
    }

    private function topTests(Carbon $from, Carbon $to): array
    {
        return TestRequestItem::query()
            ->selectRaw('tests.id as test_id, tests.name as test_name, SUM(test_request_items.quantity) as total')
            ->join('tests', 'test_request_items.test_id', '=', 'tests.id')
            ->join('test_requests', 'test_request_items.test_request_id', '=', 'test_requests.id')
            ->whereBetween('test_requests.created_at', [$from, $to])
            ->groupBy('tests.id', 'tests.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'test_id' => $row->test_id,
                'test_name' => $row->test_name,
                'count' => (int) $row->total,
            ])
            ->all();
    }

    private function averageTurnaroundHours(Collection $samples): float
    {
        if ($samples->isEmpty()) {
            return 0.0;
        }

        return round(
            $samples->average(
                fn(Sample $sample) => $sample->received_at->diffInMinutes($sample->updated_at) / 60
            ),
            1
        );
    }
}
