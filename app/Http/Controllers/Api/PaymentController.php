<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(protected PaymentService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $payments = $this->service->getPayments($filters);

        return $this->successResponse([
            'payments' => PaymentResource::collection($payments->items()),
            'pagination' => $this->pagination($payments),
        ], 'Payments retrieved successfully.');
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        try {
            return $this->successResponse(
                new PaymentResource($this->service->processPayment($request->validated())),
                'Full payment recorded successfully.'
            );
        } catch (ValidationException $exception) {
            return $this->respondValidation($exception);
        } catch (Throwable $exception) {
            return $this->respondWithError($exception->getMessage());
        }
    }

    public function show(Payment $payment): JsonResponse
    {
        return $this->successResponse(
            new PaymentResource($this->service->getPayment($payment)),
            'Payment retrieved successfully.'
        );
    }

    public function invoices(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,paid,all'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $invoices = $this->service->getInvoices($filters);

        return $this->successResponse([
            'invoices' => InvoiceResource::collection($invoices->items()),
            'pagination' => $this->pagination($invoices),
        ], 'Invoices retrieved successfully.');
    }

    public function invoice(Invoice $invoice): JsonResponse
    {
        return $this->successResponse(
            new InvoiceResource($this->service->getInvoice($invoice)),
            'Invoice retrieved successfully.'
        );
    }

    public function balance(Patient $patient): JsonResponse
    {
        return $this->successResponse(
            $this->service->patientBalance($patient),
            'Patient balance retrieved successfully.'
        );
    }

    public function balances(): JsonResponse
    {
        return $this->successResponse(
            $this->service->allPatientBalances(),
            'Patient balances retrieved successfully.'
        );
    }

    private function pagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
