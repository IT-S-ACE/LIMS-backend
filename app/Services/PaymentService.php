<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function processPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $invoice = Invoice::query()
                ->where('test_request_id', $data['test_request_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $invoice->status === 'paid'
                || (float) $invoice->remaining <= 0
            ) {
                throw ValidationException::withMessages([
                    'test_request_id' => ['This invoice is already paid.'],
                ]);
            }

            if ($invoice->payments()->exists()) {
                throw ValidationException::withMessages([
                    'test_request_id' => [
                        'A payment is already recorded for this invoice. Partial payments are not supported.',
                    ],
                ]);
            }

            $amount = round((float) $invoice->remaining, 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'test_request_id' => ['There is no patient balance to pay.'],
                ]);
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'recorded_by' => Auth::id(),
                'amount' => $amount,
                'method' => $data['method'],
                'notes' => $data['notes'] ?? null,
                'date' => now(),
            ]);

            $invoice->update([
                'paid' => $invoice->patient_due,
                'remaining' => 0,
                'status' => 'paid',
            ]);

            if (Auth::check()) {
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'entity_type' => 'Payment',
                    'entity_id' => $payment->id,
                    'action' => 'create',
                    'old_values' => [
                        'invoice_status' => 'pending',
                        'remaining' => $amount,
                    ],
                    'new_values' => [
                        'invoice_status' => 'paid',
                        'remaining' => 0,
                        'amount' => $amount,
                        'method' => $data['method'],
                    ],
                    'reason' => 'Full invoice payment recorded',
                    'ip_address' => request()->ip(),
                    'timestamp' => now(),
                ]);
            }

            return $payment->load([
                'recordedBy',
                'invoice.testRequest.patient',
                'invoice.testRequest.insuranceCompany',
                'invoice.items.testRequestItem.test',
            ]);
        });
    }

    public function getPayments(array $filters = []): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return Payment::query()
            ->with([
                'recordedBy',
                'invoice.testRequest.patient',
                'invoice.testRequest.insuranceCompany',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('payment_number', 'like', "%{$search}%")
                        ->orWhereHas('invoice', fn($query) =>
                            $query->where('invoice_number', 'like', "%{$search}%")
                        )
                        ->orWhereHas('invoice.testRequest.patient', fn($query) =>
                            $query->where('name', 'like', "%{$search}%")
                        );
                });
            })
            ->latest('date')
            ->paginate($perPage);
    }

    public function getPayment(Payment $payment): Payment
    {
        return $payment->load([
            'recordedBy',
            'invoice.testRequest.patient',
            'invoice.testRequest.insuranceCompany',
            'invoice.items.testRequestItem.test',
        ]);
    }

    public function getInvoices(array $filters = []): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? 'pending';
        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return Invoice::query()
            ->with([
                'testRequest.patient',
                'testRequest.insuranceCompany',
                'items.testRequestItem.test',
                'payments.recordedBy',
            ])
            ->when(
                $status === 'pending',
                fn($query) => $query
                    ->whereIn('status', ['pending', 'partial'])
                    ->where('remaining', '>', 0)
            )
            ->when(
                $status === 'paid',
                fn($query) => $query->where('status', 'paid')
            )
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('test_request_id', $search)
                        ->orWhereHas('testRequest', fn($query) =>
                            $query->where('request_number', 'like', "%{$search}%")
                        )
                        ->orWhereHas('testRequest.patient', fn($query) =>
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('patient_number', 'like', "%{$search}%")
                        );
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function getInvoice(Invoice $invoice): Invoice
    {
        return $invoice->load([
            'testRequest.patient',
            'testRequest.insuranceCompany',
            'items.testRequestItem.test',
            'payments.recordedBy',
        ]);
    }

    public function patientBalance(Patient $patient): array
    {
        $invoices = Invoice::query()
            ->whereHas(
                'testRequest',
                fn($query) => $query->where('patient_id', $patient->id)
            )
            ->get();

        return [
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'phone' => $patient->phone,
            'total' => round((float) $invoices->sum('patient_due'), 2),
            'paid' => round((float) $invoices->sum('paid'), 2),
            'remaining' => round((float) $invoices->sum('remaining'), 2),
            'invoices_count' => $invoices->count(),
        ];
    }

    public function allPatientBalances()
    {
        return Patient::query()
            ->whereHas('testRequests.invoice')
            ->with('testRequests.invoice')
            ->get()
            ->map(function (Patient $patient) {
                $invoices = $patient->testRequests
                    ->map(fn($request) => $request->invoice)
                    ->filter();

                return [
                    'patient_id' => $patient->id,
                    'patient_name' => $patient->name,
                    'phone' => $patient->phone,
                    'total' => round((float) $invoices->sum('patient_due'), 2),
                    'paid' => round((float) $invoices->sum('paid'), 2),
                    'remaining' => round((float) $invoices->sum('remaining'), 2),
                    'invoices_count' => $invoices->count(),
                ];
            })
            ->sortByDesc('remaining')
            ->values();
    }
}
