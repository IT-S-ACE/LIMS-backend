<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LimsReleaseCheck extends Command
{
    protected $signature = 'lims:release-check
        {--strict : Enforce production-only configuration requirements}';

    protected $description = 'Check database, migrations, storage, audit integrity, and release configuration';

    public function handle(): int
    {
        /** @var Migrator $migrator */
        $migrator = app('migrator');

        $checks = [];

        $this->check($checks, 'Application key', fn() => filled(config('app.key')));
        $this->check($checks, 'Database connection', function (): bool {
            DB::select('SELECT 1');

            return true;
        });
        $this->check(
            $checks,
            'Framework storage writable',
            fn() => is_writable(storage_path('framework'))
        );
        $this->check($checks, 'Required database tables', function (): bool {
            $required = [
                'users',
                'patients',
                'tests',
                'test_requests',
                'samples',
                'test_results',
                'medical_reports',
                'invoices',
                'payments',
                'reagents',
                'audit_logs',
            ];

            return collect($required)->every(fn(string $table) => Schema::hasTable($table));
        });
        $this->check($checks, 'No pending migrations', function () use ($migrator): bool {
            if (!$migrator->repositoryExists()) {
                return false;
            }

            $files = array_keys($migrator->getMigrationFiles(database_path('migrations')));

            return collect($files)->diff($migrator->getRepository()->getRan())->isEmpty();
        });
        $this->check($checks, 'Audit hash integrity', function (): bool {
            if (!Schema::hasTable('audit_logs')) {
                return false;
            }

            return AuditLog::query()
                ->whereNotNull('event_hash')
                ->latest('timestamp')
                ->limit(100)
                ->get()
                ->every(fn(AuditLog $log) => $log->integrityStatus() === 'VERIFIED');
        });

        if ($this->option('strict')) {
            $origins = (array) config('cors.allowed_origins', []);
            $this->check($checks, 'Production environment', fn() => app()->environment('production'));
            $this->check($checks, 'Debug mode disabled', fn() => config('app.debug') === false);
            $this->check(
                $checks,
                'Testing OTP disabled',
                fn() => config('auth.testing_otp.enabled') === false
            );
            $this->check(
                $checks,
                'HTTPS application URL',
                fn() => str_starts_with((string) config('app.url'), 'https://')
            );
            $this->check(
                $checks,
                'Explicit HTTPS CORS origins',
                fn() => $origins !== []
                    && !in_array('*', $origins, true)
                    && collect($origins)->every(
                        fn(string $origin) => str_starts_with($origin, 'https://')
                    )
            );
        }

        $this->newLine();
        $this->table(['Check', 'Result', 'Detail'], $checks);

        $failed = collect($checks)->contains(fn(array $row) => $row[1] === 'FAIL');

        if ($failed) {
            $this->error('Release readiness checks failed. Do not deploy.');

            return self::FAILURE;
        }

        $this->info('Release readiness checks passed.');

        return self::SUCCESS;
    }

    private function check(array &$checks, string $name, callable $callback): void
    {
        try {
            $passed = $callback() === true;
            $checks[] = [$name, $passed ? 'PASS' : 'FAIL', $passed ? 'OK' : 'Requirement not met'];
        } catch (Throwable $exception) {
            report($exception);
            $checks[] = [$name, 'FAIL', class_basename($exception)];
        }
    }
}
