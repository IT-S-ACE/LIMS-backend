<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CoverageRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoverageRuleService
{
    public function create(array $data): CoverageRule
    {
        return DB::transaction(function () use ($data) {
            $rule = CoverageRule::create($data);
            $this->audit($rule, 'create', [], $rule->toArray(), 'Coverage rule created');
            return $rule->load(['insuranceCompany', 'test']);
        });
    }

    public function update(CoverageRule $rule, array $data): CoverageRule
    {
        return DB::transaction(function () use ($rule, $data) {
            $old = $rule->only([
                'insurance_company_id', 'test_id', 'coverage_percent', 'max_amount',
            ]);
            $rule->update($data);
            $new = $rule->fresh()->only(array_keys($old));
            $this->audit($rule, 'update', $old, $new, 'Coverage rule updated');
            return $rule->refresh()->load(['insuranceCompany', 'test']);
        });
    }

    public function delete(CoverageRule $rule): void
    {
        DB::transaction(function () use ($rule) {
            $old = $rule->toArray();
            $this->audit($rule, 'delete', $old, [], 'Coverage rule deleted');
            $rule->delete();
        });
    }

    private function audit(
        CoverageRule $rule,
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
            'entity_type' => 'CoverageRule',
            'entity_id' => $rule->id,
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ]);
    }
}
