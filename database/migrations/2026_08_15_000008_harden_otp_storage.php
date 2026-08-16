<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('user_otps', 'otp_hash')) {
            Schema::table('user_otps', function (Blueprint $table) {
                $table->char('otp_hash', 64)->nullable()->after('otp');
            });
        }

        if (!Schema::hasColumn('user_otps', 'attempts')) {
            Schema::table('user_otps', function (Blueprint $table) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('otp_hash');
            });
        }

        DB::table('user_otps')
            ->whereNull('otp_hash')
            ->select(['id', 'otp'])
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('user_otps')
                        ->where('id', $row->id)
                        ->update([
                            'otp' => '******',
                            'otp_hash' => hash_hmac(
                                'sha256',
                                (string) $row->otp,
                                (string) config('app.key')
                            ),
                        ]);
                }
            }, 'id');

        if (!$this->indexExists('user_otps', 'user_otps_lookup_idx')) {
            Schema::table('user_otps', function (Blueprint $table) {
                $table->index(
                    ['user_id', 'type', 'created_at'],
                    'user_otps_lookup_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('user_otps', 'user_otps_lookup_idx')) {
            Schema::table('user_otps', function (Blueprint $table) {
                $table->dropIndex('user_otps_lookup_idx');
            });
        }

        $hasAttempts = Schema::hasColumn('user_otps', 'attempts');
        $hasOtpHash = Schema::hasColumn('user_otps', 'otp_hash');

        if ($hasAttempts || $hasOtpHash) {
            Schema::table('user_otps', function (Blueprint $table) use ($hasAttempts, $hasOtpHash) {
                if ($hasAttempts) {
                    $table->dropColumn('attempts');
                }

                if ($hasOtpHash) {
                    $table->dropColumn('otp_hash');
                }
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return match (DB::getDriverName()) {
            'mysql' => DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists(),
            'pgsql' => DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists(),
            'sqlite' => collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn($row) => $row->name === $index),
            default => false,
        };
    }
};
