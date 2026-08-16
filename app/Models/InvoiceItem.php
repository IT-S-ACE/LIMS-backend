<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $table = 'invoice_items';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'invoice_id',
        'test_request_item_id',
        'price',
        'quantity',
        'line_total',
        'coverage_percent',
        'insurance_amount',
        'patient_amount',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'line_total' => 'decimal:2',
        'coverage_percent' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'patient_amount' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (InvoiceItem $invoiceItem) {
            if (empty($invoiceItem->id)) {
                $invoiceItem->id = (string) Str::uuid();
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function testRequestItem(): BelongsTo
    {
        return $this->belongsTo(TestRequestItem::class);
    }
}
