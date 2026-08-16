<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class TestRequest extends Model
{

    use HasFactory, HasUuids;



    protected $fillable = [

        'patient_id',
        'request_number',
        'insurance_company_id',
        'status',
        'total_price',

    ];



    protected $casts = [

        'total_price' => 'decimal:2',

    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function insuranceCompany()
    {
        return $this->belongsTo(
            InsuranceCompany::class
        );
    }

    public function items()
    {
        return $this->hasMany(
            TestRequestItem::class
        );
    }

    public function samples()
    {
        return $this->hasMany(
            Sample::class
        );
    }

    public function invoice()
    {
        return $this->hasOne(
            Invoice::class
        );
    }

    public function medicalReport()
    {
        return $this->hasOne(
            MedicalReport::class
        );
    }

    public function results()
    {
        return $this->hasManyThrough(
            TestResult::class,
            TestRequestItem::class,
            'test_request_id',
            'test_request_item_id'
        );
    }

}
