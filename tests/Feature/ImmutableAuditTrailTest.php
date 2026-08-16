<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImmutableAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_audit_trail(): void
    {
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        app(AuditLogService::class)->record(
            'Patient',
            '11111111-1111-1111-1111-111111111111',
            'UPDATE',
            ['phone' => '111'],
            ['phone' => '222'],
            'Corrected patient phone'
        );

        $this->getJson('/api/user/audit-logs?search=Corrected&action=UPDATE')
            ->assertOk()
            ->assertJsonPath('payload.pagination.total', 1)
            ->assertJsonPath('payload.items.0.action', 'UPDATE')
            ->assertJsonPath('payload.items.0.integrity.status', 'VERIFIED');
    }

    public function test_denied_export_attempt_is_recorded(): void
    {
        $receptionist = $this->user('receptionist');
        Sanctum::actingAs($receptionist);

        $this->get('/api/user/audit-logs/export')->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $receptionist->id,
            'entity_type' => 'AuditLog',
            'action' => 'EXPORT',
            'result' => 'DENIED',
        ]);
    }

    public function test_sensitive_model_creation_is_recorded_automatically(): void
    {
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        $patient = Patient::create([
            'name' => 'Audit Patient',
            'gender' => 'male',
            'phone' => '0999999999',
            'email' => 'audit-patient@example.test',
            'dob' => '1990-01-01',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'entity_type' => 'Patient',
            'entity_id' => $patient->id,
            'action' => 'CREATE',
            'result' => 'SUCCESS',
        ]);
    }

    public function test_audit_log_cannot_be_changed_through_eloquent(): void
    {
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);
        $log = app(AuditLogService::class)->record('Patient', null, 'VIEW');

        $this->expectException(\LogicException::class);
        $log->update(['reason' => 'Tampered']);
    }

    public function test_database_trigger_rejects_audit_log_deletion(): void
    {
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);
        $log = app(AuditLogService::class)->record('Patient', null, 'VIEW');

        $this->expectException(QueryException::class);
        DB::table('audit_logs')->where('id', $log->id)->delete();
    }

    public function test_no_update_or_delete_audit_api_exists(): void
    {
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        $path = '/api/user/audit-logs/Patient/11111111-1111-1111-1111-111111111111';
        $this->putJson($path)->assertStatus(405);
        $this->deleteJson($path)->assertStatus(405);
    }

    private function user(string $role): User
    {
        return User::create([
            'username' => $role . '-audit-user',
            'email' => $role . '-audit@example.test',
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
