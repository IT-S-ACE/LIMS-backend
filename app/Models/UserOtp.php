<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class UserOtp extends Model
{
    use HasFactory;

    protected $table = 'user_otps';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'otp',
        'otp_hash',
        'attempts',
        'type',
        'expires_at',
        'verified_at'
    ];

    protected $casts = [
        'attempts' => 'integer',
        'expires_at'=>'datetime',
        'verified_at'=>'datetime'
    ];

    protected $hidden = [
        'otp',
        'otp_hash',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function($model){
            $model->id = Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
