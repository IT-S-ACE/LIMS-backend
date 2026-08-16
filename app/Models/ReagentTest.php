<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReagentTest extends Model
{
    use HasFactory;

    protected $table = 'reagent_test';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'test_id',
        'reagent_id',
        'quantity_used',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ReagentTest $reagentTest) {
            if (empty($reagentTest->id)) {
                $reagentTest->id = (string) Str::uuid();
            }
        });
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function reagent(): BelongsTo
    {
        return $this->belongsTo(Reagent::class);
    }
}