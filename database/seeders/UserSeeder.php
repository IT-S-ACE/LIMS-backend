<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'Demo users may only be seeded in the local or testing environment.'
            );
        }

        $accounts = [
            [
                'username' => 'admin',
                'email' => 'admin@medlab.test',
                'role' => 'admin',
            ],
            [
                'username' => 'reception',
                'email' => 'reception@medlab.test',
                'role' => 'receptionist',
            ],
            [
                'username' => 'technician',
                'email' => 'technician@medlab.test',
                'role' => 'lab_technician',
            ],
            [
                'username' => 'ahmad',
                'email' => 'ahmad.patient@medlab.test',
                'role' => 'patient',
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->firstOrCreate(
                ['email' => $account['email']],
                [
                    'username' => $account['username'],
                    'password' => Hash::make('password'),
                    'role' => $account['role'],
                    'status' => 'active',
                ]
            );
        }
    }
}
