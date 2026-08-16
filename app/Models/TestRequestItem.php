<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class TestRequestItem extends Model
{

    use HasFactory, HasUuids;



    protected $fillable = [

        'test_request_id',
        'test_id',
        'quantity',
        'price',

    ];



    protected $casts = [

        'price' => 'decimal:2',
        'quantity' => 'integer',

    ];

    public function testRequest()
    {
        return $this->belongsTo(
            TestRequest::class
        );
    }

    public function test()
    {
        return $this->belongsTo(
            Test::class
        );
    }

    public function invoiceItem()
    {
        return $this->hasOne(InvoiceItem::class);
    }

    public function testResults()
    {
        return $this->hasMany(
            TestResult::class
        );
    }
}