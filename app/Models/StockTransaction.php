<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class StockTransaction extends Model
{
    use HasFactory;


    protected $keyType = 'string';
    public $incrementing = false;


    protected $fillable = [
        'reagent_id',
        'reagent_lot_id',
        'sample_id',
        'created_by',
        'quantity',
        'type',
        'reason',
        'reference',
        'date'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'date' => 'datetime',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = Str::uuid();
        });
    }


    public function reagent()
    {
        return $this->belongsTo(Reagent::class);
    }

    public function lot()
    {
        return $this->belongsTo(ReagentLot::class, 'reagent_lot_id');
    }

    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function logTransaction()
    {
        return $this->save();
    }
}
