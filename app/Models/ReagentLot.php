<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReagentLot extends Model
{
    use HasUuids;

    protected $fillable = [
        'reagent_id',
        'lot_number',
        'initial_quantity',
        'remaining_quantity',
        'expiry_date',
        'received_at',
        'unit_price',
    ];

    protected $casts = [
        'initial_quantity' => 'decimal:3',
        'remaining_quantity' => 'decimal:3',
        'expiry_date' => 'date',
        'received_at' => 'date',
        'unit_price' => 'decimal:3',
    ];

    public function reagent(): BelongsTo
    {
        return $this->belongsTo(Reagent::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(ReagentConsumption::class);
    }
}
