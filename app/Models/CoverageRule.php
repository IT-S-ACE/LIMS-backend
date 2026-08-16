<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class CoverageRule extends Model
{
    use HasFactory;


    protected $keyType = 'string';

    public $incrementing = false;


    protected $fillable = [

        'insurance_company_id',

        'test_id',

        'test_code',

        'coverage_percent',

        'max_amount'

    ];



    protected $casts = [

        'coverage_percent' => 'decimal:2',

        'max_amount' => 'decimal:2'

    ];


    protected static function boot()
    {
        parent::boot();


        static::creating(function ($model) {

            $model->id = Str::uuid();

        });

    }

    public function insuranceCompany()
    {
        return $this->belongsTo(
            InsuranceCompany::class
        );
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function calculateCoverage($amount)
    {


        $coverage =
            ($amount * $this->coverage_percent) / 100;



        if (
            $this->max_amount &&
            $coverage > $this->max_amount
        ) {

            $coverage = $this->max_amount;

        }



        return $coverage;

    }

}
