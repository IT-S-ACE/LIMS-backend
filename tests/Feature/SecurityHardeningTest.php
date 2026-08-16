<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_security_and_request_headers(): void
    {
        $response = $this->getJson('/api/health/live');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            (string) $response->headers->get('X-Request-ID')
        );
    }

    public function test_patient_is_denied_staff_data_and_attempt_is_audited(): void
    {
        $patient = $this->user('patient');
        Sanctum::actingAs($patient);

        $this->getJson('/api/user/patients')
            ->assertForbidden()
            ->assertJsonPath('code', 'E007');

        $this->assertTrue(
            AuditLog::query()
                ->where('user_id', $patient->id)
                ->where('entity_type', 'Authorization')
                ->where('action', 'ACCESS')
                ->where('result', 'DENIED')
                ->exists()
        );
    }

    public function test_technician_can_read_tests_but_cannot_create_them(): void
    {
        Sanctum::actingAs($this->user('lab_technician'));

        $this->getJson('/api/user/tests')->assertOk();
        $this->postJson('/api/user/tests', [])->assertForbidden();
    }

    public function test_insecure_test_authentication_routes_do_not_exist(): void
    {
        $this->postJson('/api/user/login-test', [])->assertNotFound();
        $this->postJson('/api/user/verifyOTPTest', [])->assertNotFound();
    }

    public function test_password_cannot_be_reset_without_verified_otp(): void
    {
        $user = $this->user('admin');

        $this->postJson('/api/user/reset-password', [
            'email' => $user->email,
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
        ])->assertStatus(400);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_verified_otp_allows_one_password_reset_and_revokes_sessions(): void
    {
        Config::set('auth.testing_otp.enabled', true);
        Config::set('auth.testing_otp.email', 'secure-reset@example.test');
        Config::set('auth.testing_otp.code', '000000');

        $user = User::create([
            'username' => 'secure-reset-user',
            'email' => 'secure-reset@example.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
        $user->createToken('existing-session');

        $this->postJson('/api/user/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $this->assertDatabaseHas('user_otps', [
            'user_id' => $user->id,
            'otp' => '******',
            'attempts' => 0,
        ]);
        $this->assertDatabaseMissing('user_otps', [
            'user_id' => $user->id,
            'otp' => '000000',
        ]);

        $this->postJson('/api/user/verify-reset-password-otp', [
            'email' => $user->email,
            'otp' => '000000',
        ])->assertOk();

        $this->postJson('/api/user/reset-password', [
            'email' => $user->email,
            'password' => 'Secure123',
            'password_confirmation' => 'Secure123',
        ])->assertOk();

        $this->assertTrue(Hash::check('Secure123', $user->fresh()->password));
        $this->assertDatabaseMissing('user_otps', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_demo_user_seeder_is_idempotent_and_creates_all_local_roles(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertSame(4, User::query()
            ->whereIn('email', [
                'admin@medlab.test',
                'reception@medlab.test',
                'technician@medlab.test',
                'ahmad.patient@medlab.test',
            ])
            ->count());

        $this->assertDatabaseHas('users', [
            'email' => 'admin@medlab.test',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'reception@medlab.test',
            'role' => 'receptionist',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'technician@medlab.test',
            'role' => 'lab_technician',
            'status' => 'active',
        ]);
    }

    public function test_configured_demo_user_can_login_with_local_fixed_otp(): void
    {
        Config::set('auth.testing_otp.enabled', true);
        Config::set('auth.testing_otp.email', '');
        Config::set('auth.testing_otp.emails', ['reception@medlab.test']);
        Config::set('auth.testing_otp.code', '000000');
        $this->seed(UserSeeder::class);

        $this->postJson('/api/user/login', [
            'email' => 'reception@medlab.test',
            'password' => 'password',
        ])->assertOk();

        $this->postJson('/api/user/verifyOTP', [
            'email' => 'reception@medlab.test',
            'otp' => '000000',
            'type' => 'login',
        ])->assertOk();
    }

    private function user(string $role): User
    {
        return User::create([
            'username' => $role . '-security-user',
            'email' => $role . '-security@example.test',
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => 'active',
        ]);
    }
}
