<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MedicalReportSeeder extends Seeder
{
    public function run(): void
    {
        $requests = DB::table('test_requests')
            ->whereIn('request_number', [
                'REQ-2001',
                'REQ-2002',
                'REQ-2003',
            ])
            ->get();

        foreach ($requests as $request) {

            $path = "reports/{$request->id}.pdf";

            Storage::disk('public')->put(
                $path,
                "%PDF-1.4\n% Seeded Medical Report\n"
            );

            DB::table('medical_reports')->insert([
                'id' => Str::uuid(),
                'test_request_id' => $request->id,
                'pdf_path' => $path,
                'generated_at' => now()->subDays(1),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ]);
        }
    }
}