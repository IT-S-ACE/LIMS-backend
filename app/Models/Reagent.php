<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reagent extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;


    protected $fillable = [
        'name',

        'code',

        'category',

        'stock_qty',

        'min_stock',

        'expiry_date',

        'unit_price',
    ];

    protected $casts = [

        'stock_qty' => 'decimal:2',

        'min_stock' => 'decimal:2',

        'unit_price' => 'decimal:2',

        'expiry_date' => 'date'

    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = Str::uuid();
        });
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function lots(): HasMany
    {
        return $this->hasMany(ReagentLot::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(ReagentConsumption::class);
    }


    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(
            Test::class,
            'reagent_test'
        )
            ->withPivot([
                'id',
                'quantity_used',
            ])
            ->withTimestamps();
    }

    public function testUsages(): HasMany
    {
        return $this->hasMany(
            ReagentTest::class,
            'reagent_id'
        );
    }
}
