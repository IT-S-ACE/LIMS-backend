<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class InsuranceCompany extends Model
{

    use HasFactory, HasUuids;


    protected $fillable = [

        'code',
        'name',
        'email',
        'phone',
        'default_coverage',
        'status'

    ];


    protected $casts = [

        'default_coverage' => 'decimal:2'

    ];



    public function coverageRules()
    {
        return $this->hasMany(
            CoverageRule::class
        );
    }



    public function testRequests()
    {
        return $this->hasMany(
            TestRequest::class
        );
    }

}