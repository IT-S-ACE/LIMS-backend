<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_database_seeder_can_be_safely_resumed(): void
    {
        $this->seed();
        $before = $this->counts();

        $this->seed();

        $this->assertSame($before, $this->counts());
        $this->assertGreaterThan(0, $before['users']);
        $this->assertGreaterThan(0, $before['patients']);
        $this->assertGreaterThan(0, $before['requests']);
        $this->assertGreaterThan(0, $before['stock_transactions']);
    }

    private function counts(): array
    {
        return [
            'users' => DB::table('users')->count(),
            'patients' => DB::table('patients')->count(),
            'tests' => DB::table('tests')->count(),
            'requests' => DB::table('test_requests')->count(),
            'samples' => DB::table('samples')->count(),
            'results' => DB::table('test_results')->count(),
            'reagents' => DB::table('reagents')->count(),
            'stock_transactions' => DB::table('stock_transactions')->count(),
            'notifications' => DB::table('notifications')->count(),
        ];
    }
}
