<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory;


    protected $keyType = 'string';
    public $incrementing = false;


    protected $fillable = [
        'patient_id',
        'type',
        'message',
        'channel',
        'status'
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = Str::uuid();
        });
    }


    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }


    public function send()
    {
        $this->status = 'sent';
        $this->save();
    }
}