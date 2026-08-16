<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class FinancialReportService
{
    public function report(string $fromDate, string $toDate): array
    {
        $from = Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay();
        $days = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $granularity = $days <= 62 ? 'day' : 'month';

        $invoices = Invoice::query()
            ->with([
                'testRequest.insuranceCompany:id,name,code',
            ])
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $payments = Payment::query()
            ->with([
                'recordedBy:id,username',
                'invoice:id,invoice_number,test_request_id',
                'invoice.testRequest:id,request_number,patient_id',
                'invoice.testRequest.patient:id,patient_number,name',
            ])
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'days' => $days,
                'granularity' => $granularity,
            ],
            'summary' => $this->summary($invoices, $payments),
            'billing_trend' => $this->billingTrend($invoices, $from, $to, $granularity),
            'collection_trend' => $this->collectionTrend($payments, $from, $to, $granularity),
            'payment_methods' => $this->paymentMethods($payments),
            'top_tests' => $this->topTests($from, $to),
            'coverage_by_company' => $this->coverageByCompany($invoices),
            'recent_payments' => $this->recentPayments($payments),
        ];
    }

    private function summary(Collection $invoices, Collection $payments): array
    {
        $grossBilled = $this->money($invoices->sum('total'));
        $insuranceCovered = $this->money($invoices->sum('insurance_amount'));
        $patientBilled = $this->money($invoices->sum('patient_due'));
        $patientCollected = $this->money($payments->sum('amount'));
        $periodOutstanding = $this->money($invoices->sum('remaining'));
        $currentOutstanding = $this->money(
            Invoice::query()->where('remaining', '>', 0)->sum('remaining')
        );
        $paidInvoices = $invoices->where('status', 'paid')->count();
        $unpaidInvoices = $invoices->where('status', '!=', 'paid')->count();

        return [
            'gross_billed' => $grossBilled,
            'insurance_covered' => $insuranceCovered,
            'patient_billed' => $patientBilled,
            'patient_collected' => $patientCollected,
            'period_outstanding' => $periodOutstanding,
            'current_outstanding' => $currentOutstanding,
            'invoices_count' => $invoices->count(),
            'paid_invoices_count' => $paidInvoices,
            'unpaid_invoices_count' => $unpaidInvoices,
            'payments_count' => $payments->count(),
            'insured_invoices_count' => $invoices
                ->filter(fn(Invoice $invoice) => $invoice->testRequest?->insurance_company_id !== null)
                ->count(),
            'coverage_rate' => $grossBilled > 0
                ? round(($insuranceCovered / $grossBilled) * 100, 2)
                : 0.0,
            'collection_rate' => $patientBilled > 0
                ? round(($this->money($invoices->sum('paid')) / $patientBilled) * 100, 2)
                : 0.0,
        ];
    }

    private function billingTrend(
        Collection $invoices,
        Carbon $from,
        Carbon $to,
        string $granularity
    ): array {
        $grouped = $invoices->groupBy(
            fn(Invoice $invoice) => $this->bucketKey($invoice->created_at, $granularity)
        );

        return $this->bucketKeys($from, $to, $granularity)
            ->map(function (array $bucket) use ($grouped) {
                $rows = $grouped->get($bucket['key'], collect());

                return [
                    'period' => $bucket['key'],
                    'label' => $bucket['label'],
                    'gross_billed' => $this->money($rows->sum('total')),
                    'insurance_covered' => $this->money($rows->sum('insurance_amount')),
                    'patient_due' => $this->money($rows->sum('patient_due')),
                    'invoices_count' => $rows->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function collectionTrend(
        Collection $payments,
        Carbon $from,
        Carbon $to,
        string $granularity
    ): array {
        $grouped = $payments->groupBy(
            fn(Payment $payment) => $this->bucketKey($payment->date, $granularity)
        );

        return $this->bucketKeys($from, $to, $granularity)
            ->map(function (array $bucket) use ($grouped) {
                $rows = $grouped->get($bucket['key'], collect());

                return [
                    'period' => $bucket['key'],
                    'label' => $bucket['label'],
                    'amount' => $this->money($rows->sum('amount')),
                    'transactions' => $rows->count(),
                ];
            })
            ->values()
            ->all();
    }

    private function paymentMethods(Collection $payments): array
    {
        $total = $this->money($payments->sum('amount'));

        return collect(['cash', 'card'])
            ->map(function (string $method) use ($payments, $total) {
                $rows = $payments->where('method', $method);
                $amount = $this->money($rows->sum('amount'));

                return [
                    'method' => $method,
                    'amount' => $amount,
                    'transactions' => $rows->count(),
                    'percentage' => $total > 0 ? round(($amount / $total) * 100, 2) : 0.0,
                ];
            })
            ->all();
    }

    private function topTests(Carbon $from, Carbon $to): array
    {
        return InvoiceItem::query()
            ->selectRaw(
                'tests.id AS test_id,
                 tests.name AS test_name,
                 SUM(invoice_items.quantity) AS quantity,
                 SUM(invoice_items.line_total) AS gross_billed,
                 SUM(invoice_items.insurance_amount) AS insurance_covered,
                 SUM(invoice_items.patient_amount) AS patient_due'
            )
            ->join(
                'test_request_items',
                'invoice_items.test_request_item_id',
                '=',
                'test_request_items.id'
            )
            ->join('tests', 'test_request_items.test_id', '=', 'tests.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.created_at', [$from, $to])
            ->groupBy('tests.id', 'tests.name')
            ->orderByDesc('gross_billed')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'test_id' => $row->test_id,
                'test_name' => $row->test_name,
                'quantity' => (int) $row->quantity,
                'gross_billed' => $this->money($row->gross_billed),
                'insurance_covered' => $this->money($row->insurance_covered),
                'patient_due' => $this->money($row->patient_due),
            ])
            ->all();
    }

    private function coverageByCompany(Collection $invoices): array
    {
        return $invoices
            ->filter(fn(Invoice $invoice) => $invoice->testRequest?->insuranceCompany !== null)
            ->groupBy(fn(Invoice $invoice) => $invoice->testRequest->insuranceCompany->id)
            ->map(function (Collection $rows) {
                $company = $rows->first()->testRequest->insuranceCompany;
                $gross = $this->money($rows->sum('total'));
                $covered = $this->money($rows->sum('insurance_amount'));

                return [
                    'company_id' => $company->id,
                    'company_code' => $company->code,
                    'company_name' => $company->name,
                    'invoices_count' => $rows->count(),
                    'gross_billed' => $gross,
                    'insurance_covered' => $covered,
                    'patient_due' => $this->money($rows->sum('patient_due')),
                    'coverage_rate' => $gross > 0 ? round(($covered / $gross) * 100, 2) : 0.0,
                ];
            })
            ->sortByDesc('insurance_covered')
            ->values()
            ->all();
    }

    private function recentPayments(Collection $payments): array
    {
        return $payments
            ->sortByDesc('date')
            ->take(10)
            ->map(function (Payment $payment) {
                $request = $payment->invoice?->testRequest;

                return [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'invoice_number' => $payment->invoice?->invoice_number,
                    'request_number' => $request?->request_number,
                    'patient_name' => $request?->patient?->name,
                    'method' => $payment->method,
                    'amount' => $this->money($payment->amount),
                    'recorded_by' => $payment->recordedBy?->username,
                    'date' => $payment->date?->toISOString(),
                ];
            })
            ->values()
            ->all();
    }

    private function bucketKeys(Carbon $from, Carbon $to, string $granularity): Collection
    {
        if ($granularity === 'month') {
            return collect(CarbonPeriod::create(
                $from->copy()->startOfMonth(),
                '1 month',
                $to->copy()->startOfMonth()
            ))->map(fn(Carbon $date) => [
                'key' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
            ]);
        }

        return collect(CarbonPeriod::create(
            $from->copy()->startOfDay(),
            '1 day',
            $to->copy()->startOfDay()
        ))->map(fn(Carbon $date) => [
            'key' => $date->format('Y-m-d'),
            'label' => $date->format('M j'),
        ]);
    }

    private function bucketKey($date, string $granularity): string
    {
        return Carbon::parse($date)->format($granularity === 'month' ? 'Y-m' : 'Y-m-d');
    }

    private function money($value): float
    {
        return round((float) $value, 2);
    }
}
