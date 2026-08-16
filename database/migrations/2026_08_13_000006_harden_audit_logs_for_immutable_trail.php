<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // A previous interrupted attempt may have created one or more columns,
        // indexes or triggers. Remove the guards while the migration normalizes
        // existing rows, then recreate them after the schema is complete.
        $this->dropImmutableTriggers();

        $this->addColumnIfMissing('actor_name', function (Blueprint $table): void {
            $table->string('actor_name')->nullable()->after('user_id');
        });
        $this->addColumnIfMissing('actor_role', function (Blueprint $table): void {
            $table->string('actor_role', 50)->nullable()->after('actor_name');
        });
        $this->addColumnIfMissing('result', function (Blueprint $table): void {
            $table->string('result', 20)->default('SUCCESS')->after('action');
        });
        $this->addColumnIfMissing('request_method', function (Blueprint $table): void {
            $table->string('request_method', 10)->nullable()->after('ip_address');
        });
        $this->addColumnIfMissing('request_path', function (Blueprint $table): void {
            $table->text('request_path')->nullable()->after('request_method');
        });
        $this->addColumnIfMissing('request_id', function (Blueprint $table): void {
            $table->uuid('request_id')->nullable()->after('request_path');
        });
        $this->addColumnIfMissing('user_agent', function (Blueprint $table): void {
            $table->text('user_agent')->nullable()->after('request_id');
        });
        $this->addColumnIfMissing('metadata', function (Blueprint $table): void {
            $table->json('metadata')->nullable()->after('user_agent');
        });
        $this->addColumnIfMissing('event_hash', function (Blueprint $table): void {
            $table->char('event_hash', 64)->nullable()->after('metadata');
        });

        $this->addIndexIfMissing(
            'audit_logs_timestamp_id_index',
            fn(Blueprint $table) => $table->index(
                ['timestamp', 'id'],
                'audit_logs_timestamp_id_index'
            )
        );
        $this->addIndexIfMissing(
            'audit_logs_entity_index',
            fn(Blueprint $table) => $table->index(
                ['entity_type', 'entity_id'],
                'audit_logs_entity_index'
            )
        );
        $this->addIndexIfMissing(
            'audit_logs_user_timestamp_index',
            fn(Blueprint $table) => $table->index(
                ['user_id', 'timestamp'],
                'audit_logs_user_timestamp_index'
            )
        );
        $this->addIndexIfMissing(
            'audit_logs_action_result_index',
            fn(Blueprint $table) => $table->index(
                ['action', 'result'],
                'audit_logs_action_result_index'
            )
        );
        $this->addIndexIfMissing(
            'audit_logs_request_id_index',
            fn(Blueprint $table) => $table->index(
                'request_id',
                'audit_logs_request_id_index'
            )
        );

        $users = DB::table('users')
            ->get(['id', 'username', 'role'])
            ->keyBy('id');

        DB::table('audit_logs')
            ->whereNull('actor_name')
            ->select(['id', 'user_id'])
            ->chunkById(500, function ($logs) use ($users): void {
                foreach ($logs as $log) {
                    $actor = $users->get($log->user_id);

                    DB::table('audit_logs')
                        ->where('id', $log->id)
                        ->update([
                            'actor_name' => $actor?->username ?? 'Legacy User',
                            'actor_role' => $actor?->role ?? 'unknown',
                        ]);
                }
            }, 'id');

        DB::table('audit_logs')->update([
            'action' => DB::raw('UPPER(action)'),
            'result' => DB::raw('UPPER(result)'),
        ]);

        $this->createImmutableTriggers();
    }

    public function down(): void
    {
        $this->dropImmutableTriggers();

        foreach ([
            'audit_logs_timestamp_id_index',
            'audit_logs_entity_index',
            'audit_logs_user_timestamp_index',
            'audit_logs_action_result_index',
            'audit_logs_request_id_index',
        ] as $index) {
            if ($this->indexExists($index)) {
                Schema::table('audit_logs', fn(Blueprint $table) => $table->dropIndex($index));
            }
        }

        $columns = collect([
            'actor_name',
            'actor_role',
            'result',
            'request_method',
            'request_path',
            'request_id',
            'user_agent',
            'metadata',
            'event_hash',
        ])->filter(fn(string $column) => Schema::hasColumn('audit_logs', $column))->all();

        if ($columns !== []) {
            Schema::table('audit_logs', fn(Blueprint $table) => $table->dropColumn($columns));
        }
    }

    private function addColumnIfMissing(string $column, Closure $definition): void
    {
        if (!Schema::hasColumn('audit_logs', $column)) {
            Schema::table('audit_logs', $definition);
        }
    }

    private function addIndexIfMissing(string $index, Closure $definition): void
    {
        if (!$this->indexExists($index)) {
            Schema::table('audit_logs', $definition);
        }
    }

    private function indexExists(string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $row = DB::selectOne(
                'SELECT COUNT(*) AS aggregate
                 FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [DB::connection()->getDatabaseName(), 'audit_logs', $index]
            );

            return (int) ($row->aggregate ?? 0) > 0;
        }

        if ($driver === 'pgsql') {
            $row = DB::selectOne(
                'SELECT COUNT(*) AS aggregate
                 FROM pg_indexes
                 WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?',
                ['audit_logs', $index]
            );

            return (int) ($row->aggregate ?? 0) > 0;
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('audit_logs')"))
                ->contains(fn(object $row) => ($row->name ?? null) === $index);
        }

        return false;
    }

    private function createImmutableTriggers(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER audit_logs_prevent_update
                BEFORE UPDATE ON audit_logs
                FOR EACH ROW
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Audit logs are immutable and cannot be updated'
            SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER audit_logs_prevent_delete
                BEFORE DELETE ON audit_logs
                FOR EACH ROW
                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Audit logs are immutable and cannot be deleted'
            SQL);
        } elseif ($driver === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION prevent_audit_log_mutation()
                RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'Audit logs are immutable and cannot be modified';
                END;
                $$ LANGUAGE plpgsql
            SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER audit_logs_prevent_update
                BEFORE UPDATE ON audit_logs
                FOR EACH ROW EXECUTE FUNCTION prevent_audit_log_mutation()
            SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER audit_logs_prevent_delete
                BEFORE DELETE ON audit_logs
                FOR EACH ROW EXECUTE FUNCTION prevent_audit_log_mutation()
            SQL);
        } elseif ($driver === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER audit_logs_prevent_update
                BEFORE UPDATE ON audit_logs
                BEGIN
                    SELECT RAISE(ABORT, 'Audit logs are immutable and cannot be updated');
                END;
            SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER audit_logs_prevent_delete
                BEFORE DELETE ON audit_logs
                BEGIN
                    SELECT RAISE(ABORT, 'Audit logs are immutable and cannot be deleted');
                END;
            SQL);
        }
    }

    private function dropImmutableTriggers(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_prevent_update ON audit_logs');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_prevent_delete ON audit_logs');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_audit_log_mutation()');
        } else {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_prevent_update');
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_prevent_delete');
        }
    }
};
