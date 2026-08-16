<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'payment_number',
        'invoice_id',
        'recorded_by',
        'amount',
        'method',
        'notes',
        'date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'datetime',
    ];



    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {

            if (empty($payment->id)) {
                $payment->id = (string) Str::uuid();
            }

            if (empty($payment->payment_number)) {
                $lastNumber = static::query()
                    ->lockForUpdate()
                    ->orderByRaw("CAST(SUBSTRING(payment_number, 5) AS UNSIGNED) DESC")
                    ->value('payment_number');

                $next = $lastNumber
                    ? ((int) str_replace('PAY-', '', $lastNumber)) + 1
                    : 5001;

                $payment->payment_number = 'PAY-' . $next;
            }

        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }


    public function validate()
    {
        return $this->amount > 0;
    }
}
