<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'invoice_number',
        'test_request_id',
        'total',
        'insurance_amount',
        'patient_due',
        'paid',
        'remaining',
        'status',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'patient_due' => 'decimal:2',
        'paid' => 'decimal:2',
        'remaining' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invoice $invoice) {
            if (empty($invoice->id)) {
                $invoice->id = (string) Str::uuid();
            }

            if (empty($invoice->invoice_number)) {
                $lastNumber = static::query()
                    ->lockForUpdate()
                    ->orderByRaw("CAST(SUBSTRING(invoice_number, 5) AS UNSIGNED) DESC")
                    ->value('invoice_number');

                $next = $lastNumber
                    ? ((int) str_replace('INV-', '', $lastNumber)) + 1
                    : 1001;

                $invoice->invoice_number = 'INV-' . str_pad(
                    (string) $next,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    public function testRequest(): BelongsTo
    {
        return $this->belongsTo(TestRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function calculateBalance(): float
    {
        return max(0, (float) $this->patient_due - (float) $this->paid);
    }
}
