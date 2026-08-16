<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;


class SystemSettingSeeder extends Seeder
{

    public function run(): void
    {

        SystemSetting::create([

            'lab_name' => 'MedLab Diagnostics',

            'license_number' => 'LIC-2024-887',

            'address' => 'Riyadh, KSA',

            'email_notifications' => true

        ]);

    }

}