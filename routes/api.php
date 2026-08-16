<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\CoverageRuleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FinancialReportController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\InsuranceCompanyController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\InventoryAlertController;
use App\Http\Controllers\Api\InventoryDashboardController;
use App\Http\Controllers\Api\InventoryReportController;
use App\Http\Controllers\Api\MedicalReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReagentController;
use App\Http\Controllers\Api\SampleController;
use App\Http\Controllers\Api\SystemSettingController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\TestRequestController;
use App\Http\Controllers\Api\TestResultController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ResetPasswordController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('health')->group(function () {
    Route::get('live', [HealthController::class, 'live']);
    Route::get('ready', [HealthController::class, 'ready']);
});

/*
|--------------------------------------------------------------------------
| dashboard Routes
|--------------------------------------------------------------------------
*/
//done 4
Route::prefix('dashboard')->middleware([
    'auth:sanctum',
    'role:admin,receptionist,lab_technician',
])->group(function () {

    Route::get(
        '/',
        [
            DashboardController::class,
            'index'
        ]
    );

    Route::get(
        '/search',
        [
            DashboardController::class,
            'indexsearch'
        ]
    );

    Route::get(
        '/settings',
        [
            SystemSettingController::class,
            'show'
        ]
    )->middleware('role:admin');


    Route::put(
        '/settings',
        [
            SystemSettingController::class,
            'update'
        ]
    )->middleware('role:admin');



});


/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
//done 9
Route::prefix('user')->middleware('throttle:authentication')->group(function () {

    Route::post('register', [AuthController::class, 'register']);

    Route::post('login', [AuthController::class, 'login']);

    Route::post('resendOTP', [AuthController::class, 'resendOTP']);

    Route::post('verifyOTP', [AuthController::class, 'verifyOTP']);

    Route::post('forgot-password', [ResetPasswordController::class, 'sendOTP']);

    Route::post('verify-reset-password-otp', [ResetPasswordController::class, 'verifyOTP']);

    Route::post('reset-password', [ResetPasswordController::class, 'resetPassword']);

});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/
Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    // done 2
    Route::get(
        'profile',
        [ProfileController::class, 'profile']
    );

    Route::post(
        'logout',
        [ProfileController::class, 'logout']
    );

    //done 6
    Route::prefix('patients')
        ->middleware('role:admin,receptionist')
        ->group(function () {

        Route::get(
            '/',
            [PatientController::class, 'index']
        );

        Route::post(
            '/',
            [PatientController::class, 'store']
        );

        Route::get(
            'export',
            [PatientController::class, 'exportCsv']
        );

        Route::get(
            '{id}',
            [PatientController::class, 'show']
        );

        Route::put(
            '{id}',
            [PatientController::class, 'update']
        );

        Route::delete(
            '{id}',
            [PatientController::class, 'destroy']
        );

    });
    //done 6
    Route::prefix('test-requests')
        ->middleware('role:admin,receptionist,lab_technician')
        ->group(function () {

        Route::get(
            '/',
            [TestRequestController::class, 'index']
        );

        Route::post(
            '/',
            [TestRequestController::class, 'store']
        )->middleware('role:admin,receptionist');

        Route::get(
            'export',
            [TestRequestController::class, 'exportCsv']
        );

        Route::get(
            '{id}',
            [TestRequestController::class, 'show']
        );

        Route::put(
            '{testRequest}',
            [TestRequestController::class, 'update']
        )->middleware('role:admin,receptionist');

        Route::delete(
            '{testRequest}',
            [TestRequestController::class, 'destroy']
        )->middleware('role:admin,receptionist');
    });
    //done 10
    Route::prefix('samples')
        ->middleware('role:admin,receptionist,lab_technician')
        ->group(function () {

        Route::get(
            '/',
            [SampleController::class, 'index']
        );

        Route::get(
            '{sample}/enter-result',
            [SampleController::class, 'enterResult']
        );

        Route::post(
            '/',
            [SampleController::class, 'store']
        )->middleware('role:admin,receptionist');

        Route::get(
            'export',
            [SampleController::class, 'exportCsv']
        );

        Route::get(
            '{id}',
            [SampleController::class, 'show']
        );

        Route::patch(
            '{sample}/status',
            [SampleController::class, 'updateStatus']
        );


        Route::get(
            'track/{code}',
            [SampleController::class, 'track']
        );


        Route::post(
            '{sample}/reject',
            [SampleController::class, 'reject']
        );


        Route::post(
            '{sample}/cancel',
            [SampleController::class, 'cancel']
        );

        Route::delete(
            '{sample}',
            [SampleController::class, 'destroy']
        )->middleware('role:admin');
    });
    //done 7
    Route::prefix('test-results')
        ->middleware('role:admin,lab_technician')
        ->group(function () {

        Route::get(
            '/',
            [TestResultController::class, 'list']
        );

        Route::get(
            'export',
            [TestResultController::class, 'exportCsv']
        );

        Route::get(
            'sample/{sample}/workspace',
            [TestResultController::class, 'workspace']
        );

        Route::put(
            'sample/{sample}',
            [TestResultController::class, 'saveSampleResults']
        );

        Route::post(
            'sample/{sample}/submit',
            [TestResultController::class, 'submitSampleResults']
        );

        Route::get(
            'sample/{sample}',
            [TestResultController::class, 'index']
        );

        Route::post(
            '{testResult}/review',
            [TestResultController::class, 'review']
        );

        Route::post(
            '{testResult}/return',
            [TestResultController::class, 'returnForCorrection']
        );

        Route::post(
            '{testResult}/approve',
            [TestResultController::class, 'approve']
        );

        Route::get(
            '{testResult}',
            [TestResultController::class, 'show']
        );

    });
    //done 6
    Route::prefix('medical-reports')
        ->middleware('role:admin,lab_technician,receptionist')
        ->group(function () {

        Route::get(
            '/export',
            [MedicalReportController::class, 'export']
        );

        Route::get(
            '/',
            [
                MedicalReportController::class,
                'index'
            ]
        );

        Route::post(
            '/test-requests/{testRequest}/report',
            [
                MedicalReportController::class,
                'generate'
            ]
        );

        Route::get(
            '/test-requests/{testRequest}',
            [MedicalReportController::class, 'showByTestRequest']
        );

        Route::get(
            '/{medicalReport}',
            [
                MedicalReportController::class,
                'show'
            ]
        );

        Route::get(
            '/{medicalReport}/pdf',
            [
                MedicalReportController::class,
                'exportPdf'
            ]
        );

        Route::post(
            '/{medicalReport}/notify',
            [
                MedicalReportController::class,
                'notify'
            ]
        );

    });
    Route::prefix('financial-reports')
        ->middleware('role:admin')
        ->group(function () {

        Route::get(
            '/export',
            [
                FinancialReportController::class,
                'export'
            ]
        );

        Route::get(
            '/',
            [
                FinancialReportController::class,
                'index'
            ]
        );

    });
    //done 3
    Route::prefix('payments')
        ->middleware('role:admin,receptionist')
        ->group(function () {

        Route::get(
            '/',
            [PaymentController::class, 'index']
        );

        Route::post(
            '/',
            [
                PaymentController::class,
                'store'
            ]
        );

        Route::get(
            '/invoices',
            [
                PaymentController::class,
                'invoices'
            ]
        );

        Route::get(
            '/invoices/{invoice}',
            [PaymentController::class, 'invoice']
        );

        Route::get(
            '/balances',
            [
                PaymentController::class,
                'balances'
            ]
        );

        Route::get(
            'patients/{patient}/balance',
            [
                PaymentController::class,
                'balance'
            ]
        );

        Route::get(
            '/{payment}',
            [PaymentController::class, 'show']
        );

    });
    // done 1
    Route::prefix('inventory-reports')
        ->middleware('role:admin')
        ->group(function () {


        Route::get(
            '/',
            [
                InventoryReportController::class,
                'index'
            ]
        );


    });
    // done 1
    Route::prefix('inventory-dashboard')
        ->middleware('role:admin,lab_technician')
        ->group(function () {

        Route::get(
            '/',
            [
                InventoryDashboardController::class,
                'index'
            ]
        );

    });
    // done 2
    Route::prefix('inventory')
        ->middleware('role:admin,lab_technician')
        ->group(function () {


        Route::get(
            'expired',
            [
                InventoryAlertController::class,
                'expired'
            ]
        );


        Route::get(
            'low-stock',
            [
                InventoryAlertController::class,
                'lowStock'
            ]
        );

        Route::get(
            'expiring-soon',
            [
                InventoryAlertController::class,
                'expiringSoon'
            ]
        );


    });
    // done 4
    Route::prefix('insurance-companies')->group(function () {

        Route::get(
            '/',
            [
                InsuranceCompanyController::class,
                'index'
            ]
        )->middleware('role:admin,receptionist');


        Route::post(
            '/',
            [
                InsuranceCompanyController::class,
                'store'
            ]
        )->middleware('role:admin');


        Route::put(
            '/{insuranceCompany}',
            [
                InsuranceCompanyController::class,
                'update'
            ]
        )->middleware('role:admin');


        Route::delete(
            '/{insuranceCompany}',
            [
                InsuranceCompanyController::class,
                'destroy'
            ]
        )->middleware('role:admin');


    });
    // done 4
    Route::prefix('coverage-rules')
        ->middleware('role:admin')
        ->group(function () {


        Route::get(
            '/',
            [
                CoverageRuleController::class,
                'index'
            ]
        );



        Route::post(
            '/',
            [
                CoverageRuleController::class,
                'store'
            ]
        );



        Route::put(
            '/{coverageRule}',
            [
                CoverageRuleController::class,
                'update'
            ]
        );



        Route::delete(
            '/{coverageRule}',
            [
                CoverageRuleController::class,
                'destroy'
            ]
        );


    });

    Route::prefix('test-requests')
        ->middleware('role:admin,receptionist')
        ->group(function () {
            Route::post(
                '{testRequest}/insurance',
                [InsuranceController::class, 'apply']
            );

            Route::get(
                '{testRequest}/insurance',
                [InsuranceController::class, 'calculate']
            );
        });
    // done 6
    Route::prefix('reagents')
        ->middleware('role:admin,lab_technician')
        ->group(function () {

        Route::get(
            '/',
            [ReagentController::class, 'index']
        );

        Route::post(
            '/',
            [ReagentController::class, 'store']
        )->middleware('role:admin');

        Route::get(
            '{reagent}',
            [ReagentController::class, 'show']
        );

        Route::put(
            '{reagent}',
            [ReagentController::class, 'update']
        )->middleware('role:admin');

        Route::patch(
            '{reagent}/stock',
            [ReagentController::class, 'updateStock']
        );

        Route::delete(
            '{reagent}',
            [ReagentController::class, 'destroy']
        )->middleware('role:admin');

    });
    // done 2
    Route::prefix('audit-logs')
        ->middleware('audit.access')
        ->group(function () {


        Route::get(
            '/',
            [
                AuditLogController::class,
                'index'
            ]
        )->name('audit-logs.index');


        Route::get(
            '/export',
            [
                AuditLogController::class,
                'export'
            ]
        )->name('audit-logs.export');


        Route::get(
            '/{type}/{id}',
            [
                AuditLogController::class,
                'entity'
            ]
        )->whereUuid('id')->name('audit-logs.entity');


    });
    // done 3
    Route::prefix('notifications')->group(function () {


        Route::post(
            '/{notification}/send',
            [
                NotificationController::class,
                'send'
            ]
        )->middleware('role:admin,receptionist');

        Route::get(
            '/',
            [
                NotificationController::class,
                'index'
            ]
        );

        Route::post(
            '/{notification}/read',
            [
                NotificationController::class,
                'read'
            ]
        );
    });
    // done 5 
    Route::prefix('tests')
        ->middleware('role:admin,receptionist,lab_technician')
        ->group(function () {

        Route::get(
            '/',
            [TestController::class, 'index']
        );

        Route::post(
            '/',
            [TestController::class, 'store']
        )->middleware('role:admin');

        Route::get(
            '{test}',
            [TestController::class, 'show']
        );

        Route::put(
            '{test}',
            [TestController::class, 'update']
        )->middleware('role:admin');

        Route::delete(
            '{test}',
            [TestController::class, 'destroy']
        )->middleware('role:admin');

    });
});

// not used 
// Route::prefix('test-requests')->group(function () {

//     Route::post(
//         '{testRequest}/insurance',
//         [
//             InsuranceController::class,
//             'apply'
//         ]
//     );


//     Route::get(
//         '{testRequest}/insurance',
//         [
//             InsuranceController::class,
//             'calculate'
//         ]
//     );


// });
