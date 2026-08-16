<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_number',
        'user_id',
        'name',
        'gender',
        'phone',
        'email',
        'dob',
    ];

    protected $casts = [
        'id' => 'string',
        'dob' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function testRequests(): HasMany
    {
        return $this->hasMany(TestRequest::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($patient) {

            $patient->patient_number =
                'PAT-' .
                str_pad(
                    static::count() + 1001,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

        });
    }
}