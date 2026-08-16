<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SampleStatusHistory extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'sample_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
