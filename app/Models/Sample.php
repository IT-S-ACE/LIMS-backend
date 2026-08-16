<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class Sample extends Model
{

    use HasFactory, HasUuids;



    protected $fillable = [

        'test_request_id',
        'qr_code',
        'status',
        'received_at',
        'collected_at',
        'rejected_reason',
        'cancelled_reason',
        'reagents_consumed_at',
        'sample_number',
        'barcode',
        'sample_type',
    ];


    protected $casts = [

        'received_at' => 'datetime',
        'collected_at' => 'datetime',
        'reagents_consumed_at' => 'datetime',

    ];

    public function testRequest()
    {
        return $this->belongsTo(
            TestRequest::class
        );
    }

    public function results()
    {
        return $this->hasMany(
            TestResult::class
        );
    }

    public function testResults()
    {
        return $this->hasMany(
            TestResult::class
        );
    }

    public function statusHistories()
    {
        return $this->hasMany(SampleStatusHistory::class)->oldest('created_at');
    }

    public function reagentConsumptions()
    {
        return $this->hasMany(ReagentConsumption::class);
    }


}
