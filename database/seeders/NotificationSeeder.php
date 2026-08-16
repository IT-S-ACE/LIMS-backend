<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $patient = DB::table('patients')
            ->where('patient_number', 'PAT-1001')
            ->first();

        DB::table('notifications')->insert([

            [
                'id' => Str::uuid(),
                'patient_id' => $patient->id,
                'type' => 'result_ready',
                'message' => 'Your laboratory result is ready.',
                'channel' => 'email',
                'status' => 'sent',
                'read_at' => now()->subDay(),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],

            [
                'id' => Str::uuid(),
                'patient_id' => null,
                'type' => 'low_stock',
                'message' => 'Glucose Reagent is at 29 (min 40).',
                'channel' => 'in-app',
                'status' => 'pending',
                'read_at' => null,
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ],

            [
                'id' => Str::uuid(),
                'patient_id' => null,
                'type' => 'low_stock',
                'message' => 'COVID-19 PCR Kit is at 14 (min 25).',
                'channel' => 'in-app',
                'status' => 'pending',
                'read_at' => null,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],

            [
                'id' => Str::uuid(),
                'patient_id' => null,
                'type' => 'expiry_warning',
                'message' => 'COVID-19 PCR Kit will expire on 2026-07-30.',
                'channel' => 'in-app',
                'status' => 'pending',
                'read_at' => null,
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
            ],

        ]);
    }
}