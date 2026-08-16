<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;


class TestResult extends Model
{

    use HasFactory, HasUuids;



    protected $fillable = [

        'sample_id',

        'test_request_item_id',

        'result_number',

        'value',

        'value_unit',

        'reference_range',

        'flag',

        'status',
        'workflow_status',
        'notes',
        'entered_by',
        'entered_at',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'correction_reason',
        'approved',
        'approved_by',
        'approved_at',

    ];



    protected $casts = [

        'approved' => 'boolean',
        'entered_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',

    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($result) {

            if ($result->result_number) {
                return;
            }

            do {
                $number = 'RES-' . now()->format('ymd') . '-' . Str::upper(Str::random(8));
            } while (static::query()->where('result_number', $number)->exists());

            $result->result_number = $number;

        });
    }
    
    public function sample()
    {
        return $this->belongsTo(
            Sample::class
        );
    }

    public function testRequestItem()
    {
        return $this->belongsTo(
            TestRequestItem::class
        );
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function statusHistories()
    {
        return $this->hasMany(TestResultStatusHistory::class)->oldest('created_at');
    }

}
