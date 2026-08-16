<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{

    use HasFactory, HasUuids;



    protected $fillable = [

        'name',
        'price',
        'reference_range',
        'unit',
        'result_type',
        'result_options',
        'critical_low',
        'critical_high',
    ];



    protected $casts = [

        'price' => 'decimal:2',
        'result_options' => 'array',
        'critical_low' => 'decimal:4',
        'critical_high' => 'decimal:4',

    ];

    public function testRequestItems()
    {
        return $this->hasMany(TestRequestItem::class);
    }

    public function reagents(): BelongsToMany
    {
        return $this->belongsToMany(
            Reagent::class,
            'reagent_test'
        )
            ->withPivot([
                'id',
                'quantity_used',
            ])
            ->withTimestamps();
    }

    public function reagentUsages(): HasMany
    {
        return $this->hasMany(
            ReagentTest::class,
            'test_id'
        );
    }

}
