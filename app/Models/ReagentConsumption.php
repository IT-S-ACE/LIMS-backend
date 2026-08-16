<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReagentConsumption extends Model
{
    use HasUuids;

    protected $fillable = [
        'sample_id',
        'test_request_item_id',
        'reagent_id',
        'reagent_lot_id',
        'quantity',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function testRequestItem(): BelongsTo
    {
        return $this->belongsTo(TestRequestItem::class);
    }

    public function reagent(): BelongsTo
    {
        return $this->belongsTo(Reagent::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ReagentLot::class, 'reagent_lot_id');
    }
}
