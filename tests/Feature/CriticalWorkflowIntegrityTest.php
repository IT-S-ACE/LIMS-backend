<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CriticalWorkflowIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_uat_data_contains_the_complete_critical_workflow(): void
    {
        $this->seed();

        $request = DB::table('test_requests')
            ->where('request_number', 'REQ-2001')
            ->first();

        $this->assertNotNull($request);
        $this->assertTrue(DB::table('patients')->where('id', $request->patient_id)->exists());
        $this->assertTrue(DB::table('test_request_items')
            ->where('test_request_id', $request->id)
            ->exists());
        $this->assertTrue(DB::table('samples')
            ->where('test_request_id', $request->id)
            ->exists());

        $itemIds = DB::table('test_request_items')
            ->where('test_request_id', $request->id)
            ->pluck('id');
        $this->assertTrue(DB::table('test_results')
            ->whereIn('test_request_item_id', $itemIds)
            ->exists());
        $this->assertTrue(DB::table('medical_reports')
            ->where('test_request_id', $request->id)
            ->exists());

        $invoice = DB::table('invoices')
            ->where('test_request_id', $request->id)
            ->first();
        $this->assertNotNull($invoice);
        $this->assertTrue(DB::table('payments')->where('invoice_id', $invoice->id)->exists());

        $this->assertTrue(DB::table('stock_transactions')->where('type', 'out')->exists());
        $this->assertTrue(DB::table('audit_logs')->exists());
    }
}
