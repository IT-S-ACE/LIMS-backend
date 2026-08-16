<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class MedicalReport extends Model
{

    use HasFactory, HasUuids;



    protected $fillable = [

        'test_request_id',
        'pdf_path',
        'generated_at',

    ];



    protected $casts = [

        'generated_at' => 'datetime',

    ];

    public function testRequest()
    {
        return $this->belongsTo(
            TestRequest::class,
            'test_request_id'
        );
    }



}