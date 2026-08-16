<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Test;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TestService
{


    public function getTests(
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {

        return Test::query()

            ->withCount([
                'reagents',
            ])

            ->when(
                $search,
                function ($query) use ($search) {
                    $query->where(
                        'name',
                        'like',
                        '%' . $search . '%'
                    );
                }
            )

            ->latest()

            ->paginate($perPage);
    }

    public function getTest(
        Test $test
    ): Test {

        return $test->load([
            'reagents',
        ])->loadCount([
                    'reagents',
                ]);
    }

    public function createTest(
        array $data
    ): Test {

        return DB::transaction(
            function () use ($data) {

                $test = Test::create([
                    'name' => $data['name'],

                    'price' => $data['price'],

                    'reference_range' =>
                        $data['reference_range'],
                    'unit' => $data['unit'],
                    'result_type' => $data['result_type'],
                    'result_options' => $data['result_type'] === 'choice'
                        ? array_values($data['result_options'] ?? [])
                        : null,
                    'critical_low' => $data['critical_low'] ?? null,
                    'critical_high' => $data['critical_high'] ?? null,
                ]);

                return $this->getTest($test);
            }
        );
    }



    public function updateTest(
        Test $test,
        array $data
    ): Test {

        return DB::transaction(
            function () use ($test, $data) {

                $reason = $data['reason'];
                unset($data['reason']);

                $old = $test->only(array_keys($data));

                $test->update($data);

                $new = $test->fresh()->only(array_keys($data));

                if (Auth::check()) {
                    AuditLog::create([

                        'user_id' => Auth::id(),

                        'entity_type' => 'Test',

                        'entity_id' => $test->id,

                        'action' => 'update',

                        'old_values' => $old,

                        'new_values' => $new,

                        'reason' => $reason,

                        'ip_address' => request()->ip(),

                        'timestamp' => now()

                    ]);
                }
                return $this->getTest(
                    $test->refresh()
                );
            }
        );
    }


    public function deleteTest(
        Test $test
    ): void {

        DB::transaction(
            function () use ($test) {

                if ($test->testRequestItems()->exists()) {
                    throw ValidationException::withMessages([
                        'test' => [
                            'This test is used by an existing test request and cannot be deleted.',
                        ],
                    ]);
                }

                $test->delete();

            }
        );
    }
}
