<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_check_passes_after_migrations(): void
    {
        $this->artisan('lims:release-check')
            ->assertSuccessful();
    }

    public function test_strict_release_check_runs_without_container_resolution_errors(): void
    {
        $this->artisan('lims:release-check --strict')
            ->assertFailed();
    }

    public function test_liveness_and_readiness_endpoints_are_available(): void
    {
        $this->getJson('/api/health/live')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['version']);

        $this->getJson('/api/health/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonPath('checks.storage', 'ok');
    }
}
