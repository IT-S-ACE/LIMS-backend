<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\InsuranceCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InsuranceCompanyService
{
    public function create(array $data): InsuranceCompany
    {
        return DB::transaction(function () use ($data) {
            $company = InsuranceCompany::create($data);
            $this->audit($company, 'create', [], $company->toArray(), 'Insurance company created');
            return $company;
        });
    }

    public function update(InsuranceCompany $company, array $data): InsuranceCompany
    {
        return DB::transaction(function () use ($company, $data) {
            $old = $company->only([
                'code', 'name', 'email', 'phone', 'default_coverage', 'status',
            ]);
            $company->update($data);
            $new = $company->fresh()->only(array_keys($old));
            $this->audit($company, 'update', $old, $new, 'Insurance company updated');
            return $company->refresh();
        });
    }

    public function delete(InsuranceCompany $company): void
    {
        if ($company->testRequests()->exists()) {
            throw ValidationException::withMessages([
                'insurance_company' => [
                    'A company used by test requests cannot be deleted. Mark it inactive instead.',
                ],
            ]);
        }

        DB::transaction(function () use ($company) {
            $old = $company->toArray();
            $this->audit($company, 'delete', $old, [], 'Insurance company deleted');
            $company->delete();
        });
    }

    private function audit(
        InsuranceCompany $company,
        string $action,
        array $old,
        array $new,
        string $reason
    ): void {
        if (!Auth::check()) {
            return;
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'entity_type' => 'InsuranceCompany',
            'entity_id' => $company->id,
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
    }
}
