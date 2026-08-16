<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patientUser = DB::table('users')
            ->where('username', 'ahmad')
            ->first();

        DB::table('patients')->insert([

            [
                'id' => Str::uuid(),
                'user_id' => $patientUser?->id,
                'patient_number' => 'PAT-1001',
                'name' => 'Yousef Ahmad',
                'gender' => 'male',
                'phone' => '+966500000004',
                'email' => 'yousef@medlab.test',
                'dob' => '1990-05-20',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'user_id' => null,
                'patient_number' => 'PAT-1002',
                'name' => 'Fatimah Al-Saleh',
                'gender' => 'female',
                'phone' => '+966511111111',
                'email' => 'fatimah@medlab.test',
                'dob' => '1988-03-12',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'user_id' => null,
                'patient_number' => 'PAT-1003',
                'name' => 'Khalid Al-Otaibi',
                'gender' => 'male',
                'phone' => '+966533456789',
                'email' => 'khalid@medlab.test',
                'dob' => '1975-11-22',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'user_id' => null,
                'patient_number' => 'PAT-1004',
                'name' => 'Omar Hassan',
                'gender' => 'male',
                'phone' => '+966544444444',
                'email' => 'omar@medlab.test',
                'dob' => '1995-08-10',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}