<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Patient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\ValidationException;

class PatientService
{
    public function getPatients(array $data): LengthAwarePaginator
    {
        $search = $data['search'] ?? null;

        $perPage = $data['per_page'] ?? 15;

        return Patient::query()

            ->with([

                'testRequests.insuranceCompany',

                'testRequests.invoice',

            ])

            ->withCount([

                'testRequests',

                'notifications',

            ])

            ->when($search, function ($query) use ($search) {

                $query->where(function ($query) use ($search) {

                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('patient_number', 'like', "%{$search}%");

                });

            })

            ->latest()

            ->paginate($perPage);
    }

    public function getPatient(string $id): Patient
    {
        return Patient::query()
            ->with([
                'user',

                'testRequests' => function ($query) {
                    $query->latest();
                },

                'testRequests.insuranceCompany',

                'testRequests.items.test',

                'testRequests.samples',

                'testRequests.samples.results',

                'testRequests.medicalReport',

                'testRequests.invoice',

                'testRequests.invoice.items.testRequestItem.test',

                'testRequests.invoice.payments',

                'testRequests.invoice.refunds',

                'notifications',
            ])
            ->withCount([
                'testRequests',
                'notifications',
            ])
            ->where('id', $id)
            ->firstOrFail();
    }

    public function createPatient(array $data): Patient
    {
        return DB::transaction(function () use ($data) {

            return Patient::create([

                'name' => $data['name'],

                'gender' => $data['gender'],

                'phone' => $data['phone'],

                'email' => $data['email'] ?? null,

                'dob' => $data['dob'],

            ]);

        });
    }

    public function updatePatient(string $id, array $data): Patient
    {
        return DB::transaction(function () use ($id, $data) {

            $patient = Patient::query()
                ->where('id', $id)
                ->orWhere('phone', $id)
                ->firstOrFail();

            $old = $patient->only(array_keys($data));

            $patient->update($data);

            $new = $patient->fresh()->only(array_keys($data));

            if (Auth::check()) {
                AuditLog::create([

                    'user_id' => Auth::id(),

                    'entity_type' => 'Patient',

                    'entity_id' => $patient->id,

                    'action' => 'update',

                    'old_values' => $old,

                    'new_values' => $new,

                    'reason' => 'Updated patient',

                    'ip_address' => request()->ip(),

                    'timestamp' => now()

                ]);
            }
            return $patient->refresh();
        });
    }

    public function exportCsv(): StreamedResponse
    {

        $patients = Patient::query()

            ->with([
                'testRequests.insuranceCompany',
                'testRequests.invoice',
            ])

            ->get();

        return response()->streamDownload(

            function () use ($patients) {

                $handle = fopen('php://output', 'w');

                fputcsv($handle, [

                    'Patient Number',

                    'Name',

                    'Gender',

                    'Phone',

                    'Email',

                    'Insurance',

                    'Balance'

                ]);

                foreach ($patients as $patient) {

                    $insurance = $patient
                        ->testRequests
                        ->pluck('insuranceCompany.name')
                        ->filter()
                        ->unique()
                        ->implode(', ');

                    $balance = $patient
                        ->testRequests
                        ->pluck('invoice')
                        ->filter()
                        ->sum('remaining');

                    fputcsv($handle, [

                        $patient->patient_number,

                        $patient->name,

                        $patient->gender,

                        $patient->phone,

                        $patient->email,

                        $insurance,

                        $balance,

                    ]);

                }

                fclose($handle);

            },

            'patients.csv',

            [

                'Content-Type' => 'text/csv',

            ]

        );

    }

    public function deletePatient(
        string $id
    ): void {

        DB::transaction(function () use ($id) {

            $patient = Patient::findOrFail($id);

            if ($patient->testRequests()->exists()) {
                throw ValidationException::withMessages([
                    'patient' => [
                        'This patient has existing test requests and cannot be deleted.',
                    ],
                ]);
            }

            $patient->delete();

        });

    }

}
