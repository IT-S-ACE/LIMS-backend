<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MedicalReport;
use App\Models\Patient;
use App\Models\ReagentLot;
use App\Models\StockTransaction;
use App\Models\SystemSetting;
use App\Models\Test;
use App\Models\TestRequest;
use App\Models\TestRequestItem;
use App\Observers\AuditTrailObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            Patient::class,
            Test::class,
            TestRequest::class,
            TestRequestItem::class,
            MedicalReport::class,
            Invoice::class,
            InvoiceItem::class,
            ReagentLot::class,
            StockTransaction::class,
            SystemSetting::class,
        ] as $model) {
            $model::observe(AuditTrailObserver::class);
        }
    }
}
