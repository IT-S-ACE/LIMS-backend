<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TestResultStatusHistory extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'test_result_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function result()
    {
        return $this->belongsTo(TestResult::class, 'test_result_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
