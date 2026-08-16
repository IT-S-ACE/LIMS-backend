<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserOtpSeeder extends Seeder
{
    public function run(): void
    {
        $admin = DB::table('users')
            ->where('username', 'admin')
            ->first();

        $reception = DB::table('users')
            ->where('username', 'reception')
            ->first();

        DB::table('user_otps')->insert([

            [
                'id' => Str::uuid(),
                'user_id' => $admin->id,
                'otp' => '123456',
                'type' => 'login',
                'expires_at' => now()->addMinutes(10),
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'id' => Str::uuid(),
                'user_id' => $reception->id,
                'otp' => '654321',
                'type' => 'reset_password',
                'expires_at' => now()->addMinutes(10),
                'verified_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ]);
    }
}