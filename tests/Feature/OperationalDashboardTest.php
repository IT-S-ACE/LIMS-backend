<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperationalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_staff_can_view_real_operational_statistics(): void
    {
        Carbon::setTestNow('2026-08-14 10:00:00');
        Sanctum::actingAs($this->user('admin'));
        $this->seedOperationalRecord();

        $this->getJson('/api/dashboard?days=7')
            ->assertOk()
            ->assertJsonPath('payload.period.days', 7)
            ->assertJsonPath('payload.statistics.patients_total', 1)
            ->assertJsonPath('payload.statistics.requests_today', 1)
            ->assertJsonPath('payload.statistics.samples_in_lab', 1)
            ->assertJsonPath('payload.statistics.pending_results', 1)
            ->assertJsonPath('payload.statistics.critical_results', 1)
            ->assertJsonPath('payload.permissions.financial', true)
            ->assertJsonPath('payload.request_status.processing', 1)
            ->assertJsonPath('payload.sample_status.in_progress', 1)
            ->assertJsonPath('payload.result_status.pending_review', 1)
            ->assertJsonPath('payload.attention.critical_results.0.result_number', 'RES-DASH-1')
            ->assertJsonPath('payload.recent_requests.0.request_number', 'REQ-DASH-1')
            ->assertJsonCount(7, 'payload.activity_trend');
    }

    public function test_technician_does_not_receive_financial_statistics(): void
    {
        Sanctum::actingAs($this->user('lab_technician'));

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('payload.permissions.financial', false)
            ->assertJsonPath('payload.statistics.revenue_today', null);
    }

    public function test_patient_cannot_access_staff_dashboard(): void
    {
        Sanctum::actingAs($this->user('patient'));

        $this->getJson('/api/dashboard')->assertForbidden();
    }

    public function test_dashboard_period_is_validated(): void
    {
        Sanctum::actingAs($this->user('admin'));

        $this->getJson('/api/dashboard?days=90')->assertStatus(400);
    }

    private function user(string $role): User
    {
        return User::create([
            'username' => $role . '-dashboard-user',
            'email' => $role . '-dashboard@example.test',
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function seedOperationalRecord(): void
    {
        $patientId = (string) Str::uuid();
        $requestId = (string) Str::uuid();
        $testId = (string) Str::uuid();
        $itemId = (string) Str::uuid();
        $sampleId = (string) Str::uuid();
        $now = now();

        DB::table('patients')->insert([
            'id' => $patientId,
            'patient_number' => 'PAT-DASH-1',
            'name' => 'Dashboard Patient',
            'gender' => 'male',
            'phone' => '0100000000',
            'email' => 'dashboard-patient@example.test',
            'dob' => '1990-01-01',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('test_requests')->insert([
            'id' => $requestId,
            'request_number' => 'REQ-DASH-1',
            'patient_id' => $patientId,
            'status' => 'processing',
            'total_price' => 50,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('tests')->insert([
            'id' => $testId,
            'name' => 'Dashboard Test',
            'price' => 50,
            'reference_range' => '1-10',
            'unit' => 'mg/dL',
            'result_type' => 'numeric',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('test_request_items')->insert([
            'id' => $itemId,
            'test_request_id' => $requestId,
            'test_id' => $testId,
            'quantity' => 1,
            'price' => 50,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('samples')->insert([
            'id' => $sampleId,
            'sample_number' => 'SMP-DASH-1',
            'barcode' => 'BC-DASH-1',
            'sample_type' => 'blood',
            'test_request_id' => $requestId,
            'qr_code' => 'QR-DASH-1',
            'status' => 'in_progress',
            'received_at' => $now->copy()->subHours(2),
            'collected_at' => $now->copy()->subHours(3),
            'created_at' => $now->copy()->subHours(3),
            'updated_at' => $now,
        ]);

        DB::table('test_results')->insert([
            'id' => (string) Str::uuid(),
            'sample_id' => $sampleId,
            'test_request_item_id' => $itemId,
            'value' => '25',
            'result_number' => 'RES-DASH-1',
            'value_unit' => 'mg/dL',
            'reference_range' => '1-10',
            'flag' => 'critical',
            'status' => 'draft',
            'workflow_status' => 'pending_review',
            'approved' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
